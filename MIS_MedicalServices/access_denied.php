<?php
require_once 'includes/config.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Доступ запрещен - МЕДИС</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <div class="logo-placeholder">🚫</div>
                <h2>Доступ запрещен</h2>
                <p>У вас недостаточно прав для просмотра этой страницы</p>
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                <p>Эта функция доступна только для врачей и администраторов.</p>
                <p>Если вы считаете, что это ошибка, обратитесь к администратору системы.</p>
            </div>
            
            <div style="text-align: center;">
                <a href="dashboard.php" class="btn btn-primary">Вернуться на главную</a>
                <a href="logout.php" class="btn btn-secondary">Выйти</a>
            </div>
            
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666;">
                <p><strong>Текущий пользователь:</strong> 
                    <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Не авторизован'; ?>
                    (<?php echo isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'Нет роли'; ?>)
                </p>
            </div>
        </div>
    </div>
</body>
</html>