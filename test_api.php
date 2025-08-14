<?php
require_once 'config.php';



echo "🧪 Тестирование API smtp.bz\n";
echo "===========================\n\n";

echo "🔑 API ключ: " . SMTP_API_KEY . "\n";
echo "🌐 API URL: " . SMTP_API_URL . "\n";
echo "📧 Отправитель: " . SENDER_EMAIL . "\n\n";

// Тестовое письмо
$testEmail = 'admin@svortex.ru'; 

$curl = curl_init();

$postData = [
    'subject' => 'Тест API - ApexNodes Email Manager',
    'name' => SENDER_NAME,
    'html' => '<html><body><h2>🎉 Тест успешен!</h2><p>API smtp.bz работает корректно.</p><p>Система готова к массовой рассылке.</p></body></html>',
    'from' => SENDER_EMAIL,
    'to' => $testEmail,
    'to_name' => 'Test User'
];

curl_setopt_array($curl, [
    CURLOPT_URL => SMTP_API_URL,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_HTTPHEADER => [
        "authorization: " . SMTP_API_KEY,
        "content-type: application/x-www-form-urlencoded"
    ],
    CURLOPT_POSTFIELDS => http_build_query($postData)
]);

echo "📤 Отправляем тестовое письмо на $testEmail...\n";

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$error = curl_error($curl);
curl_close($curl);

echo "\n📋 РЕЗУЛЬТАТ:\n";
echo "=============\n";

if ($error) {
    echo "❌ cURL ошибка: " . $error . "\n";
} else {
    echo "📊 HTTP код: $httpCode\n";
    echo "📄 Ответ сервера: " . $response . "\n\n";
    
    if ($httpCode >= 200 && $httpCode < 300) {
        echo "✅ УСПЕХ! API работает корректно.\n";
        echo "📧 Проверьте почту $testEmail\n\n";
        
        echo "🚀 ГОТОВО К ЗАПУСКУ:\n";
        echo "===================\n";
        echo "1. Запустите: php -S localhost:8000\n";
        echo "2. Откройте: http://localhost:8000\n";
        echo "3. Нажмите 'Импортировать Email'\n";
        echo "4. Начните массовую рассылку!\n";
    } else {
        echo "❌ ОШИБКА API!\n";
        echo "Проверьте:\n";
        echo "- Правильность API ключа\n";
        echo "- Баланс аккаунта smtp.bz\n";
        echo "- Настройки домена отправителя\n";
    }
}
?>
