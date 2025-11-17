<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
// Только врачи и администраторы имеют доступ к управлению пациентами
$auth->checkAccess('Врач');

$db = new Database();
$patients = [];

// Получение списка пациентов
$result = $db->query("
    SELECT p.*, u.login 
    FROM Patients p 
    JOIN Users u ON p.user_id = u.id 
    ORDER BY p.last_name, p.first_name
");

while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пациентами - МЕДИС</title>
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
        <a href="patients.php" class="nav-item active">Пациенты</a>
        <a href="appointments.php" class="nav-item">Записи</a>
        <a href="services.php" class="nav-item">Услуги</a>
        <?php if ($_SESSION['role'] === 'Администратор'): ?>
            <a href="reports.php" class="nav-item">Отчеты</a>
            <a href="doctors.php" class="nav-item">Врачи</a>
        <?php endif; ?>
    </div>
</nav>

    <div class="container">
        <h1>Управление пациентами</h1>
        
        <div class="card">
            <div class="card-header">
                <h3>👥 Список пациентов</h3>
                <a href="?action=create" class="btn btn-success">Добавить пациента</a>
            </div>
            <div class="card-body">
                <?php if (count($patients) > 0): ?>
                    <table class="table" id="patientsTable">
                        <thead>
                            <tr>
                                <th data-sort="last_name">Фамилия</th>
                                <th data-sort="first_name">Имя</th>
                                <th data-sort="middle_name">Отчество</th>
                                <th data-sort="birth_date">Дата рождения</th>
                                <th data-sort="gender">Пол</th>
                                <th data-sort="phone">Телефон</th>
                                <th data-sort="insurance_policy">Полис</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patients as $patient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['middle_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($patient['birth_date']); ?></td>
                                <td>
                                    <?php if ($patient['gender'] === 'M'): ?>
                                        Мужской
                                    <?php else: ?>
                                        Женский
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td><?php echo htmlspecialchars($patient['insurance_policy']); ?></td>
                                <td>
                                    <a href="?action=edit&id=<?php echo $patient['id']; ?>" class="btn btn-primary btn-sm">Редактировать</a>
                                    <a href="?action=view&id=<?php echo $patient['id']; ?>" class="btn btn-secondary btn-sm">Просмотр</a>
                                    <?php if ($_SESSION['role'] === 'Администратор'): ?>
                                        <a href="?action=delete&id=<?php echo $patient['id']; ?>" class="btn btn-danger btn-sm" 
                                           onclick="return confirm('Вы уверены, что хотите удалить пациента?')">Удалить</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d; padding: 20px;">Пациенты не найдены</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Поиск пациента -->
        <div class="card">
            <div class="card-header">
                <h3>🔍 Поиск пациента</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Фамилия</label>
                            <input type="text" class="form-control" name="last_name" placeholder="Введите фамилию">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Имя</label>
                            <input type="text" class="form-control" name="first_name" placeholder="Введите имя">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Полис ОМС</label>
                            <input type="text" class="form-control" name="insurance_policy" placeholder="Номер полиса">
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Найти</button>
                        <a href="patients.php" class="btn btn-secondary">Сбросить</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
    <script>
        // Горячие клавиши для patients.php
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.altKey || e.shiftKey) return;
            
            const key = e.key.toUpperCase();
            
            switch(key) {
                case '1':
                    e.preventDefault();
                    window.location.href = 'dashboard.php';
                    break;
                case '2':
                    e.preventDefault();
                    window.location.href = 'patients.php';
                    break;
                case '3':
                    e.preventDefault();
                    window.location.href = 'doctors.php';
                    break;
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

        // Автофокус на поиске при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="last_name"]');
            if (searchInput) {
                searchInput.focus();
            }
        });
    </script>
</body>
</html>