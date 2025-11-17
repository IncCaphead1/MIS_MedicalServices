<?php
require_once 'includes/config.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Обработка формы входа
if ($_POST && isset($_POST['login'])) {
    try {
        require_once 'includes/auth.php';
        $auth = new Auth();
        
        if ($auth->login($_POST['login'], $_POST['password'])) {
            header('Location: dashboard.php');
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему - МЕДИС</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-form .form-group {
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-logo">
                <div class="logo-placeholder">
                    МС
                </div>
                <h2>МЕДИС</h2>
                <p>Медицинская информационная система</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label class="form-label" for="login">Логин</label>
                    <input type="text" class="form-control" id="login" name="login" 
                           value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>" 
                           required autofocus placeholder="Введите ваш логин">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Пароль</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Введите ваш пароль">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                    Войти в систему (Enter)
                </button>
            </form>

            <!-- Ссылка на регистрацию -->
            <div style="text-align: center; margin-top: 20px;">
                <p>Нет учетной записи? <a href="register.php">Зарегистрируйтесь</a></p>
            </div>

            <div class="keyboard-hint" style="text-align: center; margin-top: 10px; font-size: 12px; color: #6c757d;">
                💡 Нажмите Enter для быстрого входа
            </div>
            
            <div class="server-info-login">
                <strong>Сервер:</strong> <span class="ip-display"><?php echo SERVER_IP; ?></span>
            </div>
            
            <?php if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS): ?>
                <div class="login-warning" style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 10px; margin: 15px 0; text-align: center;">
                    <strong>Внимание!</strong> Аккаунт временно заблокирован из-за множества неудачных попыток входа.
                </div>
            <?php endif; ?>
            
            <div class="login-footer" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666;">
                <p><strong>Тестовые доступы:</strong></p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <li><strong>Пациент:</strong> ivanov_p / 3213asw</li>
                    <li><strong>Врач:</strong> dr_ivanova / gsgshfu45</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                if (document.getElementById('login').value && document.getElementById('password').value) {
                    document.getElementById('loginBtn').click();
                }
            }
        });

        document.getElementById('login').focus();
    </script>
</body>
</html>