<?php
// Включение отладки
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Настройки базы данных
define('DB_HOST', '134.90.167.42:10306');
define('DB_USER', 'Valiev');
define('DB_PASS', 'RG_z9a');
define('DB_NAME', 'project_Valiev');

// Настройки приложения
define('APP_NAME', 'МЕДИС - Медицинская Информационная Система');
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOCKOUT_TIME', 900);
define('PASSWORD_MAX_AGE', 15552000);

// Статический IP адрес сервера
define('SERVER_IP', '134.90.167.42'); // Ваш реальный IP

// Цветовая схема
define('BG_COLOR', '#F5F5F5');
define('TEXT_COLOR', '#212529');
define('ACCENT_COLOR', '#007BFF');
define('HIGHLIGHT_COLOR', '#FFFFFF');

// Запуск сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>