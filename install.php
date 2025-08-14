<?php
/**
 * Скрипт установки и настройки системы
 */

echo "🚀 Установка ApexNodes Email Manager\n";
echo "====================================\n\n";

// Проверяем PHP версию
if (version_compare(PHP_VERSION, '7.4.0') < 0) {
    die("❌ Требуется PHP 7.4 или выше. Текущая версия: " . PHP_VERSION . "\n");
}
echo "✅ PHP версия: " . PHP_VERSION . "\n";

// Проверяем расширения
$required_extensions = ['pdo', 'pdo_mysql', 'curl'];
foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        die("❌ Не установлено расширение: $ext\n");
    }
    echo "✅ Расширение $ext установлено\n";
}

// Проверяем наличие файлов
$required_files = [
    'config.php',
    'database.php', 
    'emailer.php',
    'index.php',
    'ApexNodes-DataBase-By-Stacey.sql'
];

foreach ($required_files as $file) {
    if (!file_exists($file)) {
        die("❌ Не найден файл: $file\n");
    }
    echo "✅ Файл $file найден\n";
}

// Создаем папки
$directories = ['logs'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "✅ Создана папка: $dir\n";
    } else {
        echo "✅ Папка $dir уже существует\n";
    }
}

echo "\n📋 НАСТРОЙКА:\n";
echo "=============\n";

// Проверяем настройки конфигурации
require_once 'config.php';

echo "📧 Email отправителя: " . SENDER_EMAIL . "\n";
echo "👤 Имя отправителя: " . SENDER_NAME . "\n";
echo "🔑 API ключ: " . (SMTP_API_KEY === 'YOUR_API_KEY_HERE' ? '❌ НЕ НАСТРОЕН!' : '✅ Настроен') . "\n";
echo "📊 Писем в час: " . EMAILS_PER_HOUR . "\n";

echo "\n⚠️  ВАЖНО!\n";
echo "=========\n";

if (SMTP_API_KEY === 'YOUR_API_KEY_HERE') {
    echo "❌ ОБЯЗАТЕЛЬНО замените API ключ в config.php!\n";
    echo "   Получите ключ на: https://smtp.bz/\n\n";
}

echo "📋 СЛЕДУЮЩИЕ ШАГИ:\n";
echo "==================\n";
echo "1. Если не настроен API ключ - получите его на smtp.bz\n";
echo "2. Создайте базу данных MySQL:\n";
echo "   mysql -u root -p < setup.sql\n";
echo "3. Запустите веб-сервер:\n";
echo "   php -S localhost:8000\n";
echo "4. Откройте браузер: http://localhost:8000\n";
echo "5. Нажмите 'Импортировать Email' в веб-интерфейсе\n";
echo "6. Настройте cron для автоотправки:\n";
echo "   0 * * * * php " . __DIR__ . "/cron.php\n\n";

echo "✅ Установка завершена!\n";
echo "🌐 Запустите: php -S localhost:8000\n";
?>
