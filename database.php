<?php
require_once 'config.php';


class Database {
    private $pdo;
    
    public function __construct() {
        $this->connect();
        $this->createTables();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]);
        } catch (PDOException $e) {
            die("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }
    

    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS email_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL,
            username VARCHAR(255) DEFAULT NULL,
            discord_id VARCHAR(255) DEFAULT NULL,
            status ENUM('pending', 'sent', 'failed', 'retry') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            last_attempt TIMESTAMP NULL,
            sent_at TIMESTAMP NULL,
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_status (status),
            INDEX idx_email (email)
        );
        
        CREATE TABLE IF NOT EXISTS email_stats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            total_emails INT DEFAULT 0,
            sent_emails INT DEFAULT 0,
            failed_emails INT DEFAULT 0,
            pending_emails INT DEFAULT 0,
            last_batch_sent TIMESTAMP NULL,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        
        -- Инициализация статистики
        INSERT IGNORE INTO email_stats (id, total_emails) VALUES (1, 0);
        ";
        
        $this->pdo->exec($sql);
    }
    

    public function importEmailsFromDump() {
        $count = $this->pdo->query("SELECT COUNT(*) FROM email_queue")->fetchColumn();
        if ($count > 0) {
            return ["success" => false, "message" => "Данные уже импортированы"];
        }
        
        $sqlContent = file_get_contents(SOURCE_DB_FILE);
        if (!$sqlContent) {
            return ["success" => false, "message" => "Не удалось прочитать SQL файл"];
        }
        
        preg_match_all("/INSERT INTO `users`.*?VALUES\s*(.*?);/s", $sqlContent, $matches);
        
        $imported = 0;
        $stmt = $this->pdo->prepare("INSERT INTO email_queue (email, username, discord_id) VALUES (?, ?, ?)");
        
        foreach ($matches[1] as $valuesString) {
            preg_match_all("/\(([^)]+)\)/", $valuesString, $valueMatches);
            
            foreach ($valueMatches[1] as $valueGroup) {
                $values = str_getcsv($valueGroup, ',', "'");
                
                if (count($values) >= 5) {
                    $email = trim($values[1], "'\"");
                    $username = trim($values[2], "'\"");
                    $discord_id = trim($values[4], "'\"");
                    
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $stmt->execute([$email, $username, $discord_id]);
                            $imported++;
                        } catch (PDOException $e) {
                            if ($e->getCode() != 23000) {
                                error_log("Ошибка вставки email: " . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }
        
        $this->updateStats();
        
        return ["success" => true, "imported" => $imported];
    }
    

    public function getNextBatch($limit = EMAILS_PER_HOUR) {
        $limit = (int) $limit;
        
        $stmt = $this->pdo->prepare("
            SELECT * FROM email_queue 
            WHERE status = 'pending' OR (status = 'retry' AND attempts < ?)
            ORDER BY created_at ASC 
            LIMIT " . $limit
        );
        $stmt->execute([MAX_RETRIES]);
        return $stmt->fetchAll();
    }
    

    public function markAsSent($id) {
        $stmt = $this->pdo->prepare("
            UPDATE email_queue 
            SET status = 'sent', sent_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$id]);
        $this->updateStats();
    }
    

    public function markAsFailed($id, $errorMessage = null) {
        $stmt = $this->pdo->prepare("
            UPDATE email_queue 
            SET status = 'failed', 
                attempts = attempts + 1,
                last_attempt = NOW(),
                error_message = ?
            WHERE id = ?
        ");
        $stmt->execute([$errorMessage, $id]);
        $this->updateStats();
    }
    

    public function markForRetry($id, $errorMessage = null) {
        $stmt = $this->pdo->prepare("
            UPDATE email_queue 
            SET status = 'retry', 
                attempts = attempts + 1,
                last_attempt = NOW(),
                error_message = ?
            WHERE id = ?
        ");
        $stmt->execute([$errorMessage, $id]);
    }
    

    public function updateStats() {
        $stats = $this->pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' OR status = 'retry' THEN 1 ELSE 0 END) as pending
            FROM email_queue
        ")->fetch();
        
        $this->pdo->prepare("
            UPDATE email_stats 
            SET total_emails = ?, 
                sent_emails = ?, 
                failed_emails = ?, 
                pending_emails = ?
            WHERE id = 1
        ")->execute([
            $stats['total'], 
            $stats['sent'], 
            $stats['failed'], 
            $stats['pending']
        ]);
    }
    
 
    public function getStats() {
        return $this->pdo->query("SELECT * FROM email_stats WHERE id = 1")->fetch();
    }
    

    public function searchEmails($query, $status = null, $perPage = 50, $page = 1) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM email_queue WHERE (email LIKE ? OR username LIKE ?)";
        $params = ["%$query%", "%$query%"];
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    

    public function updateLastBatchTime() {
        $this->pdo->query("UPDATE email_stats SET last_batch_sent = NOW() WHERE id = 1");
    }
    

    public function cleanupInvalidEmails() {
        $invalidPatterns = [
            '%test%@%',
            '%fake%@%', 
            '%temp%@%',
            '%@example.%',
            '%@test.%',
            '%noreply%@%',
            '%no-reply%@%',
            '%admin%@localhost%',
            '%@localhost%'
        ];
        
        $deletedCount = 0;
        
        foreach ($invalidPatterns as $pattern) {
            $stmt = $this->pdo->prepare("DELETE FROM email_queue WHERE email LIKE ?");
            $stmt->execute([$pattern]);
            $deletedCount += $stmt->rowCount();
        }
        
        $invalidDomains = [
            'example.com',
            'example.org', 
            'test.com',
            'localhost',
            'invalid.com',
            '10minutemail.com'
        ];
        
        foreach ($invalidDomains as $domain) {
            $stmt = $this->pdo->prepare("DELETE FROM email_queue WHERE email LIKE ?");
            $stmt->execute(["%@$domain"]);
            $deletedCount += $stmt->rowCount();
        }
        
        $permanentErrorPatterns = [
            '%does not exist%',
            '%user is terminated%',
            '%account is disabled%',
            '%invalid mailbox%',
            '%is unavailable%',
            '%no such user%',
            '%user is over quota%',
            '%relay access denied%',
            '%dns error%',
            '%domain name not found%',
            '%connection refused%',
            '%connection timed out%',
            '%network error%'
        ];
        
        foreach ($permanentErrorPatterns as $pattern) {
            $stmt = $this->pdo->prepare("DELETE FROM email_queue WHERE status = 'failed' AND error_message LIKE ?");
            $stmt->execute([$pattern]);
            $deletedCount += $stmt->rowCount();
        }
        
        $this->updateStats();
        
        return [
            'success' => true,
            'deleted' => $deletedCount,
            'message' => "Удалено $deletedCount нерабочих email адресов"
        ];
    }
}
?>
