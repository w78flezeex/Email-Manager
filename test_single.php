<?php
require_once 'config.php';
require_once 'emailer.php';

$message = '';
$status = '';

if ($_POST) {
    $testEmail = $_POST['test_email'] ?? '';
    
    if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $message = 'Введите корректный email адрес';
        $status = 'error';
    } else {
        $emailer = new EmailSender();
        
        $testData = [
            'email' => $testEmail,
            'username' => 'Test User'
        ];
        
        $result = $emailer->sendSingleEmail($testData);
        
        if ($result['success']) {
            $message = "✅ Тестовое письмо успешно отправлено на $testEmail";
            $status = 'success';
        } else {
            $message = "❌ Ошибка отправки: " . $result['error'];
            $status = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Тестовая отправка - Svortex Email Manager</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .header p {
            color: #666;
            margin: 0;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
            width: 100%;
            margin-bottom: 15px;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn.secondary {
            background: #6c757d;
        }
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .preview-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .preview-info h3 {
            margin-top: 0;
            color: #495057;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Тестовая отправка</h1>
            <p>Отправьте тестовое письмо для проверки</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $status ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="preview-info">
            <h3>📋 Информация о письме:</h3>
            <div class="info-item">
                <span class="info-label">Тема:</span>
                <span><?= EMAIL_SUBJECT ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">От кого:</span>
                <span><?= SENDER_EMAIL ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Имя отправителя:</span>
                <span><?= SENDER_NAME ?></span>
            </div>
        </div>
        
        <form method="post">
            <div class="form-group">
                <label class="form-label" for="test_email">📧 Email для тестовой отправки:</label>
                <input 
                    type="email" 
                    id="test_email" 
                    name="test_email" 
                    class="form-input" 
                    placeholder="example@gmail.com"
                    value="<?= htmlspecialchars($_POST['test_email'] ?? SENDER_EMAIL) ?>"
                    required
                >
            </div>
            
            <button type="submit" class="btn">📤 Отправить тестовое письмо</button>
        </form>
        
        <a href="preview.php" class="btn secondary">👁️ Предварительный просмотр</a>
        <a href="index.php" class="btn secondary">🏠 Вернуться к панели</a>
    </div>
</body>
</html>
