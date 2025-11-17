<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
// Все авторизованные пользователи могут записываться на прием
if ($_SESSION['role'] === 'Пациент') {
    $auth->checkAccess('Пациент');
} else {
    $auth->checkAccess('Врач');
}

$db = new Database();
$error = '';
$success = '';

// Получаем данные пациента (если это пациент)
$patient_id = null;
if ($_SESSION['role'] === 'Пациент') {
    $patient_result = $db->query("SELECT id FROM Patients WHERE user_id = ?", [$_SESSION['user_id']]);
    $patient_data = $patient_result->fetch_assoc();
    $patient_id = $patient_data['id'];
}

// Если передан service_id, выбираем его по умолчанию
$preselected_service_id = $_GET['service_id'] ?? null;

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Запись на прием - МЕДИС</title>
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
                <a href="appointments.php" class="btn btn-secondary btn-sm">Назад к записям</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3>
                    <?php if ($_SESSION['role'] === 'Пациент'): ?>
                        📅 Запись на прием
                    <?php else: ?>
                        ➕ Новая запись на прием
                    <?php endif; ?>
                </h3>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($_SESSION['error_message']); ?></div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['success_message']); ?></div>
                    <div style="text-align: center; margin: 20px 0;">
                        <a href="appointments.php" class="btn btn-primary">Вернуться к записям</a>
                        <a href="appointments_create.php" class="btn btn-success">Создать еще одну запись</a>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php else: ?>
                    <form method="POST" action="appointments_create_process.php">
                        <div class="row">
                            <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Пациент *</label>
                                    <select class="form-control" name="patient_id" required>
                                        <option value="">Выберите пациента</option>
                                        <?php
                                        $db = new Database();
                                        $patients = $db->query("
                                            SELECT p.*, u.login 
                                            FROM Patients p 
                                            JOIN Users u ON p.user_id = u.id 
                                            ORDER BY p.last_name, p.first_name
                                        ");
                                        while ($patient = $patients->fetch_assoc()): ?>
                                            <option value="<?php echo $patient['id']; ?>">
                                                <?php echo htmlspecialchars($patient['last_name'] . ' ' . $patient['first_name'] . ' (' . $patient['insurance_policy'] . ')'); ?>
                                            </option>
                                        <?php endwhile;
                                        $db->close();
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Врач *</label>
                                    <select class="form-control" name="doctor_id" required id="doctorSelect">
                                        <option value="">Выберите врача</option>
                                        <?php 
                                        $db = new Database();
                                        $doctors = $db->query("
                                            SELECT d.*, u.login 
                                            FROM Doctors d 
                                            JOIN Users u ON d.user_id = u.id 
                                            WHERE d.is_active = 1 
                                            ORDER BY d.last_name, d.first_name
                                        ");
                                        while ($doctor = $doctors->fetch_assoc()): ?>
                                            <option value="<?php echo $doctor['id']; ?>">
                                                <?php echo htmlspecialchars($doctor['last_name'] . ' ' . $doctor['first_name'] . ' - ' . $doctor['specialization']); ?>
                                            </option>
                                        <?php endwhile;
                                        $db->close();
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Услуга *</label>
                                    <select class="form-control" name="service_id" required id="serviceSelect">
                                        <option value="">Выберите услугу</option>
                                        <?php 
                                        $db = new Database();
                                        $services = $db->query("
                                            SELECT * FROM MedicalServices 
                                            WHERE is_available = 1 
                                            ORDER BY category, name
                                        ");
                                        while ($service = $services->fetch_assoc()): ?>
                                            <option value="<?php echo $service['id']; ?>" 
                                                    data-price="<?php echo $service['base_price']; ?>"
                                                    <?php echo ($service['id'] == $preselected_service_id) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($service['name'] . ' (' . $service['category'] . ') - ' . $service['base_price'] . ' руб.'); ?>
                                            </option>
                                        <?php endwhile;
                                        $db->close();
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Стоимость</label>
                                    <input type="text" class="form-control" id="priceDisplay" value="0.00 руб." readonly style="background: #f8f9fa;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Дата приема *</label>
                                    <input type="date" class="form-control" name="appointment_date" required 
                                           min="<?php echo date('Y-m-d'); ?>" id="appointmentDate">
                                </div>
                            </div>
                            
                            <div class="col-6">
                                <div class="form-group">
                                    <label class="form-label">Время приема *</label>
                                    <select class="form-control" name="appointment_time" required id="appointmentTime">
                                        <option value="">Выберите время</option>
                                        <?php
                                        // Генерируем временные слоты с 9:00 до 18:00 каждые 30 минут
                                        for ($hour = 9; $hour <= 18; $hour++) {
                                            for ($minute = 0; $minute < 60; $minute += 30) {
                                                if ($hour == 18 && $minute > 0) break; // Не показывать время после 18:00
                                                $time = sprintf("%02d:%02d", $hour, $minute);
                                                echo "<option value=\"$time\">$time</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Примечания</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Дополнительная информация..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary" id="submitBtn">Записаться на прием</button>
                            <a href="appointments.php" class="btn btn-secondary">Отмена</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Обновление стоимости при выборе услуги
        document.getElementById('serviceSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const price = selectedOption.getAttribute('data-price');
            document.getElementById('priceDisplay').value = price ? price + ' руб.' : '0.00 руб.';
        });

        // Установка минимальной даты - сегодня
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('appointmentDate').min = today;

        // Проверка доступности времени при изменении даты или врача
        function checkAvailability() {
            const doctorId = document.getElementById('doctorSelect').value;
            const date = document.getElementById('appointmentDate').value;
            const timeSelect = document.getElementById('appointmentTime');
            
            if (doctorId && date) {
                timeSelect.disabled = false;
            } else {
                timeSelect.disabled = true;
            }
        }

        document.getElementById('doctorSelect').addEventListener('change', checkAvailability);
        document.getElementById('appointmentDate').addEventListener('change', checkAvailability);

        // Валидация формы перед отправкой
        document.querySelector('form').addEventListener('submit', function(e) {
            const doctorId = document.getElementById('doctorSelect').value;
            const serviceId = document.getElementById('serviceSelect').value;
            const date = document.getElementById('appointmentDate').value;
            const time = document.getElementById('appointmentTime').value;
            
            if (!doctorId || !serviceId || !date || !time) {
                e.preventDefault();
                alert('Пожалуйста, заполните все обязательные поля');
                return false;
            }
            
            // Проверяем, что дата не в прошлом
            const selectedDateTime = new Date(date + ' ' + time);
            const now = new Date();
            if (selectedDateTime < now) {
                e.preventDefault();
                alert('Нельзя записываться на прошедшую дату');
                return false;
            }
            
            return true;
        });

        // Автоматическое обновление стоимости при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            const serviceSelect = document.getElementById('serviceSelect');
            if (serviceSelect.value) {
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                document.getElementById('priceDisplay').value = price ? price + ' руб.' : '0.00 руб.';
            }
        });
    </script>
</body>
</html>