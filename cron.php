<?php


require_once __DIR__ . '/emailer.php';

file_put_contents(
    __DIR__ . '/logs/cron_log.txt', 
    date('Y-m-d H:i:s') . " - Cron запущен\n", 
    FILE_APPEND | LOCK_EX
);

try {
    $emailer = new EmailSender();
    $result = $emailer->sendBatch();
    
    $logMessage = sprintf(
        "%s - Cron выполнен: отправлено %d, ошибок %d из %d\n",
        date('Y-m-d H:i:s'),
        $result['sent'],
        $result['failed'], 
        $result['total']
    );
    
    file_put_contents(
        __DIR__ . '/logs/cron_log.txt', 
        $logMessage, 
        FILE_APPEND | LOCK_EX
    );
    
    if (isset($result['message'])) {
        file_put_contents(
            __DIR__ . '/logs/cron_log.txt', 
            date('Y-m-d H:i:s') . " - " . $result['message'] . "\n", 
            FILE_APPEND | LOCK_EX
        );
    }
    
} catch (Exception $e) {
    $errorMessage = date('Y-m-d H:i:s') . " - Ошибка cron: " . $e->getMessage() . "\n";
    
    file_put_contents(
        __DIR__ . '/logs/cron_log.txt', 
        $errorMessage, 
        FILE_APPEND | LOCK_EX
    );
    
    file_put_contents(
        __DIR__ . '/logs/error_log.txt', 
        $errorMessage, 
        FILE_APPEND | LOCK_EX
    );
}
?>
