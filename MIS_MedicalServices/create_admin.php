<?php
require_once 'includes/config.php';

// Начинаем сессию если еще не начата
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Отключаем буферизацию вывода для немедленного отображения
ob_implicit_flush(true);
ob_end_flush();

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание администратора - МЕДИС</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .result {
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            font-family: monospace;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card" style="max-width: 800px;">
            <div class="login-logo">
                <div class="logo-placeholder">👑</div>
                <h2>Создание администратора</h2>
                <p>МЕДИС - Медицинская информационная система</p>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Создание тестового администратора</h3>
                </div>
                <div class="card-body">
                    <?php
                    try {
                        require_once 'includes/auth.php';
                        $auth = new Auth();

                        echo "<div class='info result'>";
                        echo "🔍 Проверяем существование администратора...<br>";
                        
                        // Проверяем существование администратора
                        if (!$auth->testAdministratorExists()) {
                            echo "❌ Администратор не найден<br>";
                            echo "🔄 Создаем тестового администратора...<br>";
                            
                            // Создаем тестового администратора
                            if ($auth->createTestAdministrator()) {
                                echo "<div class='success result'>";
                                echo "✅ <strong>Тестовый администратор успешно создан!</strong><br><br>";
                                echo "📧 <strong>Логин:</strong> admin<br>";
                                echo "🔑 <strong>Пароль:</strong> admin123<br><br>";
                                echo "⚠️ <strong>Внимание:</strong> Не забудьте сменить пароль после первого входа!";
                                echo "</div>";
                            } else {
                                throw new Exception("Не удалось создать администратора");
                            }
                        } else {
                            echo "<div class='success result'>";
                            echo "✅ <strong>Тестовый администратор уже существует!</strong><br><br>";
                            echo "📧 <strong>Логин:</strong> admin<br>";
                            echo "🔑 <strong>Пароль:</strong> admin123<br><br>";
                            echo "Вы можете использовать эти данные для входа в систему.";
                            echo "</div>";
                        }
                        
                        echo "</div>";

                        // Дополнительная информация о базе данных
                        echo "<div class='info result'>";
                        echo "<strong>Информация о базе данных:</strong><br>";
                        
                        $db = new Database();
                        
                        // Проверяем таблицы
                        $tables = ['Users', 'Administrators', 'Doctors', 'Patients'];
                        foreach ($tables as $table) {
                            $result = $db->query("SHOW TABLES LIKE '$table'");
                            if ($result->num_rows > 0) {
                                echo "✅ Таблица <strong>$table</strong> существует<br>";
                            } else {
                                echo "❌ Таблица <strong>$table</strong> не найдена<br>";
                            }
                        }
                        
                        // Проверяем количество записей
                        $users_count = $db->query("SELECT COUNT(*) as count FROM Users")->fetch_assoc()['count'];
                        $admins_count = $db->query("SELECT COUNT(*) as count FROM Administrators")->fetch_assoc()['count'];
                        $doctors_count = $db->query("SELECT COUNT(*) as count FROM Doctors")->fetch_assoc()['count'];
                        $patients_count = $db->query("SELECT COUNT(*) as count FROM Patients")->fetch_assoc()['count'];
                        
                        echo "<br><strong>Статистика:</strong><br>";
                        echo "👥 Пользователей: $users_count<br>";
                        echo "👑 Администраторов: $admins_count<br>";
                        echo "👨‍⚕️ Врачей: $doctors_count<br>";
                        echo "👤 Пациентов: $patients_count<br>";
                        
                        $db->close();
                        echo "</div>";

                    } catch (Exception $e) {
                        echo "<div class='error result'>";
                        echo "❌ <strong>Ошибка:</strong> " . $e->getMessage() . "<br><br>";
                        echo "Проверьте:<br>";
                        echo "1. Существует ли таблица Users<br>";
                        echo "2. Существует ли таблица Administrators<br>";
                        echo "3. Корректны ли настройки подключения к БД в includes/config.php";
                        echo "</div>";
                        
                        // Дополнительная отладочная информация
                        echo "<div class='info result'>";
                        echo "<strong>Отладочная информация:</strong><br>";
                        echo "База данных: project_Valiev<br>";
                        echo "Статус сессии: " . session_status() . "<br>";
                        echo "Ошибка PHP: " . error_get_last()['message'] ?? 'Нет ошибок';
                        echo "</div>";
                    }
                    ?>

                    <div style="text-align: center; margin-top: 20px;">
                        <a href="login.php" class="btn btn-primary">Перейти к входу в систему</a>
                        <a href="index.php" class="btn btn-secondary">На главную</a>
                        <a href="register.php" class="btn btn-success">Регистрация нового пользователя</a>
                    </div>

                    <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <strong>💡 Подсказка:</strong> После создания администратора вы можете:
                        <ul style="margin: 10px 0; padding-left: 20px;">
                            <li>Войти с логином <code>admin</code> и паролем <code>admin123</code></li>
                            <li>Создать дополнительных администраторов через панель управления</li>
                            <li>Создать врачей и пациентов через соответствующие разделы</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>