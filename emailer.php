<?php
require_once 'config.php';
require_once 'database.php';


class EmailSender {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    

    public function sendSingleEmail($emailData) {
        $curl = curl_init();
        
        $postData = [
            'subject' => EMAIL_SUBJECT,
            'name' => SENDER_NAME,
            'html' => EMAIL_HTML_TEMPLATE,
            'from' => SENDER_EMAIL,
            'to' => $emailData['email'],
            'to_name' => $emailData['username'] ?? 'User'
        ];
        
        curl_setopt_array($curl, [
            CURLOPT_URL => SMTP_API_URL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => CURL_TIMEOUT,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_HTTPHEADER => [
                "authorization: " . SMTP_API_KEY,
                "content-type: application/x-www-form-urlencoded"
            ],
            CURLOPT_POSTFIELDS => http_build_query($postData)
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            return [
                'success' => false,
                'error' => "cURL Error: " . $error
            ];
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'response' => $response
            ];
        } else {
            return [
                'success' => false,
                'error' => "HTTP Error $httpCode: " . $response
            ];
        }
    }
    
 
    public function sendBatch() {
        $emails = $this->db->getNextBatch();
        $results = [
            'sent' => 0,
            'failed' => 0,
            'total' => count($emails),
            'permanent_failures' => 0
        ];
        
        if (empty($emails)) {
            return array_merge($results, ['message' => 'Нет писем для отправки']);
        }
        
        foreach ($emails as $emailData) {
            $result = $this->sendSingleEmail($emailData);
            
            if ($result['success']) {
                $this->db->markAsSent($emailData['id']);
                $results['sent']++;
                $this->logMessage("✓ Отправлено: {$emailData['email']}");
            } else {
                $errorType = $this->analyzeError($result['error']);
                
                if ($errorType['permanent']) {
                    $this->db->markAsFailed($emailData['id'], $result['error']);
                    $results['failed']++;
                    $results['permanent_failures']++;
                    $this->logError("✗ Постоянная ошибка: {$emailData['email']} - {$errorType['reason']}");
                } else {
                    $attempts = $emailData['attempts'] + 1;
                    
                    if ($attempts >= MAX_RETRIES) {
                        $this->db->markAsFailed($emailData['id'], $result['error']);
                        $results['failed']++;
                        $this->logError("✗ Не удалось отправить (финальная попытка): {$emailData['email']} - {$result['error']}");
                    } else {
                        $this->db->markForRetry($emailData['id'], $result['error']);
                        $this->logError("⚠ Временная ошибка (попытка $attempts): {$emailData['email']} - {$result['error']}");
                        
                        // Задержка перед следующей попыткой
                        sleep(RETRY_DELAY);
                    }
                }
            }
            
            usleep(100000); 
        }
        
        $this->db->updateLastBatchTime();
        
        return $results;
    }
    

    private function analyzeError($errorMessage) {
        $errorMessage = strtolower($errorMessage);
        
        $permanentErrorPatterns = [
            'the email account that you tried to reach does not exist' => 'Несуществующий Gmail аккаунт',
            'user is terminated' => 'Аккаунт заблокирован',
            'account is disabled' => 'Аккаунт отключен',
            'invalid mailbox' => 'Неверный почтовый ящик',
            'local mailbox .* is unavailable' => 'Почтовый ящик недоступен',
            'no such user' => 'Пользователь не существует',
            'user unknown' => 'Неизвестный пользователь',
            'mailbox unavailable' => 'Почтовый ящик недоступен',
            'recipient address rejected' => 'Адрес получателя отклонен',
            'domain not found' => 'Домен не найден',
            '550' => 'Постоянная ошибка доставки (550)',
            '5.1.1' => 'Несуществующий адрес',
            '5.1.10' => 'Адрес не существует',
        ];
        
        foreach ($permanentErrorPatterns as $pattern => $reason) {
            if (strpos($errorMessage, $pattern) !== false) {
                return [
                    'permanent' => true,
                    'reason' => $reason
                ];
            }
        }
        
        $temporaryErrorPatterns = [
            '4' => 'Временная ошибка сервера',
            'timeout' => 'Таймаут соединения',
            'connection' => 'Проблема соединения',
            'temporary' => 'Временная ошибка',
            'rate limit' => 'Превышен лимит отправки',
            'throttled' => 'Ограничение скорости',
        ];
        
        foreach ($temporaryErrorPatterns as $pattern => $reason) {
            if (strpos($errorMessage, $pattern) !== false) {
                return [
                    'permanent' => false,
                    'reason' => $reason
                ];
            }
        }
        
        return [
            'permanent' => false,
            'reason' => 'Неизвестная ошибка (считается временной)'
        ];
    }
    

    private function logMessage($message) {
        $logEntry = date('Y-m-d H:i:s') . " - " . $message . PHP_EOL;
        file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
    }
    

    private function logError($message) {
        $logEntry = date('Y-m-d H:i:s') . " - " . $message . PHP_EOL;
        file_put_contents(ERROR_LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
        $this->logMessage($message); // Дублируем в основной лог
    }
    
 
    public function importEmails() {
        return $this->db->importEmailsFromDump();
    }
    
 
    public function getStats() {
        return $this->db->getStats();
    }
    

    public function searchEmails($query, $status = null, $perPage = 50, $page = 1) {
        return $this->db->searchEmails($query, $status, $perPage, $page);
    }
    

    public function cleanupInvalidEmails() {
        return $this->db->cleanupInvalidEmails();
    }
}

if (php_sapi_name() === 'cli' || (isset($_GET['action']) && $_GET['action'] === 'send_batch')) {
    $emailer = new EmailSender();
    $result = $emailer->sendBatch();
    
    if (php_sapi_name() === 'cli') {
        echo "Результат отправки: отправлено {$result['sent']}, ошибок {$result['failed']} из {$result['total']}" . PHP_EOL;
    } else {
        header('Content-Type: application/json');
        echo json_encode($result);
    }
}
?>
