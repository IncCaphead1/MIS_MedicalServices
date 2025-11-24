<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
// Только администраторы могут добавлять услуги
$auth->checkAccess('Администратор');

$db = new Database();
$error = '';
$success = '';

// Обработка формы добавления услуги
if ($_POST && isset($_POST['create_service'])) {
    try {
        $name = trim($_POST['name']);
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category']);
        $base_price = floatval($_POST['base_price']);
        $duration = intval($_POST['duration']);
        
        // Валидация
        if (empty($name) || empty($category) || empty($base_price) || empty($duration)) {
            throw new Exception("Заполните все обязательные поля");
        }
        
        if ($base_price <= 0) {
            throw new Exception("Цена должна быть больше 0");
        }
        
        if ($duration <= 0) {
            throw new Exception("Длительность должна быть больше 0 минут");
        }
        
        // Проверяем, не существует ли уже услуга с таким названием
        $check_service = $db->query("SELECT id FROM MedicalServices WHERE name = ?", [$name]);
        if ($check_service->num_rows > 0) {
            throw new Exception("Услуга с таким названием уже существует");
        }
        
        // Создаем услугу
        $db->query(
            "INSERT INTO MedicalServices (name, description, category, base_price, duration, is_available) 
             VALUES (?, ?, ?, ?, ?, 1)",
            [$name, $description, $category, $base_price, $duration]
        );
        
        $success = "Услуга успешно добавлена!";
        
    } catch (Exception $e) {
        $error = "Ошибка при добавлении услуги: " . $e->getMessage();
    }
}

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить услугу - МЕДИС</title>
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
                <a href="services.php" class="btn btn-secondary btn-sm">Назад к услугам</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>🏥 Добавить медицинскую услугу</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <div style="text-align: center; margin: 20px 0;">
                <a href="services.php" class="btn btn-primary">Вернуться к списку услуг</a>
                <a href="services_create.php" class="btn btn-success">Добавить еще одну услугу</a>
            </div>
        <?php else: ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-8">
                            <div class="form-group">
                                <label class="form-label">Название услуги *</label>
                                <input type="text" class="form-control" name="name" required 
                                       placeholder="Например: Консультация терапевта">
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="form-group">
                                <label class="form-label">Категория *</label>
                                <select class="form-control" name="category" required>
                                    <option value="">Выберите категорию</option>
                                    <option value="Терапия">Терапия</option>
                                    <option value="Хирургия">Хирургия</option>
                                    <option value="Кардиология">Кардиология</option>
                                    <option value="Неврология">Неврология</option>
                                    <option value="Диагностика">Диагностика</option>
                                    <option value="Лаборатория">Лаборатория</option>
                                    <option value="Стационар">Стационар</option>
                                    <option value="Эндоскопия">Эндоскопия</option>
                                    <option value="Другое">Другое</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Описание услуги</label>
                        <textarea class="form-control" name="description" rows="3" 
                                  placeholder="Подробное описание услуги..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Базовая цена (руб) *</label>
                                <input type="number" class="form-control" name="base_price" 
                                       min="0" step="0.01" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label">Длительность (минут) *</label>
                                <input type="number" class="form-control" name="duration" 
                                       min="1" max="480" required placeholder="30">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" name="create_service" class="btn btn-primary">Добавить услугу</button>
                        <a href="services.php" class="btn btn-secondary">Отмена</a>
                    </div>
                </form>
            </div>
        </div>
        
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.scrollTo(0, 0);
        });

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