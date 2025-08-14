<?php
require_once 'config.php';

/**
 * Предварительный просмотр письма
 */
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Предварительный просмотр письма - Svortex</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .preview-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .preview-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .preview-info {
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            min-width: 120px;
            color: #495057;
        }
        .email-content {
            padding: 20px;
        }
        .action-buttons {
            padding: 20px;
            background: #f8f9fa;
            text-align: center;
        }
        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin: 0 10px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn.success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        .btn.warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
        }
    </style>
</head>
<body>
    <div class="preview-container">
        <div class="preview-header">
            <h1>📧 Предварительный просмотр письма</h1>
            <p>Проверьте, как будет выглядеть ваша рассылка</p>
        </div>
        
        <div class="preview-info">
            <div class="info-row">
                <span class="info-label">От кого:</span>
                <span><?= SENDER_NAME ?> &lt;<?= SENDER_EMAIL ?>&gt;</span>
            </div>
            <div class="info-row">
                <span class="info-label">Тема:</span>
                <span><?= EMAIL_SUBJECT ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Получателей:</span>
                <span>73,504 пользователей</span>
            </div>
            <div class="info-row">
                <span class="info-label">Скорость:</span>
                <span><?= EMAILS_PER_HOUR ?> писем в час</span>
            </div>
        </div>
        
        <div class="email-content">
            <?= EMAIL_HTML_TEMPLATE ?>
        </div>
        
        <div class="action-buttons">
            <a href="test_single.php" class="btn success">📤 Отправить тестовое письмо</a>
            <a href="index.php" class="btn">🏠 Вернуться к панели</a>
            <a href="mailto:<?= SENDER_EMAIL ?>?subject=<?= urlencode(EMAIL_SUBJECT) ?>&body=<?= urlencode(strip_tags(EMAIL_HTML_TEMPLATE)) ?>" class="btn warning">✉️ Открыть в почте</a>
        </div>
    </div>
</body>
</html>
