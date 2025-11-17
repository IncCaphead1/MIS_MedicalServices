<?php
// Включение отображения всех ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Диагностика системы МЕДИС</h1>";

// Проверка базовых настроек
echo "<h2>Информация о системе:</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";

// Проверка включенных файлов
echo "<h2>Проверка файлов:</h2>";

$files_to_check = [
    'includes/config.php',
    'includes/database.php',
    'includes/auth.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✓ $file - найден<br>";
    } else {
        echo "✗ $file - НЕ НАЙДЕН!<br>";
    }
}

// Проверка подключения к БД
echo "<h2>Проверка базы данных:</h2>";
try {
    require_once 'includes/config.php';
    $db = new database();
    echo "✓ Подключение к БД успешно<br>";
    
    // Проверка таблиц
    $result = $db->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    echo "Таблицы: " . implode(', ', $tables) . "<br>";
    
    $db->close();
} catch (Exception $e) {
    echo "✗ Ошибка БД: " . $e->getMessage() . "<br>";
}

// Проверка сессии
echo "<h2>Проверка сессии:</h2>";
echo "Статус сессии: " . session_status() . "<br>";
if (isset($_SESSION)) {
    echo "Переменные сессии: <pre>";
    print_r($_SESSION);
    echo "</pre>";
}
?>