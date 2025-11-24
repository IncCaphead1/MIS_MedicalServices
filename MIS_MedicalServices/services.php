<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
// Все авторизованные пользователи имеют доступ к просмотру услуг
if ($_SESSION['role'] === 'Пациент') {
    $auth->checkAccess('Пациент');
} else {
    $auth->checkAccess('Врач');
}

$db = new Database();
$services = [];

// Получение списка услуг
$result = $db->query("SELECT * FROM MedicalServices WHERE is_available = 1 ORDER BY category, name");

while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Медицинские услуги - МЕДИС</title>
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
            height: 160px;
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
            line-height: 1.3;
        }
        
        .service-category {
            background: #007BFF;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .service-description {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .service-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }
        
        .service-price {
            font-size: 1.4rem;
            font-weight: bold;
            color: #007BFF;
        }
        
        .service-duration {
            color: #6c757d;
            font-size: 14px;
            background: #f8f9fa;
            padding: 4px 10px;
            border-radius: 15px;
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
        
        .category-section {
            margin-bottom: 30px;
        }
        
        .category-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #212529;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007BFF;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
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
        <a href="appointments.php" class="nav-item">Записи</a>
        <a href="services.php" class="nav-item active">Услуги</a>
        <?php if ($_SESSION['role'] === 'Администратор'): ?>
            <a href="reports.php" class="nav-item">Отчеты</a>
            <a href="doctors.php" class="nav-item">Врачи</a>
        <?php endif; ?>
    </div>
</nav>

    <div class="container">
        <h1>🏥 Медицинские услуги</h1>
        
        <div class="card">
            <div class="card-header">
                <h3>📋 Каталог медицинских услуг</h3>
                <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                    <a href="services_create.php" class="btn btn-success">Добавить услугу</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (count($services) > 0): ?>
                    <?php
                    // Группируем услуги по категориям
                    $categories = [];
                    foreach ($services as $service) {
                        $category = $service['category'];
                        if (!isset($categories[$category])) {
                            $categories[$category] = [];
                        }
                        $categories[$category][] = $service;
                    }
                    ?>
                    
                    <?php foreach ($categories as $category => $categoryServices): ?>
                    <div class="category-section">
                        <h3 class="category-title"><?php echo htmlspecialchars($category); ?></h3>
                        <div class="services-grid">
                            <?php foreach ($categoryServices as $service): ?>
                            <div class="service-card">
                                <div class="service-image">
                                    <?php 
                                    // Иконки в зависимости от категории
                                    $icons = [
                                        'Терапия' => '🩺',
                                        'Хирургия' => '🔪',
                                        'Кардиология' => '❤️',
                                        'Неврология' => '🧠',
                                        'Диагностика' => '🔍',
                                        'Лаборатория' => '🧪',
                                        'Стационар' => '🏨',
                                        'Эндоскопия' => '📹'
                                    ];
                                    echo $icons[$service['category']] ?? '🏥';
                                    ?>
                                </div>
                                <div class="service-content">
                                    <div class="service-category"><?php echo htmlspecialchars($service['category']); ?></div>
                                    <div class="service-title"><?php echo htmlspecialchars($service['name']); ?></div>
                                    
                                    <?php if (!empty($service['description'])): ?>
                                    <div class="service-description">
                                        <?php echo htmlspecialchars($service['description']); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="service-details">
                                        <div class="service-price">
                                            <?php echo number_format($service['base_price'], 0, '.', ' '); ?> ₽
                                        </div>
                                        <div class="service-duration">
                                            <?php echo $service['duration']; ?> мин.
                                        </div>
                                    </div>
                                    
                                    <div class="service-action">
                                        <button class="btn-book" onclick="window.location.href='appointments.php?action=create&service_id=<?php echo $service['id']; ?>'">
                                            Записаться на прием
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🏥</div>
                        <h3>Услуги не найдены</h3>
                        <p>В данный момент нет доступных медицинских услуг.</p>
                        <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                            <a href="?action=create" class="btn btn-primary">Добавить первую услугу</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Поиск услуг -->
        <div class="card">
            <div class="card-header">
                <h3>🔍 Поиск услуг</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Название услуги</label>
                            <input type="text" class="form-control" name="name" placeholder="Введите название услуги">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Категория</label>
                            <select class="form-control" name="category">
                                <option value="">Все категории</option>
                                <option value="Терапия">Терапия</option>
                                <option value="Хирургия">Хирургия</option>
                                <option value="Кардиология">Кардиология</option>
                                <option value="Неврология">Неврология</option>
                                <option value="Диагностика">Диагностика</option>
                                <option value="Лаборатория">Лаборатория</option>
                                <option value="Стационар">Стационар</option>
                                <option value="Эндоскопия">Эндоскопия</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Макс. цена</label>
                            <input type="number" class="form-control" name="max_price" placeholder="До какой цены">
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Найти услуги</button>
                        <a href="services.php" class="btn btn-secondary">Сбросить фильтры</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Информация о записи -->
        <div class="card">
            <div class="card-header">
                <h3>💡 Как записаться на прием</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-3" style="text-align: center;">
                        <div style="font-size: 36px; margin-bottom: 10px;">1️⃣</div>
                        <h4>Выберите услугу</h4>
                        <p style="font-size: 14px; color: #6c757d;">Нажмите кнопку "Записаться на прием" на нужной услуге</p>
                    </div>
                    <div class="col-3" style="text-align: center;">
                        <div style="font-size: 36px; margin-bottom: 10px;">2️⃣</div>
                        <h4>Выберите врача</h4>
                        <p style="font-size: 14px; color: #6c757d;">Подберите специалиста и удобное время</p>
                    </div>
                    <div class="col-3" style="text-align: center;">
                        <div style="font-size: 36px; margin-bottom: 10px;">3️⃣</div>
                        <h4>Подтвердите запись</h4>
                        <p style="font-size: 14px; color: #6c757d;">Заполните необходимые данные и подтвердите запись</p>
                    </div>
                    <div class="col-3" style="text-align: center;">
                        <div style="font-size: 36px; margin-bottom: 10px;">4️⃣</div>
                        <h4>Приходите на прием</h4>
                        <p style="font-size: 14px; color: #6c757d;">Не забудьте взять с собой необходимые документы</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Горячие клавиши для services.php
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
                    <?php if ($_SESSION['role'] !== 'Пациент'): ?>
                    window.location.href = '?action=create';
                    <?php endif; ?>
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
            const searchInput = document.querySelector('input[name="name"]');
            if (searchInput) {
                searchInput.focus();
            }
        });

        // Плавная прокрутка к категориям
        function scrollToCategory(category) {
            const element = document.querySelector(`[data-category="${category}"]`);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth' });
            }
        }
    </script>
</body>
</html>