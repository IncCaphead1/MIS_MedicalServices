<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
// Только врачи и администраторы имеют доступ к управлению пациентами
$auth->checkAccess('Врач');

$db = new Database();
$patients = [];

// Обработка параметров поиска
$search_last_name = $_GET['last_name'] ?? '';
$search_first_name = $_GET['first_name'] ?? '';
$search_insurance = $_GET['insurance_policy'] ?? '';

// Построение запроса с учетом поисковых параметров
$query = "
    SELECT p.*, u.login 
    FROM Patients p 
    JOIN Users u ON p.user_id = u.id 
    WHERE 1=1
";
$params = [];

if (!empty($search_last_name)) {
    $query .= " AND p.last_name LIKE ?";
    $params[] = "%$search_last_name%";
}

if (!empty($search_first_name)) {
    $query .= " AND p.first_name LIKE ?";
    $params[] = "%$search_first_name%";
}

if (!empty($search_insurance)) {
    $query .= " AND p.insurance_policy LIKE ?";
    $params[] = "%$search_insurance%";
}

$query .= " ORDER BY p.last_name, p.first_name";

// Получение списка пациентов
$result = $db->query($query, $params);

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
    <style>
        .action-buttons {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        .btn-info {
            background-color: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background-color: #138496;
        }
        .search-summary {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border-left: 4px solid #007BFF;
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
        
        <!-- Статистика поиска -->
        <?php if (!empty($search_last_name) || !empty($search_first_name) || !empty($search_insurance)): ?>
            <div class="search-summary">
                <strong>🔍 Результаты поиска:</strong>
                <?php 
                $filters = [];
                if (!empty($search_last_name)) $filters[] = "Фамилия: <strong>" . htmlspecialchars($search_last_name) . "</strong>";
                if (!empty($search_first_name)) $filters[] = "Имя: <strong>" . htmlspecialchars($search_first_name) . "</strong>";
                if (!empty($search_insurance)) $filters[] = "Полис: <strong>" . htmlspecialchars($search_insurance) . "</strong>";
                echo implode(', ', $filters);
                ?>
                <span style="margin-left: 15px; color: #6c757d;">
                    Найдено пациентов: <strong><?php echo count($patients); ?></strong>
                </span>
                
                <?php if (count($patients) > 0): ?>
                    <a href="emulate.php?last_name=<?php echo urlencode($search_last_name); ?>&first_name=<?php echo urlencode($search_first_name); ?>" 
                       class="btn btn-info btn-sm" style="margin-left: 15px;" target="_blank">
                       🔍 Эмулировать найденных
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
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
                                    <div class="action-buttons">
                                        <a href="?action=edit&id=<?php echo $patient['id']; ?>" class="btn btn-primary btn-sm">Редактировать</a>
                                        <a href="?action=view&id=<?php echo $patient['id']; ?>" class="btn btn-secondary btn-sm">Просмотр</a>
                                        <a href="emulate.php?patient_id=<?php echo $patient['id']; ?>" 
                                           class="btn btn-info btn-sm" target="_blank">Эмулировать</a>
                                        <?php if ($_SESSION['role'] === 'Администратор'): ?>
                                            <a href="?action=delete&id=<?php echo $patient['id']; ?>" class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Вы уверены, что хотите удалить пациента?')">Удалить</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <?php if (!empty($search_last_name) || !empty($search_first_name) || !empty($search_insurance)): ?>
                            <p style="color: #6c757d; margin-bottom: 20px;">Пациенты по заданным критериям не найдены</p>
                            <a href="patients.php" class="btn btn-primary">Показать всех пациентов</a>
                        <?php else: ?>
                            <p style="color: #6c757d; margin-bottom: 20px;">Пациенты не найдены</p>
                            <a href="?action=create" class="btn btn-success">Добавить первого пациента</a>
                        <?php endif; ?>
                    </div>
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
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">Фамилия</label>
                            <input type="text" class="form-control" name="last_name" 
                                   value="<?php echo htmlspecialchars($search_last_name); ?>" 
                                   placeholder="Введите фамилию">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">Имя</label>
                            <input type="text" class="form-control" name="first_name" 
                                   value="<?php echo htmlspecialchars($search_first_name); ?>" 
                                   placeholder="Введите имя">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">Полис ОМС</label>
                            <input type="text" class="form-control" name="insurance_policy" 
                                   value="<?php echo htmlspecialchars($search_insurance); ?>" 
                                   placeholder="Номер полиса">
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="form-group">
                            <label class="form-label">Действия</label>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <button type="submit" class="btn btn-primary">Найти</button>
                                <?php if (!empty($search_last_name) || !empty($search_first_name)): ?>
                                    <a href="emulate.php?last_name=<?php echo urlencode($search_last_name); ?>&first_name=<?php echo urlencode($search_first_name); ?>" 
                                       class="btn btn-info" target="_blank">Эмулировать найденных</a>
                                <?php else: ?>
                                    <button type="button" class="btn btn-info" disabled>Эмулировать найденных</button>
                                <?php endif; ?>
                                <a href="patients.php" class="btn btn-secondary">Сбросить</a>
                            </div>
                        </div>
                    </div>
                </form>
                
                <!-- Быстрый поиск по популярным фамилиям -->
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e9ecef;">
                    <label class="form-label" style="margin-bottom: 10px;">Быстрый поиск:</label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <a href="?last_name=Иванов" class="btn btn-outline-primary btn-sm">Иванов</a>
                        <a href="?last_name=Петров" class="btn btn-outline-primary btn-sm">Петров</a>
                        <a href="?last_name=Сидоров" class="btn btn-outline-primary btn-sm">Сидоров</a>
                        <a href="?last_name=Смирнов" class="btn btn-outline-primary btn-sm">Смирнов</a>
                        <a href="?last_name=Кузнецов" class="btn btn-outline-primary btn-sm">Кузнецов</a>
                        <a href="patients.php" class="btn btn-outline-secondary btn-sm">Все пациенты</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Информация об эмуляторе -->
        <div class="card">
            <div class="card-header">
                <h3>🔬 Эмуляция данных пациентов</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-8">
                        <h4>Как работает эмулятор:</h4>
                        <ol style="margin-left: 20px; margin-bottom: 20px;">
                            <li>Система подключается к внешнему эмулятору и получает случайные данные ФИО</li>
                            <li>Проводится автоматический анализ соответствия с данными пациентов из базы</li>
                            <li>Вычисляется процент совпадения по фамилии, имени и отчеству</li>
                            <li>Результаты отображаются с цветовой индикацией соответствия</li>
                        </ol>
                        
                        <div style="background: #e7f3ff; padding: 15px; border-radius: 6px;">
                            <strong>💡 Подсказка:</strong> Используйте кнопку "Эмулировать" для проверки отдельных пациентов 
                            или "Эмулировать найденных" для группового анализа.
                        </div>
                    </div>
                    <div class="col-4">
                        <div style="text-align: center; padding: 20px;">
                            <div style="font-size: 48px; margin-bottom: 15px;">🔍</div>
                            <a href="emulate.php" class="btn btn-info btn-block" target="_blank">
                                Открыть эмулятор
                            </a>
                            <p style="font-size: 12px; color: #6c757d; margin-top: 10px;">
                                Эмулятор откроется в новой вкладке
                            </p>
                        </div>
                    </div>
                </div>
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
                    window.location.href = 'appointments.php';
                    break;
                case '4':
                    e.preventDefault();
                    window.location.href = 'services.php';
                    break;
                case '5':
                    e.preventDefault();
                    window.location.href = 'reports.php';
                    break;
                case 'N':
                    e.preventDefault();
                    window.location.href = '?action=create';
                    break;
                case 'E':
                    e.preventDefault();
                    // Эмуляция для первого пациента в списке или открытие эмулятора
                    const firstEmulateBtn = document.querySelector('a[href*="emulate.php"][class*="btn-info"]');
                    if (firstEmulateBtn) {
                        firstEmulateBtn.click();
                    } else {
                        window.open('emulate.php', '_blank');
                    }
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
            
            // Добавляем обработчики для сортировки таблицы
            const table = document.getElementById('patientsTable');
            if (table) {
                const headers = table.querySelectorAll('th[data-sort]');
                headers.forEach(header => {
                    header.style.cursor = 'pointer';
                    header.addEventListener('click', function() {
                        const sortBy = this.getAttribute('data-sort');
                        sortTable(sortBy);
                    });
                });
            }
        });

        // Функция сортировки таблицы
        function sortTable(sortBy) {
            const table = document.getElementById('patientsTable');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            const sortedRows = rows.sort((a, b) => {
                const aValue = a.querySelector(`td:nth-child(${getColumnIndex(sortBy)})`).textContent.trim();
                const bValue = b.querySelector(`td:nth-child(${getColumnIndex(sortBy)})`).textContent.trim();
                
                return aValue.localeCompare(bValue, 'ru');
            });
            
            // Очищаем и перезаполняем tbody
            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }
            
            sortedRows.forEach(row => tbody.appendChild(row));
        }

        // Вспомогательная функция для получения индекса колонки
        function getColumnIndex(sortBy) {
            const headers = {
                'last_name': 1,
                'first_name': 2,
                'middle_name': 3,
                'birth_date': 4,
                'gender': 5,
                'phone': 6,
                'insurance_policy': 7
            };
            return headers[sortBy] || 1;
        }

        // Подсказка по горячим клавишам
        if (!localStorage.getItem('patientsHelpShown')) {
            setTimeout(() => {
                const message = '💡 Горячие клавиши в разделе пациентов:\n\n' +
                      '1-5 - Навигация по разделам\n' +
                      'N - Добавить нового пациента\n' +
                      'E - Эмуляция данных пациента\n' +
                      'L - Выйти из системы\n' +
                      'ESC - На главную\n\n' +
                      'Эта подсказка больше не появится.';
                
                if (confirm(message + '\n\nПоказать это сообщение при следующем входе?')) {
                    localStorage.setItem('patientsHelpShown', 'true');
                }
            }, 1000);
        }
    </script>
</body>
</html>