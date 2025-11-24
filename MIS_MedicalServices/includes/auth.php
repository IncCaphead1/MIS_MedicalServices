<?php
require_once 'includes/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function login($login, $password) {
        try {
            // Проверка блокировки
            if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
                if (time() - $_SESSION['last_attempt_time'] < LOCKOUT_TIME) {
                    throw new Exception("Аккаунт заблокирован. Попробуйте через 15 минут.");
                } else {
                    unset($_SESSION['login_attempts']);
                    unset($_SESSION['last_attempt_time']);
                }
            }
            
            // Ищем пользователя в таблице Users
            $sql = "SELECT u.* FROM Users u WHERE u.login = ?";
            $result = $this->db->query($sql, [$login]);
            $user = $result->fetch_assoc();
            
            if (!$user) {
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                $_SESSION['last_attempt_time'] = time();
                throw new Exception("Неверное имя пользователя или пароль");
            }
            
            // Проверяем пароль (в вашей БД пароли не хешированы)
            if ($user['password_hash'] !== $password) {
                $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
                $_SESSION['last_attempt_time'] = time();
                throw new Exception("Неверное имя пользователя или пароль");
            }
            
            // Проверяем, заблокирован ли пользователь
            if ($user['is_locked']) {
                throw new Exception("Аккаунт заблокирован. Обратитесь к администратору.");
            }
            
            // Определяем роль пользователя на основе данных
            $role = 'Пациент'; // базовая роль
            $full_name = '';
            $is_doctor = false;
            $doctor_id = null;
            
            // Проверяем, является ли пользователь врачом
            $doctor_sql = "SELECT d.* FROM Doctors d WHERE d.user_id = ?";
            $doctor_result = $this->db->query($doctor_sql, [$user['id']]);
            if ($doctor_result->num_rows > 0) {
                $doctor = $doctor_result->fetch_assoc();
                $role = 'Врач';
                $is_doctor = true;
                $doctor_id = $doctor['id'];
                $full_name = $doctor['last_name'] . ' ' . $doctor['first_name'] . ' ' . ($doctor['middle_name'] ?? '');
            } else {
                // Проверяем, является ли пользователь пациентом
                $patient_sql = "SELECT p.* FROM Patients p WHERE p.user_id = ?";
                $patient_result = $this->db->query($patient_sql, [$user['id']]);
                if ($patient_result->num_rows > 0) {
                    $patient = $patient_result->fetch_assoc();
                    $full_name = $patient['last_name'] . ' ' . $patient['first_name'] . ' ' . ($patient['middle_name'] ?? '');
                    $role = 'Пациент';
                } else {
                    // Если не пациент и не врач, возможно это администратор
                    $role = 'Администратор';
                    $full_name = 'Администратор';
                }
            }
            
            // Успешный вход
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_attempt_time']);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['login'] = $user['login'];
            $_SESSION['role'] = $role;
            $_SESSION['full_name'] = trim($full_name) ?: $user['login'];
            $_SESSION['is_doctor'] = $is_doctor;
            
            if ($is_doctor) {
                $_SESSION['doctor_id'] = $doctor_id;
            }
            
            // Логируем успешную попытку входа
            $this->logLoginAttempt($user['id'], $login, 'success');
            
            return true;
            
        } catch (Exception $e) {
            // Логируем неудачную попытку
            if (isset($user)) {
                $this->logLoginAttempt($user['id'], $login, 'fail');
            }
            error_log("Login error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Функция для гостевого входа
    public function guestLogin() {
        $_SESSION['user_id'] = null;
        $_SESSION['login'] = 'guest';
        $_SESSION['role'] = 'Гость';
        $_SESSION['full_name'] = 'Гость';
        $_SESSION['is_doctor'] = false;
        
        return true;
    }
    
    private function logLoginAttempt($user_id, $login, $status) {
        try {
            $sql = "INSERT INTO Login_Attempts (user_id, attempt_login, attempt_status, attempt_ip, attempt_user_agent) 
                    VALUES (?, ?, ?, ?, ?)";
            $this->db->query($sql, [
                $user_id, 
                $login, 
                $status,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log("Failed to log login attempt: " . $e->getMessage());
        }
    }
    
    public function checkAccess($requiredRole) {
        if (!isset($_SESSION['role'])) {
            header('Location: login.php');
            exit;
        }
        
        // Гость имеет доступ только к просмотру
        if ($_SESSION['role'] === 'Гость' && in_array($requiredRole, ['Гость'])) {
            return true;
        }
        
        // Администратор имеет доступ ко всему
        if ($_SESSION['role'] === 'Администратор') {
            return true;
        }
        
        // Врач имеет доступ к функциям врача и пациента
        if ($_SESSION['role'] === 'Врач' && in_array($requiredRole, ['Врач', 'Пациент', 'Гость'])) {
            return true;
        }
        
        // Пациент имеет доступ к своим функциям и просмотру
        if ($_SESSION['role'] === 'Пациент' && in_array($requiredRole, ['Пациент', 'Гость'])) {
            return true;
        }
        
        // Если ни одно условие не выполнено - доступ запрещен
        header('Location: access_denied.php');
        exit;
    }
    
    public function requireRegistration() {
        if ($_SESSION['role'] === 'Гость') {
            header('Location: registration_required.php');
            exit;
        }
    }
    
    public function logout() {
        session_destroy();
        header('Location: login.php');
        exit;
    }
}
?>