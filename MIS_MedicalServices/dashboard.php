<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Получаем статистику
$db = new Database();
$stats = [];

try {
    // Общая статистика
    $patients_count = $db->query("SELECT COUNT(*) as count FROM Patients")->fetch_assoc()['count'];
    $doctors_count = $db->query("SELECT COUNT(*) as count FROM Doctors")->fetch_assoc()['count'];
    $today_appointments = $db->query("SELECT COUNT(*) as count FROM Appointments WHERE DATE(appointment_date) = CURDATE()")->fetch_assoc()['count'];
    $services_count = $db->query("SELECT COUNT(*) as count FROM MedicalServices")->fetch_assoc()['count'];
    
    $stats = [
        'patients' => $patients_count,
        'doctors' => $doctors_count,
        'today_appointments' => $today_appointments,
        'services' => $services_count
    ];
    
    // Статистика для врача
    if ($_SESSION['is_doctor']) {
        $my_appointments = $db->query(
            "SELECT COUNT(*) as count FROM Appointments WHERE doctor_id = ? AND DATE(appointment_date) = CURDATE()",
            [$_SESSION['doctor_id']]
        )->fetch_assoc()['count'];
        $stats['my_appointments'] = $my_appointments;
    }
    
    // Статистика для пациента (только его записи)
    if ($_SESSION['role'] === 'Пациент') {
        $patient_appointments = $db->query(
            "SELECT COUNT(*) as count FROM Appointments a 
             JOIN Patients p ON a.patient_id = p.id 
             WHERE p.user_id = ? AND DATE(a.appointment_date) = CURDATE()",
            [$_SESSION['user_id']]
        )->fetch_assoc()['count'];
        $stats['my_appointments'] = $patient_appointments;
    }
    
} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Главная панель - МЕДИС</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .service-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .service-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        
        .service-content {
            padding: 20px;
        }
        
        .service-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 10px;
        }
        
        .service-price {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .current-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007BFF;
        }
        
        .old-price {
            font-size: 1.1rem;
            color: #6c757d;
            text-decoration: line-through;
        }
        
        .service-features {
            margin: 15px 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            font-size: 14px;
            color: #495057;
        }
        
        .feature-marker {
            color: #28a745;
            font-weight: bold;
        }
        
        .service-action {
            margin-top: 15px;
        }
        
        .btn-book {
            width: 100%;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-book:hover {
            background: linear-gradient(135deg, #218838, #1e7e34);
            transform: translateY(-2px);
        }
        
        .specialization-badge {
            background: #007BFF;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Клавиша пропуска для доступности -->
    <a href="#main-content" class="skip-link">Перейти к основному содержанию</a>

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
                <a href="logout.php" class="btn btn-secondary btn-sm" id="logoutBtn">Выйти (L)</a>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="nav-menu" id="main-nav">
        <div class="nav-content">
            <a href="dashboard.php" class="nav-item active" data-shortcut="1">Главная (1)</a>
            
            <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                <a href="patients.php" class="nav-item" data-shortcut="2">Пациенты (2)</a>
            <?php endif; ?>
            
            <a href="appointments.php" class="nav-item" data-shortcut="3">Записи (3)</a>
            <a href="services.php" class="nav-item" data-shortcut="4">Услуги (4)</a>
            
            <?php if ($_SESSION['role'] === 'Администратор'): ?>
                <a href="reports.php" class="nav-item" data-shortcut="5">Отчеты (5)</a>
                <a href="doctors.php" class="nav-item" data-shortcut="6">Врачи (6)</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container" id="main-content" tabindex="-1">
        <!-- Заголовок страницы -->
        <div style="margin-bottom: 30px;">
            <h1 style="color: #212529; margin-bottom: 10px;">Главная панель управления</h1>
            <p style="color: #6c757d;">
                <?php if ($_SESSION['role'] === 'Пациент'): ?>
                    Личный кабинет пациента
                <?php elseif ($_SESSION['role'] === 'Врач'): ?>
                    Рабочее место врача
                <?php else: ?>
                    Панель администратора
                <?php endif; ?>
            </p>
            <p style="color: #6c757d; font-size: 12px; margin-top: 5px;">
                💡 <strong>Горячие клавиши:</strong> 
                1-<?php echo $_SESSION['role'] === 'Администратор' ? '6' : '4'; ?> - навигация,
                N - новая запись, 
                <?php if ($_SESSION['role'] !== 'Пациент'): ?>P - пациенты, S - поиск, <?php endif; ?>
                L - выход
            </p>
        </div>

        <!-- Популярные услуги -->
        <div class="card">
            <div class="card-header">
                <h3>🏥 Популярные медицинские услуги</h3>
            </div>
            <div class="card-body">
                <div class="services-grid">
                    <!-- Услуга 1: Эндоскопические операции -->
                    <div class="service-card">
                        <div class="service-image">
                            🩺
                        </div>
                        <div class="service-content">
                            <div class="specialization-badge">Эндоскопия</div>
                            <div class="service-title">Эндоскопические операции</div>
                            <div class="service-price">
                                <span class="current-price">45 000 ₽</span>
                                <span class="old-price">52 000 ₽</span>
                            </div>
                            <div class="service-features">
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Малоинвазивное вмешательство
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Быстрое восстановление
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Современное оборудование
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Опытные хирурги
                                </div>
                            </div>
                            <div class="service-action">
                                <button class="btn-book" onclick="window.location.href='appointments.php?action=create&service=endoscopy'">
                                    Записаться на консультацию
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Услуга 2: Лечение в стационаре (1-местная палата) -->
                    <div class="service-card">
                        <div class="service-image">
                            🏨
                        </div>
                        <div class="service-content">
                            <div class="specialization-badge">Стационар</div>
                            <div class="service-title">1-местная палата</div>
                            <div class="service-price">
                                <span class="current-price">12 950 ₽</span>
                                <span class="old-price">18 500 ₽</span>
                            </div>
                            <div class="service-features">
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    3-х разовое питание
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Телевизор
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Санузел в палате
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Холодильник
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Противопролежневые матрасы
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Кондиционер
                                </div>
                            </div>
                            <div class="service-action">
                                <button class="btn-book" onclick="window.location.href='appointments.php?action=create&service=hospital1'">
                                    Забронировать палату
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Услуга 3: Лечение в стационаре (2-местная палата) -->
                    <div class="service-card">
                        <div class="service-image">
                            🏥
                        </div>
                        <div class="service-content">
                            <div class="specialization-badge">Стационар</div>
                            <div class="service-title">2-местная палата</div>
                            <div class="service-price">
                                <span class="current-price">9 730 ₽</span>
                                <span class="old-price">13 900 ₽</span>
                            </div>
                            <div class="service-features">
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    3-х разовое питание
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Телевизор
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Санузел в палате
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Холодильник
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Противопролежневые матрасы
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Кондиционер
                                </div>
                            </div>
                            <div class="service-action">
                                <button class="btn-book" onclick="window.location.href='appointments.php?action=create&service=hospital2'">
                                    Забронировать палату
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Услуга 4: Диагностика МРТ -->
                    <div class="service-card">
                        <div class="service-image">
                            🔍
                        </div>
                        <div class="service-content">
                            <div class="specialization-badge">Диагностика</div>
                            <div class="service-title">МРТ всего тела</div>
                            <div class="service-price">
                                <span class="current-price">25 000 ₽</span>
                                <span class="old-price">30 000 ₽</span>
                            </div>
                            <div class="service-features">
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Современный томограф 3.0 Тесла
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Расшифровка опытным рентгенологом
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Результаты в течение 2 часов
                                </div>
                                <div class="feature-item">
                                    <span class="feature-marker">✓</span>
                                    Запись на диск
                                </div>
                            </div>
                            <div class="service-action">
                                <button class="btn-book" onclick="window.location.href='appointments.php?action=create&service=mri'">
                                    Записаться на диагностику
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="services.php" class="btn btn-primary">Посмотреть все услуги</a>
                </div>
            </div>
        </div>

        <!-- Статистика -->
        <div class="card">
            <div class="card-header">
                <h3>📊 Статистика</h3>
            </div>
            <div class="card-body">
                <div class="stats-grid">
                    <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['patients']; ?></div>
                        <div class="stat-label">Пациентов</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['doctors']; ?></div>
                        <div class="stat-label">Врачей</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['today_appointments']; ?></div>
                        <div class="stat-label">
                            <?php if ($_SESSION['role'] === 'Пациент'): ?>
                                Моих записей сегодня
                            <?php else: ?>
                                Записей сегодня
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['services']; ?></div>
                        <div class="stat-label">Медицинских услуг</div>
                    </div>
                    
                    <?php if (isset($stats['my_appointments'])): ?>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['my_appointments']; ?></div>
                        <div class="stat-label">
                            <?php if ($_SESSION['role'] === 'Пациент'): ?>
                                Мои сегодня
                            <?php else: ?>
                                Мои записи сегодня
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Быстрые действия -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3>⚡ Быстрые действия</h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="appointments.php?action=create" class="action-btn" id="newAppointmentBtn" data-shortcut="N">
                                <div class="action-icon">➕</div>
                                <div class="action-text">
                                    <?php if ($_SESSION['role'] === 'Пациент'): ?>
                                        Записаться на прием (N)
                                    <?php else: ?>
                                        Новая запись (N)
                                    <?php endif; ?>
                                </div>
                            </a>
                            
                            <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                            <a href="patients.php?action=create" class="action-btn" id="newPatientBtn" data-shortcut="P">
                                <div class="action-icon">👤</div>
                                <div class="action-text">Добавить пациента (P)</div>
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($_SESSION['role'] === 'Администратор'): ?>
                            <a href="doctors.php?action=create" class="action-btn" id="newDoctorBtn">
                                <div class="action-icon">👨‍⚕️</div>
                                <div class="action-text">Добавить врача</div>
                            </a>
                            <?php endif; ?>
                            
                            <a href="appointments.php" class="action-btn">
                                <div class="action-icon">📅</div>
                                <div class="action-text">
                                    <?php if ($_SESSION['role'] === 'Пациент'): ?>
                                        Мои записи
                                    <?php else: ?>
                                        Расписание
                                    <?php endif; ?>
                                </div>
                            </a>
                            
                            <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                            <a href="patients.php" class="action-btn" id="searchPatientBtn" data-shortcut="S">
                                <div class="action-icon">🔍</div>
                                <div class="action-text">Поиск пациента (S)</div>
                            </a>
                            <?php else: ?>
                            <a href="services.php" class="action-btn">
                                <div class="action-icon">🏥</div>
                                <div class="action-text">Услуги клиники</div>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Последние записи -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <h3>
                            <?php if ($_SESSION['role'] === 'Пациент'): ?>
                                📋 Мои ближайшие записи
                            <?php else: ?>
                                🗓️ Ближайшие записи
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $db = new Database();
                        
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
                                WHERE p.user_id = ? AND a.appointment_date >= NOW() 
                                ORDER BY a.appointment_date 
                                LIMIT 5
                            ";
                            $appointments = $db->query($sql, [$_SESSION['user_id']]);
                        } elseif ($_SESSION['is_doctor']) {
                            // Для врача - только его записи
                            $sql = "
                                SELECT a.*, p.first_name, p.last_name, p.phone, ms.name as service_name 
                                FROM Appointments a 
                                JOIN Patients p ON a.patient_id = p.id 
                                JOIN MedicalServices ms ON a.service_id = ms.id 
                                WHERE a.doctor_id = ? AND a.appointment_date >= NOW() 
                                ORDER BY a.appointment_date 
                                LIMIT 5
                            ";
                            $appointments = $db->query($sql, [$_SESSION['doctor_id']]);
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
                                WHERE a.appointment_date >= NOW() 
                                ORDER BY a.appointment_date 
                                LIMIT 5
                            ";
                            $appointments = $db->query($sql);
                        }
                        ?>
                        
                        <?php if ($appointments && $appointments->num_rows > 0): ?>
                            <table class="table" id="appointmentsTable">
                                <thead>
                                    <tr>
                                        <th tabindex="0">Время</th>
                                        <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                                        <th tabindex="0">Пациент</th>
                                        <?php endif; ?>
                                        <?php if (!$_SESSION['is_doctor'] && $_SESSION['role'] !== 'Пациент'): ?>
                                        <th tabindex="0">Врач</th>
                                        <?php endif; ?>
                                        <th tabindex="0">Услуга</th>
                                        <th tabindex="0">Статус</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($appointment = $appointments->fetch_assoc()): ?>
                                    <tr tabindex="0">
                                        <td><?php echo date('H:i', strtotime($appointment['appointment_date'])); ?></td>
                                        <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                                        <td><?php echo htmlspecialchars($appointment['last_name'] . ' ' . $appointment['first_name']); ?></td>
                                        <?php endif; ?>
                                        <?php if (!$_SESSION['is_doctor'] && $_SESSION['role'] !== 'Пациент'): ?>
                                        <td><?php echo htmlspecialchars($appointment['doctor_last_name'] . ' ' . $appointment['doctor_first_name']); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
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
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="text-align: center; color: #6c757d; padding: 20px;">
                                <?php if ($_SESSION['role'] === 'Пациент'): ?>
                                    У вас нет запланированных записей
                                <?php else: ?>
                                    Нет запланированных записей
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                        <?php if (isset($db)) $db->close(); ?>
                        
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="appointments.php" class="btn btn-primary" id="allAppointmentsBtn">
                                <?php if ($_SESSION['role'] === 'Пациент'): ?>
                                    Все мои записи
                                <?php else: ?>
                                    Все записи
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Система горячих клавиш
        document.addEventListener('keydown', function(e) {
            // Игнорируем комбинации с Ctrl, Alt, Shift
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
                <?php endif; ?>
                case '3':
                    e.preventDefault();
                    window.location.href = 'appointments.php';
                    break;
                case '4':
                    e.preventDefault();
                    window.location.href = 'services.php';
                    break;
                <?php if ($_SESSION['role'] === 'Администратор'): ?>
                case '5':
                    e.preventDefault();
                    window.location.href = 'reports.php';
                    break;
                case '6':
                    e.preventDefault();
                    window.location.href = 'doctors.php';
                    break;
                <?php endif; ?>
                case 'N':
                    e.preventDefault();
                    document.getElementById('newAppointmentBtn').click();
                    break;
                <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                case 'P':
                    e.preventDefault();
                    document.getElementById('newPatientBtn').click();
                    break;
                case 'S':
                    e.preventDefault();
                    document.getElementById('searchPatientBtn').click();
                    break;
                <?php endif; ?>
                case 'L':
                    e.preventDefault();
                    document.getElementById('logoutBtn').click();
                    break;
                case 'ESCAPE':
                    // Фокус на основном контенте
                    document.getElementById('main-content').focus();
                    break;
            }
        });

        // Улучшенная навигация по таблице
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.getElementById('appointmentsTable');
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach((row, index) => {
                    row.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            // Здесь можно добавить действие при выборе записи
                            console.log('Selected appointment:', index);
                        }
                    });
                });
            }

            // Автофокус на основном контенте
            document.getElementById('main-content').focus();
        });

        // Всплывающая подсказка при первом посещении
        if (!localStorage.getItem('keyboardHelpShown')) {
            setTimeout(() => {
                let message = '💡 Подсказка по клавиатурной навигации:\n\n' +
                      '1 - Главная\n';
                
                <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                message += '2 - Пациенты\n';
                <?php endif; ?>
                
                message += '3 - Записи\n4 - Услуги\n';
                
                <?php if ($_SESSION['role'] === 'Администратор'): ?>
                message += '5 - Отчеты\n6 - Врачи\n';
                <?php endif; ?>
                
                message += 'N - <?php echo $_SESSION['role'] === 'Пациент' ? 'Записаться на прием' : 'Новая запись'; ?>\n';
                
                <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                message += 'P - Добавить пациента\nS - Поиск пациента\n';
                <?php endif; ?>
                
                message += 'L - Выйти из системы\nESC - Вернуться к основному содержанию\n\n' +
                      'Эта подсказка больше не появится.';
                
                alert(message);
                localStorage.setItem('keyboardHelpShown', 'true');
            }, 1000);
        }
    </script>
</body>
</html>