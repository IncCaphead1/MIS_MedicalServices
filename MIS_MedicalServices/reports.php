<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
// Только администраторы имеют доступ к отчетам
$auth->checkAccess('Администратор');

$db = new Database();

// Статистика для отчетов
$total_patients = $db->query("SELECT COUNT(*) as count FROM Patients")->fetch_assoc()['count'];
$total_doctors = $db->query("SELECT COUNT(*) as count FROM Doctors WHERE is_active = 1")->fetch_assoc()['count'];
$total_appointments = $db->query("SELECT COUNT(*) as count FROM Appointments")->fetch_assoc()['count'];
$total_services = $db->query("SELECT COUNT(*) as count FROM MedicalServices WHERE is_available = 1")->fetch_assoc()['count'];

// Доход за месяц
$month_revenue = $db->query("
    SELECT SUM(price) as revenue 
    FROM Appointments 
    WHERE MONTH(appointment_date) = MONTH(CURDATE()) 
    AND YEAR(appointment_date) = YEAR(CURDATE())
    AND status = 'completed'
")->fetch_assoc()['revenue'] ?? 0;

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчеты - МЕДИС</title>
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
            <a href="patients.php" class="nav-item">Пациенты</a>
            <a href="doctors.php" class="nav-item">Врачи</a>
            <a href="appointments.php" class="nav-item">Записи</a>
            <a href="services.php" class="nav-item">Услуги</a>
            <a href="reports.php" class="nav-item active">Отчеты</a>
        </div>
    </nav>

    <div class="container">
        <h1>📊 Отчеты и статистика</h1>
        
        <!-- Статистика -->
        <div class="row">
            <div class="col-3">
                <div class="card">
                    <div class="card-header">
                        <h3>👥 Пациенты</h3>
                    </div>
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 2.5rem; font-weight: bold; color: #007bff;">
                            <?php echo $total_patients; ?>
                        </div>
                        <p>Всего пациентов</p>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="card">
                    <div class="card-header">
                        <h3>👨‍⚕️ Врачи</h3>
                    </div>
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 2.5rem; font-weight: bold; color: #28a745;">
                            <?php echo $total_doctors; ?>
                        </div>
                        <p>Активных врачей</p>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="card">
                    <div class="card-header">
                        <h3>📅 Записи</h3>
                    </div>
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 2.5rem; font-weight: bold; color: #ffc107;">
                            <?php echo $total_appointments; ?>
                        </div>
                        <p>Всего записей</p>
                    </div>
                </div>
            </div>
            
            <div class="col-3">
                <div class="card">
                    <div class="card-header">
                        <h3>💰 Доход</h3>
                    </div>
                    <div class="card-body" style="text-align: center;">
                        <div style="font-size: 2rem; font-weight: bold; color: #dc3545;">
                            <?php echo number_format($month_revenue, 0, '.', ' '); ?> ₽
                        </div>
                        <p>За текущий месяц</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Детальные отчеты -->
        <div class="row">
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3>📈 Популярные услуги</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $db = new Database();
                        $popular_services = $db->query("
                            SELECT ms.name, COUNT(a.id) as appointment_count 
                            FROM MedicalServices ms 
                            LEFT JOIN Appointments a ON ms.id = a.service_id 
                            WHERE ms.is_available = 1 
                            GROUP BY ms.id 
                            ORDER BY appointment_count DESC 
                            LIMIT 5
                        ");
                        
                        if ($popular_services->num_rows > 0): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Услуга</th>
                                        <th>Количество записей</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($service = $popular_services->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($service['name']); ?></td>
                                        <td><?php echo $service['appointment_count']; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="text-align: center; color: #6c757d;">Нет данных</p>
                        <?php endif;
                        $db->close();
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3>📋 Статусы записей</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $db = new Database();
                        $appointment_statuses = $db->query("
                            SELECT status, COUNT(*) as count 
                            FROM Appointments 
                            GROUP BY status
                        ");
                        
                        if ($appointment_statuses->num_rows > 0): ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Статус</th>
                                        <th>Количество</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $status_labels = [
                                        'scheduled' => 'Запланирован',
                                        'completed' => 'Завершен',
                                        'cancelled' => 'Отменен',
                                        'no_show' => 'Не явился'
                                    ];
                                    
                                    while ($status = $appointment_statuses->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="status-badge status-<?php echo $status['status']; ?>">
                                                <?php echo $status_labels[$status['status']] ?? $status['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $status['count']; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="text-align: center; color: #6c757d;">Нет данных</p>
                        <?php endif;
                        $db->close();
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Экспорт данных -->
        <div class="card">
            <div class="card-header">
                <h3>📤 Экспорт данных</h3>
            </div>
            <div class="card-body">
                <div style="text-align: center;">
                    <a href="export_patients.php" class="btn btn-primary">Экспорт пациентов</a>
                    <a href="export_doctors.php" class="btn btn-success">Экспорт врачей</a>
                    <a href="export_appointments.php" class="btn btn-warning">Экспорт записей</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Прокрутка вверх при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            window.scrollTo(0, 0);
        });
    </script>
</body>
</html>