<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$system = new MedicalSystem();
$db = new Database();

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$result = [];

switch ($action) {
    case 'doctor_stats':
        $doctorId = $_POST['doctor_id'];
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        
        $result['avg_patients'] = $system->getAveragePatientsPerDay($doctorId, $startDate, $endDate);
        $result['schedule'] = $system->getDoctorSchedule($doctorId, $startDate);
        break;
        
    case 'popular_services':
        $month = $_POST['month'];
        $year = $_POST['year'];
        $result['popular_service'] = $system->getMostPopularService($month, $year);
        break;
        
    case 'revenue':
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $result['revenue'] = $system->calculateRevenue($startDate, $endDate);
        break;
        
    case 'overdue':
        $result['overdue'] = $system->getOverdueAppointments();
        break;
}

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика - МЕДИС</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h1>Аналитика и отчеты</h1>
        
        <?php if ($action === 'doctor_stats'): ?>
            <div class="card">
                <div class="card-header">Статистика врача</div>
                <div class="card-body">
                    <p>Среднее количество пациентов в день: <strong><?php echo round($result['avg_patients'], 2); ?></strong></p>
                    
                    <h3>График занятости</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Количество приемов</th>
                                <th>Время приемов</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['schedule'] as $day): ?>
                            <tr>
                                <td><?php echo $day['date']; ?></td>
                                <td><?php echo $day['appointments_count']; ?></td>
                                <td><?php echo str_replace(',', ', ', $day['times']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
        <?php elseif ($action === 'popular_services'): ?>
            <div class="card">
                <div class="card-header">Самая популярная услуга</div>
                <div class="card-body">
                    <?php if (!empty($result['popular_service'])): ?>
                        <p>Услуга: <strong><?php echo $result['popular_service']['name']; ?></strong></p>
                        <p>Количество назначений: <strong><?php echo $result['popular_service']['count']; ?></strong></p>
                    <?php else: ?>
                        <p>Данные не найдены</p>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php elseif ($action === 'revenue'): ?>
            <div class="card">
                <div class="card-header">Доход за период</div>
                <div class="card-body">
                    <p>Общий доход: <strong><?php echo number_format($result['revenue'], 2, '.', ' '); ?> руб.</strong></p>
                </div>
            </div>
            
        <?php elseif ($action === 'overdue'): ?>
            <div class="card">
                <div class="card-header">Просроченные записи (без диагноза)</div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Дата приема</th>
                                <th>Пациент</th>
                                <th>Врач</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($result['overdue'] as $appointment): ?>
                            <tr>
                                <td><?php echo $appointment['id']; ?></td>
                                <td><?php echo $appointment['appointment_date']; ?></td>
                                <td><?php echo $appointment['last_name'] . ' ' . $appointment['first_name']; ?></td>
                                <td><?php echo $appointment['doctor_name']; ?></td>
                                <td>
                                    <a href="appointments.php?action=diagnosis&id=<?php echo $appointment['id']; ?>" class="btn btn-success">
                                        Внести диагноз
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
        <a href="dashboard.php" class="btn btn-primary">Назад к панели управления</a>
    </div>
</body>
</html>