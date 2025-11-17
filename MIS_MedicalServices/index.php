<?php
require_once 'includes/config.php';

echo "<h1>Тестирование системы МЕДИС</h1>";

try {
    // Проверка подключения к БД
    $db = new database();
    echo "<p style='color: green;'>✓ Подключение к БД успешно</p>";
    
    // Проверка таблиц
    $result = $db->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    echo "<p>Найдены таблицы: " . implode(', ', $tables) . "</p>";
    
    $db->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Ошибка БД: " . $e->getMessage() . "</p>";
}

// Проверка сессии
echo "<p>Статус сессии: " . session_status() . " (2 = PHP_SESSION_ACTIVE)</p>";

// Ссылки для тестирования
echo "
<h2>Ссылки для тестирования:</h2>
<ul>
    <li><a href='login.php'>Страница входа</a></li>
    <li><a href='debug.php'>Расширенная отладка</a></li>
</ul>
";
?>