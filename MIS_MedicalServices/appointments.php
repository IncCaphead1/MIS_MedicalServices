<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
// Все авторизованные пользователи имеют доступ к записям, но с разными правами
if ($_SESSION['role'] === 'Пациент') {
    // Пациенты видят только свои записи
    $auth->checkAccess('Пациент');
} else {
    // Врачи и администраторы видят все записи
    $auth->checkAccess('Врач');
}

$db = new Database();
$appointments = [];

// Формируем SQL запрос в зависимости от роли
if ($_SESSION['role'] === 'Пациент') {
    // Для пациента - только его записи
    $sql = "
        SELECT a.*, p.first_name, p.last_name, p.phone, 
               d.first_name as doctor_first_name, d.last_name as doctor_last_name,
               ms.name as service_name 
        FROM Appointments a 
        JOIN Patients p ON a.patient_id = p.id 
        JOIN Doctors d ON a.doctor_id = d.id 
        JOIN MedicalServices ms ON a.service_id = ms.id 
        WHERE p.user_id = ?
        ORDER BY a.appointment_date DESC
    ";
    $result = $db->query($sql, [$_SESSION['user_id']]);
} elseif ($_SESSION['is_doctor']) {
    // Для врача - только его записи
    $sql = "
        SELECT a.*, p.first_name, p.last_name, p.phone, 
               d.first_name as doctor_first_name, d.last_name as doctor_last_name,
               ms.name as service_name 
        FROM Appointments a 
        JOIN Patients p ON a.patient_id = p.id 
        JOIN Doctors d ON a.doctor_id = d.id 
        JOIN MedicalServices ms ON a.service_id = ms.id 
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date DESC
    ";
    $result = $db->query($sql, [$_SESSION['doctor_id']]);
} else {
    // Для администратора - все записи
    $sql = "
        SELECT a.*, p.first_name, p.last_name, p.phone, 
               d.first_name as doctor_first_name, d.last_name as doctor_last_name,
               ms.name as service_name 
        FROM Appointments a 
        JOIN Patients p ON a.patient_id = p.id 
        JOIN Doctors d ON a.doctor_id = d.id 
        JOIN MedicalServices ms ON a.service_id = ms.id 
        ORDER BY a.appointment_date DESC
    ";
    $result = $db->query($sql);
}

while ($row = $result->fetch_assoc()) {
    $appointments[] = $row;
}

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление записями - МЕДИС</title>
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
                <div class="server-info">
                    <span class="ip-address">IP: <?php echo SERVER_IP; ?></span>
                </div>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">На главную</a>
            </div>
        </div>
    </div>

    <!-- Navigation -->
<nav class="nav-menu">
    <div class="nav-content">
        <a href="dashboard.php" class="nav-item">Главная</a>
        <?php if ($_SESSION['role'] !== 'Пациент'): ?>
            <a href="patients.php" class="nav-item">Пациенты</a>
        <?php endif; ?>
        <a href="appointments.php" class="nav-item active">Записи</a>
        <a href="services.php" class="nav-item">Услуги</a>
        <?php if ($_SESSION['role'] === 'Администратор'): ?>
            <a href="reports.php" class="nav-item">Отчеты</a>
            <a href="doctors.php" class="nav-item">Врачи</a>
        <?php endif; ?>
    </div>
</nav>

    <div class="container">
        <h1>
            <?php if ($_SESSION['role'] === 'Пациент'): ?>
                📋 Мои записи
            <?php else: ?>
                🗓️ Управление записями
            <?php endif; ?>
        </h1>
        
        <div class="card">
            <div class="card-header">
                <h3>
                    <?php if ($_SESSION['role'] === 'Пациент'): ?>
                        📅 История моих записей
                    <?php else: ?>
                        📊 Все записи на прием
                    <?php endif; ?>
                </h3>
                <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                    <a href="?action=create" class="btn btn-success">Новая запись</a>
                <?php else: ?>
                    <a href="?action=create" class="btn btn-success">Записаться на прием</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (count($appointments) > 0): ?>
                    <table class="table" id="appointmentsTable">
                        <thead>
                            <tr>
                                <th data-sort="appointment_date">Дата и время</th>
                                <th data-sort="last_name">Пациент</th>
                                <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                                    <th data-sort="doctor_last_name">Врач</th>
                                <?php endif; ?>
                                <th data-sort="service_name">Услуга</th>
                                <th data-sort="price">Стоимость</th>
                                <th data-sort="status">Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($appointment['last_name'] . ' ' . $appointment['first_name']); ?></td>
                                <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                                    <td><?php echo htmlspecialchars($appointment['doctor_last_name'] . ' ' . $appointment['doctor_first_name']); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                <td><?php echo number_format($appointment['price'], 2, '.', ' '); ?> руб.</td>
                                <td>
                                    <span class="status-badge status-<?php echo $appointment['status']; ?>">
                                        <?php 
                                        $status_labels = [
                                            'scheduled' => 'Запланирован',
                                            'completed' => 'Завершен', 
                                            'cancelled' => 'Отменен',
                                            'no_show' => 'Не явился'
                                        ];
                                        echo $status_labels[$appointment['status']] ?? $appointment['status'];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?action=view&id=<?php echo $appointment['id']; ?>" class="btn btn-secondary btn-sm">Просмотр</a>
                                    <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                                        <a href="?action=edit&id=<?php echo $appointment['id']; ?>" class="btn btn-primary btn-sm">Редактировать</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d; padding: 20px;">
                        <?php if ($_SESSION['role'] === 'Пациент'): ?>
                            У вас пока нет записей на прием
                        <?php else: ?>
                            Записи на прием не найдены
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Фильтры для врачей и администраторов -->
        <?php if ($_SESSION['role'] !== 'Пациент'): ?>
        <div class="card">
            <div class="card-header">
                <h3>🔍 Фильтры поиска</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">Дата с</label>
                            <input type="date" class="form-control" name="date_from">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">Дата по</label>
                            <input type="date" class="form-control" name="date_to">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">Статус</label>
                            <select class="form-control" name="status">
                                <option value="">Все статусы</option>
                                <option value="scheduled">Запланирован</option>
                                <option value="completed">Завершен</option>
                                <option value="cancelled">Отменен</option>
                                <option value="no_show">Не явился</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">Врач</label>
                            <select class="form-control" name="doctor_id">
                                <option value="">Все врачи</option>
                                <?php
                                $db = new Database();
                                $doctors = $db->query("SELECT id, last_name, first_name FROM Doctors ORDER BY last_name");
                                while ($doctor = $doctors->fetch_assoc()):
                                ?>
                                    <option value="<?php echo $doctor['id']; ?>">
                                        <?php echo htmlspecialchars($doctor['last_name'] . ' ' . $doctor['first_name']); ?>
                                    </option>
                                <?php endwhile;
                                $db->close();
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Применить фильтры</button>
                        <a href="appointments.php" class="btn btn-secondary">Сбросить</a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Горячие клавиши для appointments.php
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.altKey || e.shiftKey) return;
            
            const key = e.key.toUpperCase();
            
            switch(key) {
                case '1':
                    e.preventDefault();
                    window.location.href = 'dashboard.php';
                    break;
                <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                case '2':
                    e.preventDefault();
                    window.location.href = 'patients.php';
                    break;
                case '3':
                    e.preventDefault();
                    window.location.href = 'doctors.php';
                    break;
                <?php endif; ?>
                case '4':
                    e.preventDefault();
                    window.location.href = 'appointments.php';
                    break;
                case '5':
                    e.preventDefault();
                    window.location.href = 'services.php';
                    break;
                case 'N':
                    e.preventDefault();
                    window.location.href = '?action=create';
                    break;
                case 'L':
                    e.preventDefault();
                    window.location.href = 'logout.php';
                    break;
                case 'ESCAPE':
                    e.preventDefault();
                    window.location.href = 'dashboard.php';
                    break;
            }
        });
    </script>
</body>
</html>