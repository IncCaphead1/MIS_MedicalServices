<?php
require_once 'database.php';

class MedicalSystem {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Аналитика: Среднее количество пациентов в день для врача
    public function getAveragePatientsPerDay($doctorId, $startDate, $endDate) {
        $sql = "SELECT COUNT(*) as patient_count, COUNT(DISTINCT DATE(appointment_date)) as day_count 
                FROM appointments 
                WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ?";
        $result = $this->db->query($sql, [$doctorId, $startDate, $endDate]);
        $data = $result->fetch_assoc();
        
        if ($data && $data['day_count'] > 0) {
            return $data['patient_count'] / $data['day_count'];
        }
        return 0;
    }
    
    // Аналитика: Самый популярный медицинский услуги за месяц
    public function getMostPopularService($month, $year) {
        $sql = "SELECT s.name, COUNT(*) as count 
                FROM appointments a 
                JOIN services s ON a.service_id = s.id 
                WHERE MONTH(a.appointment_date) = ? AND YEAR(a.appointment_date) = ? 
                GROUP BY s.id 
                ORDER BY count DESC 
                LIMIT 1";
        $result = $this->db->query($sql, [$month, $year]);
        return $result->fetch_assoc();
    }
    
    // Аналитика: График занятости врача на неделю
    public function getDoctorSchedule($doctorId, $weekStart) {
        $weekEnd = date('Y-m-d', strtotime($weekStart . ' +6 days'));
        $sql = "SELECT DATE(appointment_date) as date, 
                       COUNT(*) as appointments_count,
                       GROUP_CONCAT(TIME(appointment_date)) as times 
                FROM appointments 
                WHERE doctor_id = ? AND appointment_date BETWEEN ? AND ? 
                GROUP BY DATE(appointment_date) 
                ORDER BY date";
        $result = $this->db->query($sql, [$doctorId, $weekStart, $weekEnd]);
        
        $schedule = [];
        while ($row = $result->fetch_assoc()) {
            $schedule[] = $row;
        }
        return $schedule;
    }
    
    // Аналитика: Помечать просроченные записи (без диагноза)
    public function getOverdueAppointments() {
        $sql = "SELECT a.*, p.last_name, p.first_name, d.last_name as doctor_name 
                FROM appointments a 
                JOIN patients p ON a.patient_id = p.id 
                JOIN doctors d ON a.doctor_id = d.id 
                WHERE a.diagnosis IS NULL AND a.appointment_date < CURDATE()";
        $result = $this->db->query($sql);
        
        $appointments = [];
        while ($row = $result->fetch_assoc()) {
            $appointments[] = $row;
        }
        return $appointments;
    }
    
    // Аналитика: Расчет скидки для пациента
    public function calculatePatientDiscount($patientId, $year) {
        $sql = "SELECT SUM(s.cost) as total_paid 
                FROM appointments a 
                JOIN services s ON a.service_id = s.id 
                WHERE a.patient_id = ? AND YEAR(a.appointment_date) = ? AND a.status = 'completed'";
        $result = $this->db->query($sql, [$patientId, $year]);
        $data = $result->fetch_assoc();
        $total = $data['total_paid'] ?? 0;
        
        // Скидка 10% при превышении 50,000 руб в год
        if ($total > 50000) {
            return 10;
        } elseif ($total > 25000) {
            return 5;
        }
        
        return 0;
    }
    
    // Расчет дохода за период
    public function calculateRevenue($startDate, $endDate) {
        $sql = "SELECT SUM(s.cost) as total_revenue 
                FROM appointments a 
                JOIN services s ON a.service_id = s.id 
                WHERE a.appointment_date BETWEEN ? AND ? AND a.status = 'completed'";
        $result = $this->db->query($sql, [$startDate, $endDate]);
        $data = $result->fetch_assoc();
        return $data['total_revenue'] ?? 0;
    }
    
    // Экспорт данных в Excel
    public function exportToExcel($data, $filename) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '.xlsx"');
        
        // Простой CSV экспорт (для простоты)
        $output = fopen('php://output', 'w');
        
        // Заголовки
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]), ';');
            
            // Данные
            foreach ($data as $row) {
                fputcsv($output, $row, ';');
            }
        }
        
        fclose($output);
        exit;
    }
    
    // Резервное копирование БД
    public function backupDatabase() {
        // Создаем директорию backup если ее нет
        if (!is_dir('backup')) {
            mkdir('backup', 0755, true);
        }
        
        $backupFile = 'backup/backup_' . date('Y-m-d_H-i-s') . '.sql';
        $connection = $this->db->getConnection();
        $tables = ['patients', 'doctors', 'appointments', 'services', 'users', 'roles'];
        $backupContent = "";
        
        foreach ($tables as $table) {
            // Проверяем существует ли таблица
            $checkTable = $this->db->query("SHOW TABLES LIKE ?", [$table]);
            if ($checkTable->num_rows === 0) {
                continue;
            }
            
            // Структура таблицы
            $result = $this->db->query("SHOW CREATE TABLE `$table`");
            if ($result && $row = $result->fetch_assoc()) {
                $backupContent .= $row['Create Table'] . ";\n\n";
                
                // Данные таблицы
                $dataResult = $this->db->query("SELECT * FROM `$table`");
                while ($dataRow = $dataResult->fetch_assoc()) {
                    $columns = implode('`, `', array_keys($dataRow));
                    $values = implode("', '", array_map([$connection, 'real_escape_string'], array_values($dataRow)));
                    $backupContent .= "INSERT INTO `$table` (`$columns`) VALUES ('$values');\n";
                }
                $backupContent .= "\n";
            }
        }
        
        if (file_put_contents($backupFile, $backupContent) !== false) {
            return $backupFile;
        } else {
            throw new Exception("Не удалось создать файл бэкапа");
        }
    }
    
    // Очистка старых бэкапов
    public function cleanupOldBackups($days = 30) {
        if (!is_dir('backup')) {
            return;
        }
        
        $files = glob('backup/*.sql');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 60 * 60 * 24 * $days) {
                    unlink($file);
                }
            }
        }
    }
    
    // Получение списка врачей
    public function getDoctors() {
        $result = $this->db->query("
            SELECT d.*, s.name as specialization 
            FROM doctors d 
            LEFT JOIN specializations s ON d.specialization_id = s.id 
            ORDER BY d.last_name, d.first_name
        ");
        
        $doctors = [];
        while ($row = $result->fetch_assoc()) {
            $doctors[] = $row;
        }
        return $doctors;
    }
    
    // Получение списка пациентов
    public function getPatients() {
        $result = $this->db->query("SELECT * FROM patients ORDER BY last_name, first_name");
        
        $patients = [];
        while ($row = $result->fetch_assoc()) {
            $patients[] = $row;
        }
        return $patients;
    }
    
    // Получение списка услуг
    public function getServices() {
        $result = $this->db->query("SELECT * FROM services ORDER BY name");
        
        $services = [];
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
        return $services;
    }
}
?>