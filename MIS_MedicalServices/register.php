<?php
require_once 'includes/config.php';

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Обработка формы регистрации
if ($_POST && isset($_POST['register'])) {
    try {
        $db = new Database();
        
        // Получаем данные из формы
        $user_type = $_POST['user_type']; // 'patient' или 'doctor'
        $login = trim($_POST['login']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $last_name = trim($_POST['last_name']);
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name'] ?? '');
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email'] ?? '');
        
        // Валидация
        if (empty($login) || empty($password) || empty($last_name) || empty($first_name) || empty($phone)) {
            throw new Exception("Заполните все обязательные поля");
        }
        
        if ($password !== $confirm_password) {
            throw new Exception("Пароли не совпадают");
        }
        
        if (strlen($password) < 6) {
            throw new Exception("Пароль должен содержать минимум 6 символов");
        }
        
        // Проверяем, не занят ли логин
        $check_user = $db->query("SELECT id FROM Users WHERE login = ?", [$login]);
        if ($check_user->num_rows > 0) {
            throw new Exception("Пользователь с таким логином уже существует");
        }
        
        // Дополнительные проверки для врачей
        if ($user_type === 'doctor') {
            $specialization = trim($_POST['specialization']);
            $license_number = trim($_POST['license_number']);
            $experience_years = intval($_POST['experience_years']);
            
            if (empty($specialization) || empty($license_number)) {
                throw new Exception("Для врачей обязательны специализация и номер лицензии");
            }
            
            // Проверяем уникальность лицензии
            $check_license = $db->query("SELECT id FROM Doctors WHERE license_number = ?", [$license_number]);
            if ($check_license->num_rows > 0) {
                throw new Exception("Врач с таким номером лицензии уже зарегистрирован");
            }
        }
        
        // Дополнительные проверки для пациентов
        if ($user_type === 'patient') {
            $birth_date = $_POST['birth_date'];
            $gender = $_POST['gender'];
            $insurance_policy = trim($_POST['insurance_policy']);
            
            if (empty($birth_date) || empty($gender) || empty($insurance_policy)) {
                throw new Exception("Для пациентов обязательны дата рождения, пол и номер полиса");
            }
            
            // Проверяем уникальность полиса
            $check_policy = $db->query("SELECT id FROM Patients WHERE insurance_policy = ?", [$insurance_policy]);
            if ($check_policy->num_rows > 0) {
                throw new Exception("Пациент с таким номером полиса уже зарегистрирован");
            }
        }
        
        // Начинаем транзакцию
        $db->getConnection()->begin_transaction();
        
        try {
            // Создаем пользователя (только login и password_hash)
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $db->query(
                "INSERT INTO Users (login, password_hash) VALUES (?, ?)",
                [$login, $password_hash]
            );
            
            $user_id = $db->getConnection()->insert_id;
            
            // Создаем запись в соответствующей таблице
            if ($user_type === 'doctor') {
                $db->query(
                    "INSERT INTO Doctors (user_id, last_name, first_name, middle_name, specialization, license_number, experience_years, phone, email, is_active) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
                    [$user_id, $last_name, $first_name, $middle_name, $specialization, $license_number, $experience_years, $phone, $email]
                );
                
                // Получаем ID врача для сессии
                $doctor_result = $db->query("SELECT id FROM Doctors WHERE user_id = ?", [$user_id]);
                $doctor_data = $doctor_result->fetch_assoc();
                
                // Устанавливаем данные в сессии
                $_SESSION['role'] = 'Врач';
                $_SESSION['is_doctor'] = true;
                $_SESSION['doctor_id'] = $doctor_data['id'];
                $_SESSION['full_name'] = $last_name . ' ' . $first_name . ($middle_name ? ' ' . $middle_name : '');
                
            } else { // patient
                $db->query(
                    "INSERT INTO Patients (user_id, last_name, first_name, middle_name, birth_date, gender, phone, email, insurance_policy) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$user_id, $last_name, $first_name, $middle_name, $birth_date, $gender, $phone, $email, $insurance_policy]
                );
                
                // Получаем ID пациента для сессии
                $patient_result = $db->query("SELECT id FROM Patients WHERE user_id = ?", [$user_id]);
                $patient_data = $patient_result->fetch_assoc();
                
                // Устанавливаем данные в сессии
                $_SESSION['role'] = 'Пациент';
                $_SESSION['patient_id'] = $patient_data['id'];
                $_SESSION['full_name'] = $last_name . ' ' . $first_name . ($middle_name ? ' ' . $middle_name : '');
            }
            
            // Устанавливаем общие данные сессии
            $_SESSION['user_id'] = $user_id;
            $_SESSION['login'] = $login;
            
            // Подтверждаем транзакцию
            $db->getConnection()->commit();
            
            $success = "Регистрация прошла успешно! Вы автоматически вошли в систему.";
            
        } catch (Exception $e) {
            // Откатываем транзакцию в случае ошибки
            $db->getConnection()->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        $error = "Ошибка при регистрации: " . $e->getMessage();
    } finally {
        if (isset($db)) {
            $db->close();
        }
    }
}

// Если регистрация успешна и пользователь авторизован - перенаправляем на главную
if ($success && isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - МЕДИС</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .user-type-selection {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .user-type-option {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .user-type-option:hover {
            border-color: #007bff;
        }
        
        .user-type-option.selected {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        
        .user-type-icon {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .form-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
        
        .form-section.active {
            display: block;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card" style="max-width: 600px;">
            <div class="login-logo">
                <div class="logo-placeholder">
                    МС
                </div>
                <h2>Регистрация в МЕДИС</h2>
                <p>Создайте учетную запись</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                    <div style="margin-top: 10px;">
                        <a href="dashboard.php" class="btn btn-primary">Перейти в личный кабинет</a>
                    </div>
                </div>
            <?php else: ?>
            
            <form method="POST" id="registrationForm">
                <!-- Выбор типа пользователя -->
                <div class="form-group">
                    <label class="form-label">Тип учетной записи *</label>
                    <div class="user-type-selection">
                        <div class="user-type-option" data-type="patient">
                            <div class="user-type-icon">👤</div>
                            <div>Пациент</div>
                        </div>
                        <div class="user-type-option" data-type="doctor">
                            <div class="user-type-icon">👨‍⚕️</div>
                            <div>Врач</div>
                        </div>
                    </div>
                    <input type="hidden" name="user_type" id="selectedUserType" required>
                </div>
                
                <!-- Общие поля -->
                <div class="form-group">
                    <label class="form-label">Логин *</label>
                    <input type="text" class="form-control" name="login" 
                           value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>" 
                           required placeholder="Придумайте логин">
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Пароль *</label>
                            <input type="password" class="form-control" name="password" 
                                   id="password" required placeholder="Не менее 6 символов">
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Подтверждение пароля *</label>
                            <input type="password" class="form-control" name="confirm_password" 
                                   id="confirmPassword" required placeholder="Повторите пароль">
                            <div class="password-strength" id="passwordMatch"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Фамилия *</label>
                            <input type="text" class="form-control" name="last_name" 
                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                                   required placeholder="Иванов">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Имя *</label>
                            <input type="text" class="form-control" name="first_name" 
                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                                   required placeholder="Иван">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Отчество</label>
                            <input type="text" class="form-control" name="middle_name" 
                                   value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>" 
                                   placeholder="Иванович">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Телефон *</label>
                            <input type="tel" class="form-control" name="phone" 
                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                                   required placeholder="+7 (999) 123-45-67">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   placeholder="ivanov@example.com">
                        </div>
                    </div>
                </div>
                
                <!-- Поля для пациентов -->
                <div class="form-section" id="patientFields">
                    <h4 style="margin-bottom: 15px;">👤 Данные пациента</h4>
                    <div class="row">
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Дата рождения *</label>
                                <input type="date" class="form-control" name="birth_date" 
                                       value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>"
                                       max="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Пол *</label>
                                <select class="form-control" name="gender" required>
                                    <option value="">Выберите пол</option>
                                    <option value="M" <?php echo ($_POST['gender'] ?? '') === 'M' ? 'selected' : ''; ?>>Мужской</option>
                                    <option value="F" <?php echo ($_POST['gender'] ?? '') === 'F' ? 'selected' : ''; ?>>Женский</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Полис ОМС *</label>
                                <input type="text" class="form-control" name="insurance_policy" 
                                       value="<?php echo htmlspecialchars($_POST['insurance_policy'] ?? ''); ?>" 
                                       required placeholder="1234567890123456">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Поля для врачей -->
                <div class="form-section" id="doctorFields">
                    <h4 style="margin-bottom: 15px;">👨‍⚕️ Профессиональные данные</h4>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Специализация *</label>
                                <input type="text" class="form-control" name="specialization" 
                                       value="<?php echo htmlspecialchars($_POST['specialization'] ?? ''); ?>" 
                                       placeholder="Терапевт, Хирург, Кардиолог...">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Номер лицензии *</label>
                                <input type="text" class="form-control" name="license_number" 
                                       value="<?php echo htmlspecialchars($_POST['license_number'] ?? ''); ?>" 
                                       required placeholder="ЛО-77-01-012345">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Опыт работы (лет) *</label>
                                <input type="number" class="form-control" name="experience_years" 
                                       value="<?php echo htmlspecialchars($_POST['experience_years'] ?? '0'); ?>" 
                                       min="0" max="50" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="register" class="btn btn-primary btn-block" id="registerBtn">
                    Зарегистрироваться
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <p>Уже есть учетная запись? <a href="login.php">Войдите в систему</a></p>
            </div>
            
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Выбор типа пользователя
        document.querySelectorAll('.user-type-option').forEach(option => {
            option.addEventListener('click', function() {
                const userType = this.getAttribute('data-type');
                
                // Сбрасываем выделение
                document.querySelectorAll('.user-type-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                
                // Выделяем выбранный тип
                this.classList.add('selected');
                document.getElementById('selectedUserType').value = userType;
                
                // Показываем соответствующие поля
                document.getElementById('patientFields').classList.remove('active');
                document.getElementById('doctorFields').classList.remove('active');
                
                if (userType === 'patient') {
                    document.getElementById('patientFields').classList.add('active');
                } else if (userType === 'doctor') {
                    document.getElementById('doctorFields').classList.add('active');
                }
                
                // Активируем кнопку регистрации
                document.getElementById('registerBtn').disabled = false;
            });
        });

        // Проверка сложности пароля
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strength = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strength.textContent = '';
                return;
            }
            
            let score = 0;
            if (password.length >= 6) score++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) score++;
            if (password.match(/\d/)) score++;
            if (password.match(/[^a-zA-Z\d]/)) score++;
            
            if (score === 0) {
                strength.textContent = 'Слишком короткий';
                strength.className = 'password-strength strength-weak';
            } else if (score <= 2) {
                strength.textContent = 'Слабый';
                strength.className = 'password-strength strength-weak';
            } else if (score === 3) {
                strength.textContent = 'Средний';
                strength.className = 'password-strength strength-medium';
            } else {
                strength.textContent = 'Сильный';
                strength.className = 'password-strength strength-strong';
            }
        });

        // Проверка совпадения паролей
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirm = this.value;
            const match = document.getElementById('passwordMatch');
            
            if (confirm.length === 0) {
                match.textContent = '';
                return;
            }
            
            if (password === confirm) {
                match.textContent = '✓ Пароли совпадают';
                match.className = 'password-strength strength-strong';
            } else {
                match.textContent = '✗ Пароли не совпадают';
                match.className = 'password-strength strength-weak';
            }
        });

        // Валидация формы перед отправкой
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const userType = document.getElementById('selectedUserType').value;
            
            if (!userType) {
                e.preventDefault();
                alert('Пожалуйста, выберите тип учетной записи');
                return false;
            }
            
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirmPassword').value;
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Пароли не совпадают');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Пароль должен содержать минимум 6 символов');
                return false;
            }
            
            return true;
        });

        // Блокируем кнопку до выбора типа пользователя
        document.getElementById('registerBtn').disabled = true;
    </script>
</body>
</html>