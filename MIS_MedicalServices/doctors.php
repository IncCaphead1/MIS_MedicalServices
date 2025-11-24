<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
// Только администраторы имеют доступ к управлению врачами
$auth->checkAccess('Администратор');

$db = new Database();
$doctors = [];

// Получение списка врачей
$result = $db->query("
    SELECT d.*, u.login 
    FROM Doctors d 
    JOIN Users u ON d.user_id = u.id 
    ORDER BY d.last_name, d.first_name
");

while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление врачами - МЕДИС</title>
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
            <a href="doctors.php" class="nav-item active">Врачи</a>
            <a href="appointments.php" class="nav-item">Записи</a>
            <a href="services.php" class="nav-item">Услуги</a>
            <?php if ($_SESSION['role'] === 'Администратор'): ?>
                <a href="reports.php" class="nav-item">Отчеты</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container">
        <h1>👨‍⚕️ Управление врачами</h1>
        
        <div class="card">
            <div class="card-header">
                <h3>📋 Список врачей</h3>
                <a href="doctors_create.php" class="btn btn-success">Добавить врача</a>
            </div>
            <div class="card-body">
                <?php if (count($doctors) > 0): ?>
                    <table class="table" id="doctorsTable">
                        <thead>
                            <tr>
                                <th data-sort="last_name">Фамилия</th>
                                <th data-sort="first_name">Имя</th>
                                <th data-sort="middle_name">Отчество</th>
                                <th data-sort="specialization">Специализация</th>
                                <th data-sort="license_number">Лицензия</th>
                                <th data-sort="experience_years">Опыт (лет)</th>
                                <th data-sort="phone">Телефон</th>
                                <th data-sort="email">Email</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctors as $doctor): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($doctor['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($doctor['first_name']); ?></td>
                                <td><?php echo htmlspecialchars($doctor['middle_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                                <td><?php echo htmlspecialchars($doctor['license_number']); ?></td>
                                <td><?php echo htmlspecialchars($doctor['experience_years']); ?></td>
                                <td><?php echo htmlspecialchars($doctor['phone']); ?></td>
                                <td><?php echo htmlspecialchars($doctor['email']); ?></td>
                                <td>
                                    <?php if ($doctor['is_active']): ?>
                                        <span class="status-badge status-completed">Активен</span>
                                    <?php else: ?>
                                        <span class="status-badge status-cancelled">Неактивен</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-secondary btn-sm" disabled>Просмотр</button>
                                    <button class="btn btn-primary btn-sm" disabled>Редактировать</button>
                                    <button class="btn btn-danger btn-sm" disabled>Удалить</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #6c757d; padding: 20px;">Врачи не найдены</p>
                    <div style="text-align: center;">
                        <a href="doctors_create.php" class="btn btn-success">Добавить первого врача</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Статистика врачей -->
        <div class="row">
            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h3>📊 Статистика</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $db = new Database();
                        $total_doctors = $db->query("SELECT COUNT(*) as count FROM Doctors")->fetch_assoc()['count'];
                        $active_doctors = $db->query("SELECT COUNT(*) as count FROM Doctors WHERE is_active = 1")->fetch_assoc()['count'];
                        $db->close();
                        ?>
                        <p>Всего врачей: <strong><?php echo $total_doctors; ?></strong></p>
                        <p>Активных врачей: <strong><?php echo $active_doctors; ?></strong></p>
                        <p>Неактивных: <strong><?php echo $total_doctors - $active_doctors; ?></strong></p>
                    </div>
                </div>
            </div>

            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h3>🔍 Поиск врача</h3>
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
                                    <label class="form-label">Специализация</label>
                                    <input type="text" class="form-control" name="specialization" placeholder="Специализация">
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="form-group">
                                    <label class="form-label">Статус</label>
                                    <select class="form-control" name="is_active">
                                        <option value="">Все</option>
                                        <option value="1">Активные</option>
                                        <option value="0">Неактивные</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Найти</button>
                                <a href="doctors.php" class="btn btn-secondary">Сбросить</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Автофокус на поиске при загрузке
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="last_name"]');
            if (searchInput) {
                searchInput.focus();
            }
        });
    </script>

    <script>
        // Прокрутка вверх при загрузке страницы и переходе по ссылкам
        document.addEventListener('DOMContentLoaded', function() {
            // Прокрутка вверх при загрузке
            window.scrollTo(0, 0);
            
            // Прокрутка вверх при клике на ссылки навигации
            document.querySelectorAll('a[href*=".php"]').forEach(link => {
                link.addEventListener('click', function() {
                    setTimeout(() => {
                        window.scrollTo(0, 0);
                    }, 100);
                });
            });
        });

        // Также прокрутка вверх при нажатии кнопки "Назад" в браузере
        window.addEventListener('pageshow', function() {
            window.scrollTo(0, 0);
        });
    </script>
</body>
</html>