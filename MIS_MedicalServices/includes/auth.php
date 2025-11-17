<?php
require_once 'database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Авторизация пользователя
     */
    public function login($login, $password) {
        $result = $this->db->query("SELECT * FROM Users WHERE login = ?", [$login]);
        
        if ($result->num_rows === 0) {
            throw new Exception("Неверный логин или пароль");
        }
        
        $user = $result->fetch_assoc();
        
        if (!password_verify($password, $user['password_hash'])) {
            throw new Exception("Неверный логин или пароль");
        }
        
        // Определяем роль пользователя и получаем ФИО
        $role = 'Пациент'; // по умолчанию
        $full_name = '';
        $is_doctor = false;
        $is_admin = false;
        $doctor_id = null;
        $patient_id = null;
        $admin_id = null;
        
        // Сначала проверяем, является ли пользователь администратором (высший приоритет)
        $admin_result = $this->db->query(
            "SELECT id, last_name, first_name, middle_name, access_level FROM Administrators WHERE user_id = ? AND is_active = 1", 
            [$user['id']]
        );
        
        if ($admin_result->num_rows > 0) {
            $role = 'Администратор';
            $is_admin = true;
            $admin_data = $admin_result->fetch_assoc();
            $admin_id = $admin_data['id'];
            $full_name = $admin_data['last_name'] . ' ' . $admin_data['first_name'];
            if (!empty($admin_data['middle_name'])) {
                $full_name .= ' ' . $admin_data['middle_name'];
            }
            $_SESSION['access_level'] = $admin_data['access_level'];
        }
        // Если не администратор, проверяем врача
        else {
            $doctor_result = $this->db->query(
                "SELECT id, last_name, first_name, middle_name FROM Doctors WHERE user_id = ?", 
                [$user['id']]
            );
            
            if ($doctor_result->num_rows > 0) {
                $role = 'Врач';
                $is_doctor = true;
                $doctor_data = $doctor_result->fetch_assoc();
                $doctor_id = $doctor_data['id'];
                $full_name = $doctor_data['last_name'] . ' ' . $doctor_data['first_name'];
                if (!empty($doctor_data['middle_name'])) {
                    $full_name .= ' ' . $doctor_data['middle_name'];
                }
            }
            // Если не врач, проверяем пациента
            else {
                $patient_result = $this->db->query(
                    "SELECT id, last_name, first_name, middle_name FROM Patients WHERE user_id = ?", 
                    [$user['id']]
                );
                
                if ($patient_result->num_rows > 0) {
                    $role = 'Пациент';
                    $patient_data = $patient_result->fetch_assoc();
                    $patient_id = $patient_data['id'];
                    $full_name = $patient_data['last_name'] . ' ' . $patient_data['first_name'];
                    if (!empty($patient_data['middle_name'])) {
                        $full_name .= ' ' . $patient_data['middle_name'];
                    }
                } else {
                    throw new Exception("Профиль пользователя не найден");
                }
            }
        }
        
        // Устанавливаем сессию
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login'] = $user['login'];
        $_SESSION['full_name'] = $full_name;
        $_SESSION['role'] = $role;
        $_SESSION['is_doctor'] = $is_doctor;
        $_SESSION['is_admin'] = $is_admin;
        
        if ($doctor_id) {
            $_SESSION['doctor_id'] = $doctor_id;
        }
        
        if ($patient_id) {
            $_SESSION['patient_id'] = $patient_id;
        }
        
        if ($admin_id) {
            $_SESSION['admin_id'] = $admin_id;
        }
        
        // Сбрасываем счетчик попыток входа при успешной авторизации
        if (isset($_SESSION['login_attempts'])) {
            unset($_SESSION['login_attempts']);
        }
        
        return true;
    }
    
    /**
     * Выход пользователя
     */
    public function logout() {
        // Очищаем все переменные сессии
        $_SESSION = array();
        
        // Удаляем куки сессии
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Уничтожаем сессию
        session_destroy();
        
        // Перенаправляем на страницу входа
        header('Location: login.php');
        exit;
    }
    
    /**
     * Проверка прав доступа
     */
    public function checkAccess($required_role) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
        
        $current_role = $_SESSION['role'];
        
        // Проверяем права доступа в зависимости от иерархии ролей
        $roles_hierarchy = [
            'Пациент' => 1,
            'Врач' => 2,
            'Администратор' => 3
        ];
        
        $current_level = $roles_hierarchy[$current_role] ?? 0;
        $required_level = $roles_hierarchy[$required_role] ?? 0;
        
        if ($current_level < $required_level) {
            header('Location: access_denied.php');
            exit;
        }
        
        return true;
    }
    
    /**
     * Проверка, авторизован ли пользователь
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Получение информации о текущем пользователе
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'login' => $_SESSION['login'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role'],
            'is_doctor' => $_SESSION['is_doctor'] ?? false,
            'is_admin' => $_SESSION['is_admin'] ?? false,
            'doctor_id' => $_SESSION['doctor_id'] ?? null,
            'patient_id' => $_SESSION['patient_id'] ?? null,
            'admin_id' => $_SESSION['admin_id'] ?? null,
            'access_level' => $_SESSION['access_level'] ?? null
        ];
    }
    
    /**
     * Проверка, является ли пользователь администратором
     */
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['role'] === 'Администратор';
    }
    
    /**
     * Проверка, является ли пользователь врачом
     */
    public function isDoctor() {
        return $this->isLoggedIn() && ($_SESSION['role'] === 'Врач' || $_SESSION['role'] === 'Администратор');
    }
    
    /**
     * Проверка, является ли пользователь пациентом
     */
    public function isPatient() {
        return $this->isLoggedIn() && $_SESSION['role'] === 'Пациент';
    }
    
    /**
     * Проверка, имеет ли администратор полный доступ
     */
    public function hasFullAccess() {
        return $this->isAdmin() && ($_SESSION['access_level'] ?? 'full') === 'full';
    }
    
    /**
     * Получение ID врача для текущего пользователя
     */
    public function getDoctorId() {
        if ($this->isDoctor() && isset($_SESSION['doctor_id'])) {
            return $_SESSION['doctor_id'];
        }
        return null;
    }
    
    /**
     * Получение ID пациента для текущего пользователя
     */
    public function getPatientId() {
        if ($this->isPatient() && isset($_SESSION['patient_id'])) {
            return $_SESSION['patient_id'];
        }
        return null;
    }
    
    /**
     * Получение ID администратора для текущего пользователя
     */
    public function getAdminId() {
        if ($this->isAdmin() && isset($_SESSION['admin_id'])) {
            return $_SESSION['admin_id'];
        }
        return null;
    }
    
    /**
     * Создание администратора
     */
    public function createAdministrator($user_id, $admin_data) {
        // Проверяем, не является ли пользователь уже администратором
        $check_admin = $this->db->query("SELECT id FROM Administrators WHERE user_id = ?", [$user_id]);
        if ($check_admin->num_rows > 0) {
            throw new Exception("Этот пользователь уже является администратором");
        }
        
        // Проверяем существование пользователя
        $check_user = $this->db->query("SELECT id FROM Users WHERE id = ?", [$user_id]);
        if ($check_user->num_rows === 0) {
            throw new Exception("Пользователь с указанным ID не существует");
        }
        
        $this->db->query(
            "INSERT INTO Administrators (user_id, last_name, first_name, middle_name, phone, email, access_level) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $user_id,
                $admin_data['last_name'],
                $admin_data['first_name'],
                $admin_data['middle_name'] ?? '',
                $admin_data['phone'],
                $admin_data['email'] ?? '',
                $admin_data['access_level'] ?? 'full'
            ]
        );
        
        return $this->db->getConnection()->insert_id;
    }
    
    /**
     * Получение списка администраторов
     */
    public function getAdministrators() {
        $result = $this->db->query("
            SELECT a.*, u.login 
            FROM Administrators a 
            JOIN Users u ON a.user_id = u.id 
            WHERE a.is_active = 1 
            ORDER BY a.last_name, a.first_name
        ");
        
        $admins = [];
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row;
        }
        
        return $admins;
    }
    
    /**
     * Получение администратора по ID пользователя
     */
    public function getAdministratorByUserId($user_id) {
        $result = $this->db->query("
            SELECT a.*, u.login 
            FROM Administrators a 
            JOIN Users u ON a.user_id = u.id 
            WHERE a.user_id = ? AND a.is_active = 1
        ", [$user_id]);
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Деактивация администратора
     */
    public function deactivateAdministrator($admin_id) {
        return $this->db->query(
            "UPDATE Administrators SET is_active = 0 WHERE id = ?",
            [$admin_id]
        );
    }
    
    /**
     * Проверка необходимости смены пароля
     */
    public function requiresPasswordChange() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return false;
    }
    
    /**
     * Обновление пароля пользователя
     */
    public function changePassword($user_id, $new_password) {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $result = $this->db->query(
            "UPDATE Users SET password_hash = ? WHERE id = ?",
            [$password_hash, $user_id]
        );
        
        return $result;
    }
    
    /**
     * Проверка сложности пароля
     */
    public function validatePasswordStrength($password) {
        if (strlen($password) < 6) {
            return "Пароль должен содержать минимум 6 символов";
        }
        
        return true;
    }
    
    /**
     * Создание тестового администратора (для разработки)
     */
    public function createTestAdministrator() {
        try {
            // Создаем пользователя
            $this->db->query(
                "INSERT INTO Users (login, password_hash) VALUES (?, ?)",
                ['admin', password_hash('admin123', PASSWORD_DEFAULT)]
            );
            
            $user_id = $this->db->getConnection()->insert_id;
            
            // Создаем администратора
            $this->db->query(
                "INSERT INTO Administrators (user_id, last_name, first_name, phone, email, access_level) 
                 VALUES (?, 'Главный', 'Администратор', '+79990000000', 'admin@medis.ru', 'full')",
                [$user_id]
            );
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error creating test admin: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Проверка существования тестового администратора
     */
    public function testAdministratorExists() {
        $result = $this->db->query("
            SELECT a.id 
            FROM Administrators a 
            JOIN Users u ON a.user_id = u.id 
            WHERE u.login = 'admin'
        ");
        
        return $result->num_rows > 0;
    }
    
    /**
     * Деструктор
     */
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
}