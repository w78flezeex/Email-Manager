<?php
require_once 'config.php';
require_once 'emailer.php';

$emailer = new EmailSender();
$action = $_GET['action'] ?? 'dashboard';
$message = '';

// Обработка AJAX запроса для статистики
if (isset($_GET['ajax']) && $_GET['ajax'] === 'stats') {
    header('Content-Type: application/json');
    $stats = $emailer->getStats();
    echo json_encode($stats);
    exit;
}

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
$perPage = (int)($_GET['per_page'] ?? 50);
$page = (int)($_GET['page'] ?? 1);

if ($searchQuery) {
    $emails = $emailer->searchEmails($searchQuery, $filterStatus ?: null, $perPage, $page);
} else {
    $emails = $emailer->searchEmails('', $filterStatus ?: null, $perPage, $page);
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
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 15px;
            position: relative;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.98);
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 20px;
            text-align: center;
            position: relative;
        }
        
        .header h1 {
            font-size: 2rem;
            margin-bottom: 8px;
            font-weight: 700;
            letter-spacing: -0.01em;
        }
        
        .header p {
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 400;
        }
        
                 .stats-grid {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
             gap: 15px;
             padding: 20px;
             background: #f8f9fa;
         }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        
        .stat-card.total { border-left-color: #3498db; }
        .stat-card.sent { border-left-color: #2ecc71; }
        .stat-card.pending { border-left-color: #f39c12; }
        .stat-card.failed { border-left-color: #e74c3c; }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .actions {
            padding: 20px;
            border-bottom: 1px solid #eee;
            background: white;
        }
        
        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn.success {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
        }
        
        .btn.warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        .search-filters {
            padding: 15px 20px;
            background: white;
            border-bottom: 1px solid #eee;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-input, .select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            background: white;
            color: #333;
        }
        
        .search-input:focus, .select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
        }
        
        /* Стили для скроллбара */
        .table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
        
        .table-container {
            padding: 20px;
            overflow-x: auto;
            background: white;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .table-info {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .table-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .table-controls label {
            color: #666;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .table-controls select {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
            color: #333;
            font-size: 0.85rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            padding: 15px 0;
            border-top: 1px solid #eee;
        }
        
        .page-link {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
            color: #667eea;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .page-link:hover {
            background: #f8f9fa;
            border-color: #667eea;
            transform: translateY(-1px);
        }
        
        .page-link.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            font-size: 0.9rem;
        }
        
        .table th {
            background: #667eea;
            color: white;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            color: #2c3e50;
            transition: background 0.15s ease;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .table tr:nth-child(even) {
            background: #fafbfc;
        }
        
        .table tr:nth-child(even):hover {
            background: #f0f2f5;
        }
        
        .status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
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
        
        .last-update {
            padding: 15px 20px;
            text-align: center;
            color: #7f8c8d;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .message {
            padding: 12px 20px;
            margin: 15px 20px;
            border-radius: 8px;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            font-size: 0.9rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #2ecc71, #27ae60);
            transition: width 0.3s ease;
        }
        
        .auto-send-control {
            display: inline-block;
            margin: 0 10px;
            padding: 18px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            min-width: 300px;
            border: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }
        
        .auto-send-control:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.12);
        }
        
        .auto-send-label {
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            font-size: 1rem;
        }
        
        .auto-send-label input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
            accent-color: #667eea;
        }
        
        .control-row {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        
        .control-row label {
            color: #34495e;
            font-size: 0.85em;
            font-weight: 500;
            min-width: 70px;
        }
        
        .control-row select,
        .control-row input[type="time"] {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
            color: #2c3e50;
            font-size: 0.85em;
            transition: all 0.2s ease;
            min-width: 100px;
        }
        
        .control-row select:focus,
        .control-row input[type="time"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }
        
        .control-row button {
            padding: 8px 14px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 0.8em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .control-row button:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        
        .status-text {
            font-size: 0.85em;
            color: #7f8c8d;
            margin-bottom: 12px;
            text-align: center;
            font-weight: 500;
            padding: 6px;
            background: #f8f9fa;
            border-radius: 6px;
        }
        
        .timer-label {
            text-align: center;
            margin-bottom: 6px;
            font-size: 0.75em;
            color: #95a5a6;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .timer-display {
            font-family: 'Courier New', monospace;
            font-size: 1.2em;
            font-weight: 700;
            color: #667eea;
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            letter-spacing: 1px;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                margin: 10px;
                border-radius: 20px;
            }
            
            .header {
                padding: 30px 20px;
            }
            
            .header h1 {
                font-size: 2.2rem;
            }
            
                         .stats-grid {
                 grid-template-columns: 1fr;
                 gap: 20px;
                 padding: 30px 20px;
             }
            
            .actions {
                padding: 30px 20px;
                text-align: center;
            }
            
            .btn {
                display: block;
                width: 100%;
                margin: 10px 0;
            }
            
            .auto-send-control {
                display: block;
                margin: 20px 0;
                min-width: auto;
                width: 100%;
            }
            
            .control-row {
                flex-direction: column;
                align-items: stretch;
                gap: 8px;
            }
            
            .control-row label {
                min-width: auto;
                text-align: center;
            }
            
            .control-row select,
            .control-row input[type="time"] {
                min-width: auto;
                width: 100%;
            }
            
            .search-filters {
                padding: 25px 20px;
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .search-input {
                min-width: auto;
            }
            
            .table-container {
                padding: 20px;
            }
            
            .table {
                font-size: 0.9rem;
            }
            
            .table th,
            .table td {
                padding: 12px 8px;
            }
        }
        
        @media (max-width: 480px) {
            .header h1 {
                font-size: 1.8rem;
            }
            
            .header p {
                font-size: 1.1rem;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .stat-label {
                font-size: 1rem;
            }
            
            .auto-send-control {
                padding: 20px;
            }
            
            .timer-display {
                font-size: 1.2em;
                padding: 12px;
            }
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
                 <?php if (($stats['failed_emails'] ?? 0) > 0): ?>
                     <div style="font-size: 0.8rem; color: #e74c3c; margin-top: 5px;">
                         <button onclick="cleanupInvalidEmails()" style="background: #e74c3c; color: white; border: none; padding: 3px 8px; border-radius: 4px; cursor: pointer; font-size: 0.7rem;">
                             Очистить
                         </button>
                     </div>
                 <?php endif; ?>
             </div>
         </div>
        
        <?php if ($stats['total_emails'] > 0): ?>
            <div style="padding: 0 20px;">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= round(($stats['sent_emails'] / $stats['total_emails']) * 100, 2) ?>%"></div>
                </div>
                <p style="text-align: center; color: #666; margin-bottom: 15px; font-size: 0.9rem;">
                    Прогресс: <?= round(($stats['sent_emails'] / $stats['total_emails']) * 100, 2) ?>%
                    (<?= number_format($stats['sent_emails']) ?> из <?= number_format($stats['total_emails']) ?>)
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
            
            <div class="auto-send-control">
                <label class="auto-send-label">
                    <input type="checkbox" id="autoSendToggle">
                    <span>🤖 Автоотправка</span>
                </label>
                
                <div class="control-row">
                    <label>⏰ Интервал:</label>
                    <select id="timeInterval">
                        <option value="3600000">1 час</option>
                        <option value="1800000">30 минут</option>
                        <option value="900000">15 минут</option>
                        <option value="600000">10 минут</option>
                        <option value="300000">5 минут</option>
                        <option value="60000">1 минута</option>
                    </select>
                </div>
                
                <div class="control-row">
                    <label>🕐 Или время:</label>
                    <input type="time" id="customTime">
                    <button id="setCustomTime">Установить</button>
                </div>
                
                <div id="autoSendStatus" class="status-text"></div>
                <div class="timer-label">ТАЙМЕР ДО ОТПРАВКИ</div>
                <div id="autoSendTimer" class="timer-display"></div>
            </div>
            
            <a href="preview.php" class="btn">👁️ Предварительный просмотр</a>
            
                         <a href="test_single.php" class="btn warning">📧 Тестовая отправка</a>
             
             <button onclick="cleanupInvalidEmails()" class="btn warning">🧹 Очистить нерабочие</button>
             
             <button onclick="location.reload()" class="btn">🔄 Обновить</button>
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
            <div class="table-header">
                <div class="table-info">
                    Показано: <?= count($emails) ?> из <?= number_format($stats['total_emails'] ?? 0) ?> записей
                </div>
                <div class="table-controls">
                    <label>Показывать по:</label>
                    <select id="perPage" onchange="changePerPage()">
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="200">200</option>
                        <option value="500">500</option>
                    </select>
                </div>
            </div>
            
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
            
            <?php if ($stats['total_emails'] > $perPage): ?>
                <div class="pagination">
                    <?php
                    $totalPages = ceil($stats['total_emails'] / $perPage);
                    $currentPage = $page;
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    ?>
                    
                    <?php if ($currentPage > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="page-link">« Первая</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" class="page-link">‹ Предыдущая</a>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i == $currentPage): ?>
                            <span class="page-link active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-link"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" class="page-link">Следующая ›</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="page-link">Последняя »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="last-update">
            Последнее обновление: <?= date('d.m.Y H:i:s') ?>
            <?php if ($stats['last_batch_sent']): ?>
                | Последняя отправка: <?= date('d.m.Y H:i:s', strtotime($stats['last_batch_sent'])) ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let autoSendInterval;
        let autoSendEnabled = false;
        let nextSendTime = null;
        
        const autoSendToggle = document.getElementById('autoSendToggle');
        const autoSendStatus = document.getElementById('autoSendStatus');
        const autoSendTimer = document.getElementById('autoSendTimer');
        const timeInterval = document.getElementById('timeInterval');
        const customTime = document.getElementById('customTime');
        const setCustomTimeBtn = document.getElementById('setCustomTime');
        
        // Функция отправки партии
        async function sendBatch() {
            try {
                const response = await fetch('emailer.php?action=send_batch');
                const result = await response.json();
                
                if (result) {
                    const message = `Отправлено: ${result.sent}, ошибок: ${result.failed} из ${result.total}`;
                    console.log('Автоотправка:', message);
                    
                    // Показываем уведомление
                    showNotification(message, 'success');
                    
                                         // Обновляем статистику через 2 секунды, но НЕ перезагружаем страницу
                     setTimeout(() => {
                         // Обновляем статистику без перезагрузки
                         updateStatsWithoutReload();
                         
                         // Если есть много ошибок, предлагаем очистку
                         if (result.failed > result.sent * 0.3) { // Если ошибок больше 30% от отправленных
                             setTimeout(() => {
                                 if (confirm(`Обнаружено много ошибок (${result.failed} из ${result.total}). Хотите автоматически очистить нерабочие адреса?`)) {
                                     cleanupInvalidEmails();
                                 }
                             }, 3000);
                         }
                     }, 2000);
                }
            } catch (error) {
                console.error('Ошибка автоотправки:', error);
                showNotification('Ошибка автоотправки: ' + error.message, 'error');
            }
        }
        
        // Функция обновления статистики без перезагрузки
        async function updateStatsWithoutReload() {
            try {
                const response = await fetch('?ajax=stats');
                if (response.ok) {
                    const stats = await response.json();
                    // Обновляем статистику на странице
                    updateStatsDisplay(stats);
                }
            } catch (error) {
                console.error('Ошибка обновления статистики:', error);
            }
        }
        
        // Функция обновления отображения статистики
        function updateStatsDisplay(stats) {
            // Обновляем карточки статистики
            const totalCard = document.querySelector('.stat-card.total .stat-number');
            const sentCard = document.querySelector('.stat-card.sent .stat-number');
            const pendingCard = document.querySelector('.stat-card.pending .stat-number');
            const failedCard = document.querySelector('.stat-card.failed .stat-number');
            
            if (totalCard) totalCard.textContent = numberFormat(stats.total_emails || 0);
            if (sentCard) sentCard.textContent = numberFormat(stats.sent_emails || 0);
            if (pendingCard) pendingCard.textContent = numberFormat(stats.pending_emails || 0);
            if (failedCard) failedCard.textContent = numberFormat(stats.failed_emails || 0);
            
            // Обновляем прогресс-бар
            if (stats.total_emails > 0) {
                const progressFill = document.querySelector('.progress-fill');
                const progressText = document.querySelector('.progress-bar + p');
                if (progressFill) {
                    const percentage = round((stats.sent_emails / stats.total_emails) * 100, 2);
                    progressFill.style.width = percentage + '%';
                }
                if (progressText) {
                    progressText.innerHTML = `Прогресс: ${round((stats.sent_emails / stats.total_emails) * 100, 2)}% (${numberFormat(stats.sent_emails)} из ${numberFormat(stats.total_emails)})`;
                }
            }
            
            // Обновляем информацию о записях в таблице
            const tableInfo = document.querySelector('.table-info');
            if (tableInfo) {
                const perPage = document.getElementById('perPage')?.value || 50;
                tableInfo.textContent = `Показано: ${Math.min(perPage, stats.total_emails)} из ${numberFormat(stats.total_emails)} записей`;
            }
        }
        
        // Вспомогательные функции
        function numberFormat(num) {
            return new Intl.NumberFormat('ru-RU').format(num);
        }
        
        function round(num, decimals) {
            return Math.round(num * Math.pow(10, decimals)) / Math.pow(10, decimals);
        }
        
        // Функция показа уведомлений
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 1000;
                max-width: 400px;
                background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#007bff'};
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
        
        // Функция обновления статуса и таймера
        function updateStatus() {
            if (!autoSendEnabled) {
                autoSendStatus.textContent = '⭕ Автоотправка выключена';
                autoSendTimer.textContent = '--:--:--';
                autoSendTimer.style.color = '#6c757d';
                autoSendTimer.style.borderColor = '#dee2e6';
                return;
            }
            
            if (nextSendTime) {
                const now = new Date();
                const diff = nextSendTime - now;
                
                if (diff > 0) {
                    const hours = Math.floor(diff / 3600000);
                    const minutes = Math.floor((diff % 3600000) / 60000);
                    const seconds = Math.floor((diff % 60000) / 1000);
                    
                    autoSendStatus.textContent = '🟢 Автоотправка включена';
                    autoSendTimer.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    autoSendTimer.style.color = '#28a745';
                    autoSendTimer.style.borderColor = '#28a745';
                } else {
                    autoSendStatus.textContent = '📤 Отправка писем...';
                    autoSendTimer.textContent = '00:00:00';
                    autoSendTimer.style.color = '#ffc107';
                    autoSendTimer.style.borderColor = '#ffc107';
                }
            }
        }
        
        // Функция включения автоотправки
        function enableAutoSend(intervalMs = null) {
            const selectedInterval = intervalMs || parseInt(timeInterval.value);
            
            autoSendEnabled = true;
            autoSendToggle.checked = true;
            
            // Сохраняем состояние
            localStorage.setItem('autoSendEnabled', 'true');
            localStorage.setItem('autoSendStartTime', Date.now().toString());
            localStorage.setItem('autoSendInterval', selectedInterval.toString());
            
            // Запускаем автоотправку с выбранным интервалом
            nextSendTime = new Date(Date.now() + selectedInterval);
            autoSendInterval = setInterval(() => {
                sendBatch();
                nextSendTime = new Date(Date.now() + selectedInterval);
                localStorage.setItem('autoSendStartTime', Date.now().toString());
            }, selectedInterval);
            
            const intervalText = getIntervalText(selectedInterval);
            showNotification(`Автоотправка включена! Следующая отправка через ${intervalText}`, 'success');
            updateStatus();
        }
        
        // Функция выключения автоотправки
        function disableAutoSend() {
            autoSendEnabled = false;
            autoSendToggle.checked = false;
            
            // Очищаем сохраненное состояние
            localStorage.removeItem('autoSendEnabled');
            localStorage.removeItem('autoSendStartTime');
            localStorage.removeItem('autoSendInterval');
            
            // Останавливаем автоотправку
            if (autoSendInterval) {
                clearInterval(autoSendInterval);
                autoSendInterval = null;
            }
            nextSendTime = null;
            showNotification('Автоотправка выключена', 'info');
            updateStatus();
        }
        
        // Функция получения текста интервала
        function getIntervalText(ms) {
            const minutes = ms / 60000;
            if (minutes < 60) {
                return `${minutes} мин`;
            } else {
                const hours = minutes / 60;
                return `${hours} ч`;
            }
        }
        
        // Функция установки времени отправки
        function setCustomSendTime() {
            const timeValue = customTime.value;
            if (!timeValue) {
                showNotification('Выберите время для отправки!', 'error');
                return;
            }
            
            const now = new Date();
            const [hours, minutes] = timeValue.split(':');
            const targetTime = new Date();
            targetTime.setHours(parseInt(hours), parseInt(minutes), 0, 0);
            
            // Если время уже прошло сегодня, устанавливаем на завтра
            if (targetTime <= now) {
                targetTime.setDate(targetTime.getDate() + 1);
            }
            
            const timeUntilSend = targetTime.getTime() - now.getTime();
            
            if (autoSendEnabled) {
                disableAutoSend();
            }
            
            // Устанавливаем разовую отправку
            autoSendEnabled = true;
            autoSendToggle.checked = true;
            nextSendTime = targetTime;
            
            // Сохраняем состояние
            localStorage.setItem('autoSendEnabled', 'true');
            localStorage.setItem('customSendTime', targetTime.getTime().toString());
            
            setTimeout(() => {
                sendBatch();
                // После отправки НЕ отключаем автоотправку, а продолжаем с обычным интервалом
                if (autoSendEnabled) {
                    enableAutoSend(); // Включаем обычную автоотправку
                }
            }, timeUntilSend);
            
            const timeStr = targetTime.toLocaleTimeString('ru-RU', {hour: '2-digit', minute: '2-digit'});
            const dateStr = targetTime.toLocaleDateString('ru-RU') === now.toLocaleDateString('ru-RU') ? 'сегодня' : 'завтра';
            showNotification(`Отправка запланирована на ${timeStr} ${dateStr}`, 'success');
            updateStatus();
        }
        
        // Включение/выключение автоотправки
        autoSendToggle.addEventListener('change', function() {
            if (this.checked) {
                enableAutoSend();
            } else {
                disableAutoSend();
            }
        });
        
        // Изменение интервала
        timeInterval.addEventListener('change', function() {
            if (autoSendEnabled) {
                // Перезапускаем с новым интервалом
                disableAutoSend();
                enableAutoSend();
            }
        });
        
        // Установка кастомного времени
        setCustomTimeBtn.addEventListener('click', setCustomSendTime);
        
        // Восстановление состояния при загрузке страницы
        function restoreAutoSendState() {
            const savedEnabled = localStorage.getItem('autoSendEnabled');
            const savedStartTime = localStorage.getItem('autoSendStartTime');
            const savedInterval = localStorage.getItem('autoSendInterval');
            const customSendTime = localStorage.getItem('customSendTime');
            
            if (savedEnabled === 'true') {
                // Восстановление кастомного времени
                if (customSendTime) {
                    const targetTime = new Date(parseInt(customSendTime));
                    const currentTime = Date.now();
                    
                    if (targetTime > currentTime) {
                        autoSendEnabled = true;
                        autoSendToggle.checked = true;
                        nextSendTime = targetTime;
                        
                                                 setTimeout(() => {
                             sendBatch();
                             // После отправки включаем обычную автоотправку
                             if (autoSendEnabled) {
                                 enableAutoSend();
                             }
                         }, targetTime.getTime() - currentTime);
                        
                        console.log('Восстановлена запланированная отправка');
                        return;
                    } else {
                        localStorage.removeItem('customSendTime');
                    }
                }
                
                // Восстановление интервальной отправки
                if (savedStartTime && savedInterval) {
                    const startTime = parseInt(savedStartTime);
                    const currentTime = Date.now();
                    const intervalMs = parseInt(savedInterval);
                    const timeSinceStart = currentTime - startTime;
                    
                    // Восстанавливаем выбранный интервал в селекторе
                    timeInterval.value = intervalMs.toString();
                    
                    if (timeSinceStart < intervalMs) {
                        autoSendEnabled = true;
                        autoSendToggle.checked = true;
                        
                        const timeLeft = intervalMs - timeSinceStart;
                        nextSendTime = new Date(currentTime + timeLeft);
                        
                        setTimeout(() => {
                            sendBatch();
                            nextSendTime = new Date(Date.now() + intervalMs);
                            localStorage.setItem('autoSendStartTime', Date.now().toString());
                            
                            autoSendInterval = setInterval(() => {
                                sendBatch();
                                nextSendTime = new Date(Date.now() + intervalMs);
                                localStorage.setItem('autoSendStartTime', Date.now().toString());
                            }, intervalMs);
                        }, timeLeft);
                        
                        console.log(`Автоотправка восстановлена. Следующая отправка через ${Math.floor(timeLeft/60000)} минут`);
                    } else {
                        enableAutoSend(intervalMs);
                    }
                }
            }
        }
        
        // Восстанавливаем состояние при загрузке
        restoreAutoSendState();
        
        // Обновляем статус каждую секунду
        setInterval(updateStatus, 1000);
        updateStatus();
        
        // Автообновление статистики каждые 30 секунд
        setInterval(() => {
            if (!autoSendEnabled) {
                // Если автоотправка выключена, обновляем статистику
                updateStatsWithoutReload();
            }
        }, 30000);
        
        // Функция очистки нерабочих email адресов
        async function cleanupInvalidEmails() {
            if (!confirm('Удалить все нерабочие email адреса? Это действие нельзя отменить.')) {
                return;
            }
            
            try {
                const response = await fetch('cleanup.php');
                const result = await response.text();
                
                if (result.includes('Удалено')) {
                    showNotification('Очистка завершена! ' + result, 'success');
                    // Обновляем статистику через 2 секунды
                    setTimeout(() => {
                        updateStatsWithoutReload();
                    }, 2000);
                } else {
                    showNotification('Ошибка очистки: ' + result, 'error');
                }
            } catch (error) {
                console.error('Ошибка очистки:', error);
                showNotification('Ошибка очистки: ' + error.message, 'error');
            }
        }
        
        // Функция изменения количества записей на странице
        function changePerPage() {
            const perPage = document.getElementById('perPage').value;
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('per_page', perPage);
            currentUrl.searchParams.delete('page'); // Сбрасываем страницу при изменении количества
            window.location.href = currentUrl.toString();
        }
        
        // Устанавливаем текущее значение в селекторе
        const urlParams = new URLSearchParams(window.location.search);
        const currentPerPage = urlParams.get('per_page') || '50';
        document.getElementById('perPage').value = currentPerPage;
    </script>
</body>
</html>
