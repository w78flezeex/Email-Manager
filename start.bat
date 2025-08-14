@echo off
echo ================================================
echo  ApexNodes Email Manager - Быстрый запуск
echo ================================================
echo.

echo 1. Проверяем наличие файлов...
if not exist "config.php" (
    echo ОШИБКА: Файл config.php не найден!
    pause
    exit
)

if not exist "ApexNodes-DataBase-By-Stacey.sql" (
    echo ОШИБКА: Файл ApexNodes-DataBase-By-Stacey.sql не найден!
    pause
    exit
)

echo ✓ Все файлы на месте

echo.
echo 2. Создаем папку для логов...
if not exist "logs" mkdir logs
echo ✓ Папка logs создана

echo.
echo 3. Запускаем локальный сервер PHP...
echo.
echo Откройте браузер и перейдите по адресу:
echo.
echo     http://localhost:8000
echo.
echo Для остановки нажмите Ctrl+C
echo.
echo ================================================

php -S localhost:8000
