<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
// Только администраторы могут добавлять врачей
$auth->checkAccess('Администратор');

$db = new Database();
$error = '';
$success = '';

// Обработка формы добавления врача
if ($_POST && isset($_POST['create_doctor'])) {
    try {
        $login = trim($_POST['login']);
        $password = $_POST['password'];
        $last_name = trim($_POST['last_name']);
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name'] ?? '');
        $specialization = trim($_POST['specialization']);
        $license_number = trim($_POST['license_number']);
        $experience_years = intval($_POST['experience_years']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email'] ?? '');
        
        // Валидация
        if (empty($login) || empty($password) || empty($last_name) || empty($first_name) || 
            empty($specialization) || empty($license_number) || empty($phone)) {
            throw new Exception("Заполните все обязательные поля");
        }
        
        if (strlen($password) < 6) {
            throw new Exception("Пароль должен содержать минимум 6 символов");
        }
        
        // Проверяем, не занят ли логин
        $check_user = $db->query("SELECT id FROM Users WHERE login = ?", [$login]);
        if ($check_user->num_rows > 0) {
            throw new Exception("Пользователь с таким логином уже существует");
        }
        
        // Проверяем уникальность лицензии
        $check_license = $db->query("SELECT id FROM Doctors WHERE license_number = ?", [$license_number]);
        if ($check_license->num_rows > 0) {
            throw new Exception("Врач с таким номером лицензии уже зарегистрирован");
        }
        
        // Начинаем транзакцию
        $db->getConnection()->begin_transaction();
        
        try {
            // Создаем пользователя
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $db->query(
                "INSERT INTO Users (login, password_hash) VALUES (?, ?)",
                [$login, $password_hash]
            );
            
            $user_id = $db->getConnection()->insert_id;
            
            // Создаем врача
            $db->query(
                "INSERT INTO Doctors (user_id, last_name, first_name, middle_name, specialization, license_number, experience_years, phone, email, is_active) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                [$user_id, $last_name, $first_name, $middle_name, $specialization, $license_number, $experience_years, $phone, $email]
            );
            
            // Подтверждаем транзакцию
            $db->getConnection()->commit();
            
            $success = "Врач успешно добавлен!";
            
        } catch (Exception $e) {
            // Откатываем транзакцию в случае ошибки
            $db->getConnection()->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $error = "Ошибка при добавлении врача: " . $e->getMessage();
    }
}

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить врача - МЕДИС</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">МС</div>
                <div class="logo-text">МЕДИС - Медицинская Система</div>
            </div>
            <div class="user-info">
                <span><strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                <span class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                <a href="doctors.php" class="btn btn-secondary btn-sm">Назад к врачам</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>👨‍⚕️ Добавить врача</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <div style="text-align: center; margin: 20px 0;">
                <a href="doctors.php" class="btn btn-primary">Вернуться к списку врачей</a>
                <a href="doctors_create.php" class="btn btn-success">Добавить еще одного врача</a>
            </div>
        <?php else: ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <h3>🔐 Учетные данные</h3>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Логин *</label>
                                <input type="text" class="form-control" name="login" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Пароль *</label>
                                <input type="password" class="form-control" name="password" required minlength="6">
                            </div>
                        </div>
                    </div>
                    
                    <h3>👤 Личные данные</h3>
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Фамилия *</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Имя *</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Отчество</label>
                                <input type="text" class="form-control" name="middle_name">
                            </div>
                        </div>
                    </div>
                    
                    <h3>🎓 Профессиональные данные</h3>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Специализация *</label>
                                <input type="text" class="form-control" name="specialization" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Номер лицензии *</label>
                                <input type="text" class="form-control" name="license_number" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Опыт работы (лет) *</label>
                                <input type="number" class="form-control" name="experience_years" min="0" max="50" required>
                            </div>
                        </div>
                    </div>
                    
                    <h3>📞 Контактные данные</h3>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Телефон *</label>
                                <input type="tel" class="form-control" name="phone" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="create_doctor" class="btn btn-primary">Добавить врача</button>
                        <a href="doctors.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.scrollTo(0, 0);
        });
    </script>
</body>
</html>