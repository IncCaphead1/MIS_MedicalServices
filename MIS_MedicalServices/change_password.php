<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();

// Проверка необходимости смены пароля
if (!isset($_SESSION['require_password_change']) && !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$message = '';

if ($_POST) {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword !== $confirmPassword) {
        $message = "Пароли не совпадают";
    } elseif (strlen($newPassword) < 6) {
        $message = "Пароль должен содержать минимум 6 символов";
    } else {
        $userId = $_SESSION['temp_user_id'] ?? $_SESSION['user_id'];
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        try {
            $db->query(
                "UPDATE users SET password_hash = ?, password_changed_at = NOW() WHERE id = ?",
                [$passwordHash, $userId]
            );
            
            if (isset($_SESSION['require_password_change'])) {
                unset($_SESSION['require_password_change']);
                unset($_SESSION['temp_user_id']);
                $_SESSION['user_id'] = $userId;
            }
            
            $message = "Пароль успешно изменен";
            header('Location: dashboard.php');
            exit;
            
        } catch (Exception $e) {
            $message = "Ошибка при изменении пароля: " . $e->getMessage();
        }
    }
}

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Смена пароля - МЕДИС</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <img src="images/logo_mis.png" alt="Логотип МЕДИС">
                <h2>Смена пароля</h2>
                <p>Требуется обновление пароля</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label" for="new_password">Новый пароль</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Подтверждение пароля</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    Сменить пароль
                </button>
            </form>
        </div>
    </div>
</body>
</html>