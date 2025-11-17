<?php
require_once 'includes/config.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Требуется регистрация - МЕДИС</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <div class="logo-placeholder">🔒</div>
                <h2>Требуется регистрация</h2>
                <p>Для выполнения этого действия необходимо быть зарегистрированным пользователем</p>
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                <p>Эта функция доступна только для зарегистрированных пациентов, врачей и администраторов.</p>
                <p>Пожалуйста, войдите в систему или зарегистрируйтесь.</p>
            </div>
            
            <div style="text-align: center;">
                <a href="login.php" class="btn btn-primary">Войти в систему</a>
                <a href="register.php" class="btn btn-success">Зарегистрироваться</a>
                <a href="services.php" class="btn btn-secondary">Вернуться к услугам</a>
            </div>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666;">
                <p><strong>Текущий статус:</strong> Гость (ограниченный доступ)</p>
            </div>
        </div>
    </div>
</body>
</html>