<?php
require_once 'config.php';
require_once 'emailer.php';

$emailer = new EmailSender();
$action = $_GET['action'] ?? 'dashboard';
$message = '';

if ($_POST) {
    switch ($_POST['action']) {
        case 'import':
            $result = $emailer->importEmails();
            $message = $result['success'] 
                ? "Успешно импортировано {$result['imported']} email адресов" 
                : "Ошибка: {$result['message']}";
            break;
            
        case 'send_batch':
            $result = $emailer->sendBatch();
            $message = "Отправлено: {$result['sent']}, ошибок: {$result['failed']} из {$result['total']}";
            break;
    }
}

$stats = $emailer->getStats();
$searchQuery = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';

if ($searchQuery) {
    $emails = $emailer->searchEmails($searchQuery, $filterStatus ?: null);
} else {
    $emails = $emailer->searchEmails('', $filterStatus ?: null);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ApexNodes Email Manager</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-left: 5px solid;
        }
        
        .stat-card.total { border-left-color: #3498db; }
        .stat-card.sent { border-left-color: #2ecc71; }
        .stat-card.pending { border-left-color: #f39c12; }
        .stat-card.failed { border-left-color: #e74c3c; }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1rem;
        }
        
        .actions {
            padding: 30px;
            border-bottom: 1px solid #eee;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            margin-right: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn.success {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }
        
        .btn.warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .search-filters {
            padding: 30px;
            background: #f8f9fa;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            min-width: 300px;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .select {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
        }
        
        .table-container {
            padding: 30px;
            overflow-x: auto;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status.sent {
            background: #d4edda;
            color: #155724;
        }
        
        .status.failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status.retry {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2ecc71, #27ae60);
            transition: width 0.3s ease;
        }
        
        .message {
            padding: 15px;
            margin: 20px 30px;
            border-radius: 8px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .last-update {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 0.9rem;
            background: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .search-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input {
                min-width: auto;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 ApexNodes smap email</h1>
            <p>Система массовой рассылки email на ApexNodes by prd_yt</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number"><?= number_format($stats['total_emails'] ?? 0) ?></div>
                <div class="stat-label">Всего email</div>
            </div>
            <div class="stat-card sent">
                <div class="stat-number"><?= number_format($stats['sent_emails'] ?? 0) ?></div>
                <div class="stat-label">Отправлено</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-number"><?= number_format($stats['pending_emails'] ?? 0) ?></div>
                <div class="stat-label">Осталось</div>
            </div>
            <div class="stat-card failed">
                <div class="stat-number"><?= number_format($stats['failed_emails'] ?? 0) ?></div>
                <div class="stat-label">Ошибок</div>
            </div>
        </div>
        
        <?php if ($stats['total_emails'] > 0): ?>
            <div style="padding: 0 30px;">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= round(($stats['sent_emails'] / $stats['total_emails']) * 100, 2) ?>%"></div>
                </div>
                <p style="text-align: center; color: #666; margin-bottom: 20px;">
                    Прогресс: <?= round(($stats['sent_emails'] / $stats['total_emails']) * 100, 2) ?>%
                    (<?= $stats['sent_emails'] ?> из <?= $stats['total_emails'] ?>)
                </p>
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <form method="post" style="display: inline-block;">
                <input type="hidden" name="action" value="import">
                <button type="submit" class="btn">📥 Импортировать Email</button>
            </form>
            
            <form method="post" style="display: inline-block;">
                <input type="hidden" name="action" value="send_batch">
                <button type="submit" class="btn success">📤 Отправить партию (<?= EMAILS_PER_HOUR ?> писем)</button>
            </form>
            
            <a href="preview.php" class="btn">👁️ Предварительный просмотр</a>
            
            <a href="test_single.php" class="btn warning">📧 Тестовая отправка</a>
            
            <button onclick="location.reload()" class="btn">🔄 Обновить</button>
            
            <a href="emailer.php?action=send_batch" target="_blank" class="btn warning">⚡ Автоотправка</a>
        </div>
        
        <div class="search-filters">
            <form method="get" style="display: flex; gap: 15px; flex: 1; align-items: center;">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Поиск по email или username..." 
                    value="<?= htmlspecialchars($searchQuery) ?>"
                    class="search-input"
                >
                <select name="status" class="select">
                    <option value="">Все статусы</option>
                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>В ожидании</option>
                    <option value="sent" <?= $filterStatus === 'sent' ? 'selected' : '' ?>>Отправлено</option>
                    <option value="failed" <?= $filterStatus === 'failed' ? 'selected' : '' ?>>Ошибка</option>
                    <option value="retry" <?= $filterStatus === 'retry' ? 'selected' : '' ?>>Повтор</option>
                </select>
                <button type="submit" class="btn">🔍 Поиск</button>
            </form>
        </div>
        
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Discord ID</th>
                        <th>Статус</th>
                        <th>Попытки</th>
                        <th>Последняя попытка</th>
                        <th>Отправлено</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($emails)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #666;">
                                <?= $searchQuery ? 'Ничего не найдено по запросу' : 'Нет данных для отображения' ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($emails as $email): ?>
                            <tr>
                                <td><?= $email['id'] ?></td>
                                <td><?= htmlspecialchars($email['email']) ?></td>
                                <td><?= htmlspecialchars($email['username'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($email['discord_id'] ?? '-') ?></td>
                                <td><span class="status <?= $email['status'] ?>"><?= $email['status'] ?></span></td>
                                <td><?= $email['attempts'] ?></td>
                                <td><?= $email['last_attempt'] ? date('d.m.Y H:i', strtotime($email['last_attempt'])) : '-' ?></td>
                                <td><?= $email['sent_at'] ? date('d.m.Y H:i', strtotime($email['sent_at'])) : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="last-update">
            Последнее обновление: <?= date('d.m.Y H:i:s') ?>
            <?php if ($stats['last_batch_sent']): ?>
                | Последняя отправка: <?= date('d.m.Y H:i:s', strtotime($stats['last_batch_sent'])) ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
