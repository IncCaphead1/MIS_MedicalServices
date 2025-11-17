<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
if ($_SESSION['role'] === 'Пациент') {
    $auth->checkAccess('Пациент');
} else {
    $auth->checkAccess('Врач');
}

$db = new Database();
$error = '';
$success = '';

if ($_POST && isset($_POST['doctor_id'])) {
    try {
        $doctor_id = $_POST['doctor_id'];
        $service_id = $_POST['service_id'];
        $appointment_date = $_POST['appointment_date'];
        $appointment_time = $_POST['appointment_time'];
        $notes = $_POST['notes'] ?? '';
        
        // Получаем ID пациента
        if ($_SESSION['role'] === 'Пациент') {
            $patient_result = $db->query("SELECT id FROM Patients WHERE user_id = ?", [$_SESSION['user_id']]);
            $patient_data = $patient_result->fetch_assoc();
            if (!$patient_data) {
                throw new Exception("Пациент не найден");
            }
            $selected_patient_id = $patient_data['id'];
        } else {
            $selected_patient_id = $_POST['patient_id'];
            if (!$selected_patient_id) {
                throw new Exception("Выберите пациента");
            }
        }
        
        // Проверяем обязательные поля
        if (!$doctor_id || !$service_id || !$appointment_date || !$appointment_time) {
            throw new Exception("Заполните все обязательные поля");
        }
        
        // Собираем полную дату и время
        $datetime = $appointment_date . ' ' . $appointment_time . ':00';
        
        // Получаем стоимость услуги
        $service_result = $db->query("SELECT base_price FROM MedicalServices WHERE id = ?", [$service_id]);
        $service_data = $service_result->fetch_assoc();
        if (!$service_data) {
            throw new Exception("Услуга не найдена");
        }
        $price = $service_data['base_price'];
        
        // Ищем подходящий schedule_id из таблицы Schedules
        $schedule_sql = "
            SELECT id FROM Schedules 
            WHERE doctor_id = ? 
            AND service_id = ?
            AND date = ?
            AND start_time <= ?
            AND end_time >= ?
            AND is_available = 1
            LIMIT 1
        ";
        
        $schedule_result = $db->query($schedule_sql, [
            $doctor_id, 
            $service_id, 
            $appointment_date, 
            $appointment_time, 
            $appointment_time
        ]);
        
        if ($schedule_result->num_rows === 0) {
            // Если нет подходящего расписания, создаем временное
            $create_schedule_sql = "
                INSERT INTO Schedules (doctor_id, service_id, date, start_time, end_time, is_available) 
                VALUES (?, ?, ?, ?, ?, 0)
            ";
            
            $end_time = date('H:i:s', strtotime($appointment_time . ' +30 minutes'));
            
            $db->query($create_schedule_sql, [
                $doctor_id,
                $service_id,
                $appointment_date,
                $appointment_time,
                $end_time
            ]);
            
            $schedule_id = $db->getConnection()->insert_id;
        } else {
            $schedule_data = $schedule_result->fetch_assoc();
            $schedule_id = $schedule_data['id'];
            
            // Помечаем расписание как занятое
            $db->query("UPDATE Schedules SET is_available = 0 WHERE id = ?", [$schedule_id]);
        }
        
        // Проверяем, не занято ли это время у врача
        $check_sql = "SELECT id FROM Appointments WHERE doctor_id = ? AND appointment_date = ? AND status != 'cancelled'";
        $check_result = $db->query($check_sql, [$doctor_id, $datetime]);
        
        if ($check_result->num_rows > 0) {
            $error = "Это время уже занято. Пожалуйста, выберите другое время.";
        } else {
            // Создаем запись с schedule_id
            $insert_sql = "
                INSERT INTO Appointments (patient_id, doctor_id, service_id, schedule_id, appointment_date, status, price, notes) 
                VALUES (?, ?, ?, ?, ?, 'scheduled', ?, ?)
            ";
            
            $db->query($insert_sql, [
                $selected_patient_id, 
                $doctor_id, 
                $service_id, 
                $schedule_id,
                $datetime, 
                $price, 
                $notes
            ]);
            
            $success = "Запись на прием успешно создана!";
        }
        
    } catch (Exception $e) {
        $error = "Ошибка при создании записи: " . $e->getMessage();
    }
}

$db->close();

// Перенаправляем обратно с сообщением
if ($success) {
    $_SESSION['success_message'] = $success;
    header('Location: appointments.php');
    exit;
} else {
    $_SESSION['error_message'] = $error;
    header('Location: appointments_create.php');
    exit;
}
?>