<?php
require_once 'emailer.php';



$emailer = new EmailSender();

echo "Начинаем очистку базы данных от нерабочих email адресов...\n";

$statsBefore = $emailer->getStats();
echo "Статистика до очистки:\n";
echo "- Всего email: {$statsBefore['total_emails']}\n";
echo "- Отправлено: {$statsBefore['sent_emails']}\n";
echo "- Ошибок: {$statsBefore['failed_emails']}\n";
echo "- В очереди: {$statsBefore['pending_emails']}\n\n";

$result = $emailer->cleanupInvalidEmails();

if ($result['success']) {
    echo "✓ {$result['message']}\n\n";
    
    $statsAfter = $emailer->getStats();
    echo "Статистика после очистки:\n";
    echo "- Всего email: {$statsAfter['total_emails']}\n";
    echo "- Отправлено: {$statsAfter['sent_emails']}\n";
    echo "- Ошибок: {$statsAfter['failed_emails']}\n";
    echo "- В очереди: {$statsAfter['pending_emails']}\n\n";
    
    $savedEmails = $statsBefore['total_emails'] - $statsAfter['total_emails'];
    echo "Освобождено места в базе данных: $savedEmails записей\n";
} else {
    echo "✗ Ошибка при очистке базы данных\n";
}

if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    echo json_encode($result);
}
?>
