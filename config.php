<?php
/**
 * Конфигурация для системы email рассылки ApexNodes
 */

// Настройки API smtp.bz
define('SMTP_API_KEY', 'OsefDYwJGvvQpAsxu0Si1Q6KW0eyjUEYdJ5H');
define('SMTP_API_URL', 'https://api.smtp.bz/v1/smtp/send');

// Настройки базы данных для отслеживания отправок
define('DB_HOST', 'localhost');
define('DB_NAME', 'email_tracker');
define('DB_USER', 'root');
define('DB_PASS', ''); // В XAMPP обычно пароль пустой

// Настройки рассылки
define('EMAILS_PER_HOUR', 50);
define('SENDER_EMAIL', 'mailing@svortex.ru');
define('SENDER_NAME', 'Svortex Malling');

// Путь к исходной базе данных ApexNodes
define('SOURCE_DB_FILE', __DIR__ . '/ApexNodes-DataBase-By-Stacey.sql');

// Настройки письма
define('EMAIL_SUBJECT', 'Новый хостинг для Minecraft и кодинга — быстрее, дешевле, удобнее! 🖥️⛏️');
define('EMAIL_HTML_TEMPLATE', '
<html>
<head>
    <meta charset="utf-8">
    <title>Svortex</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px; }
        .content { background: #f8f9fa; padding: 25px; border-radius: 10px; margin-bottom: 20px; }
        .highlight { background: #e3f2fd; padding: 15px; border-left: 4px solid #2196f3; margin: 15px 0; }
        .button { display: inline-block; background: linear-gradient(135deg, #ff6b6b, #ee5a24); color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 10px 0; }
        .footer { text-align: center; color: #666; font-size: 14px; }
        ul { padding-left: 20px; }
        li { margin: 8px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖥️⛏️ Svortex Hosting</h1>
            <p>Новое поколение хостинга для Minecraft и разработки</p>
        </div>
        
        <div class="content">
            <p><strong>Привет! 👋</strong></p>
            
            <p>Если твой Minecraft-сервер тормозит или код постоянно упирается в лимиты старого хостинга — пора что-то менять. Мы запустили новые тарифы специально для Minecraft и разработки:</p>
            
            <div class="highlight">
                <h3>🎮 Minecraft-тарифы:</h3>
                <ul>
                    <li>От 1 GB до 16 GB RAM</li>
                    <li>Мгновенное создание сервера</li>
                    <li>Поддержка Bukkit, Spigot, Paper и модов</li>
                    <li>Бесплатный перенос с любого хостинга</li>
                    <li>Скидка 25% на первый месяц</li>
                </ul>
            </div>
            
            <div class="highlight">
                <h3>💻 Для кодеров:</h3>
                <ul>
                    <li>панель Java, Python, Node.js</li>
                    <li>Удобная панель управления</li>
                    <li>SSD-диски и быстрые процессоры</li>
                    <li>24/7 техподдержка</li>
                    <li>Бесплатный перенос с любого хостинга</li>
                    <li>Скидка 50% на первый месяц</li>
                </ul>
            </div>
            
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 20px 0; text-align: center;">
                <h3 style="color: #856404;">🔥 Специальное предложение!</h3>
                <p style="color: #856404; margin: 0;"><strong>Скидка до 50% на первый месяц</strong> для всех, кто переедет к нам!</p>
            </div>
            
            <p style="text-align: center;">
                <a href="https://client.svortex.ru" class="button">🚀 Выбрать тариф</a>
            </p>
            
            <p>Сделай свой сервер быстрым и стабильным!</p>
        </div>
        
        <div class="footer">
            <p>Есть вопросы? Обратитесь в нашу службу поддержки:<br>
            <a href="https://client.svortex.ru/tickets">https://client.svortex.ru/tickets</a></p>
            
            <p>С уважением,<br><strong>Команда Svortex</strong></p>
        </div>
    </div>
</body>
</html>
');

// Лимиты и таймауты
define('CURL_TIMEOUT', 30);
define('MAX_RETRIES', 3);
define('RETRY_DELAY', 5); // секунд

// Логирование
define('LOG_FILE', __DIR__ . '/logs/email_log.txt');
define('ERROR_LOG_FILE', __DIR__ . '/logs/error_log.txt');

// Создаем директорию для логов
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}
?>
