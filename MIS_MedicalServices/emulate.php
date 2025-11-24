<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
$auth->checkAccess('Врач');

$db = new Database();

// Получаем параметры поиска
$last_name = $_GET['last_name'] ?? '';
$first_name = $_GET['first_name'] ?? '';
$patient_id = $_GET['patient_id'] ?? '';

$patients = [];
$emulation_result = null;

// Если передан конкретный пациент
if ($patient_id) {
    $result = $db->query("
        SELECT p.*, u.login 
        FROM Patients p 
        JOIN Users u ON p.user_id = u.id 
        WHERE p.id = ?
    ", [$patient_id]);
    
    if ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
} 
// Если передан поиск по имени/фамилии
elseif ($last_name || $first_name) {
    $query = "
        SELECT p.*, u.login 
        FROM Patients p 
        JOIN Users u ON p.user_id = u.id 
        WHERE 1=1
    ";
    $params = [];
    
    if (!empty($last_name)) {
        $query .= " AND p.last_name LIKE ?";
        $params[] = "%$last_name%";
    }
    if (!empty($first_name)) {
        $query .= " AND p.first_name LIKE ?";
        $params[] = "%$first_name%";
    }
    
    $query .= " ORDER BY p.last_name, p.first_name";
    
    $result = $db->query($query, $params);
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Эмуляция данных
if (!empty($patients) || (!empty($last_name) && !empty($first_name))) {
    $emulation_result = emulateAndValidateData($patients, $last_name, $first_name);
}

function emulateAndValidateData($patients, $search_last_name = '', $search_first_name = '') {
    $emulator_url = "http://prb.sylas.ru/TransferSimulator/fullName";
    
    try {
        $response = @file_get_contents($emulator_url);
        if ($response === FALSE) {
            throw new Exception("Не удалось подключиться к эмулятору");
        }
        
        $data = json_decode($response, true);
        $random_fullname = $data['value'] ?? '';
        
        // Разбиваем полученное ФИО на части
        $parts = array_filter(explode(' ', $random_fullname));
        $random_last_name = $parts[0] ?? '';
        $random_first_name = $parts[1] ?? '';
        $random_middle_name = $parts[2] ?? '';
        
        // Проверяем критерии для данных из эмулятора
        $validation_results = validateEmulatedData($random_fullname, $random_last_name, $random_first_name, $random_middle_name);
        
        $results = [];
        
        // Если есть пациенты из БД, показываем их для контекста
        if (!empty($patients)) {
            foreach ($patients as $patient) {
                $results[] = [
                    'patient' => $patient,
                    'random_data' => [
                        'fullname' => $random_fullname,
                        'last_name' => $random_last_name,
                        'first_name' => $random_first_name,
                        'middle_name' => $random_middle_name
                    ],
                    'validation' => $validation_results,
                    'context_type' => 'patient'
                ];
            }
        } else {
            // Если пациентов нет, но есть поисковый запрос
            $results[] = [
                'patient' => [
                    'last_name' => $search_last_name,
                    'first_name' => $search_first_name,
                    'middle_name' => '',
                    'birth_date' => '',
                    'insurance_policy' => ''
                ],
                'random_data' => [
                    'fullname' => $random_fullname,
                    'last_name' => $random_last_name,
                    'first_name' => $random_first_name,
                    'middle_name' => $random_middle_name
                ],
                'validation' => $validation_results,
                'context_type' => 'search'
            ];
        }
        
        return $results;
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function validateEmulatedData($fullname, $last_name, $first_name, $middle_name) {
    $criteria = [];
    $total_score = 0;
    $max_score = 4; // Увеличили до 4 критериев
    
    // Критерий 1: Общая длина ФИО не более 250 символов
    $total_length = mb_strlen($fullname);
    $length_ok = $total_length <= 250;
    $criteria[] = [
        'name' => 'Общая длина ФИО',
        'description' => "Суммарная длина не более 250 символов (текущая: {$total_length})",
        'passed' => $length_ok,
        'value' => $total_length
    ];
    if ($length_ok) $total_score++;
    
    // Критерий 2: Только русские символы
    $russian_chars_ok = containsOnlyRussianChars($fullname);
    $criteria[] = [
        'name' => 'Русские символы',
        'description' => 'ФИО содержит только русские буквы, пробелы и дефисы',
        'passed' => $russian_chars_ok,
        'value' => $russian_chars_ok ? 'Только русские символы' : 'Обнаружены не русские символы'
    ];
    if ($russian_chars_ok) $total_score++;
    
    // Критерий 3: Корректная структура ФИО (3 части)
    $structure_ok = !empty($last_name) && !empty($first_name) && !empty($middle_name);
    $criteria[] = [
        'name' => 'Структура ФИО',
        'description' => 'Наличие фамилии, имени и отчества',
        'passed' => $structure_ok,
        'value' => $structure_ok ? 'Полное ФИО' : 'Неполное ФИО'
    ];
    if ($structure_ok) $total_score++;
    
    // КРИТЕРИЙ 4: Реалистичность ФИО (новый!)
    $realistic_ok = isRealisticName($last_name, $first_name, $middle_name);
    $criteria[] = [
        'name' => 'Реалистичность ФИО',
        'description' => 'ФИО выглядит реалистично (не состоит из цифр, повторяющихся символов и т.д.)',
        'passed' => $realistic_ok,
        'value' => $realistic_ok ? 'Реалистичное ФИО' : 'Нереалистичное ФИО'
    ];
    if ($realistic_ok) $total_score++;
    
    // Дополнительные проверки
    $last_name_length = mb_strlen($last_name);
    $first_name_length = mb_strlen($first_name);
    $middle_name_length = mb_strlen($middle_name);
    
    $criteria[] = [
        'name' => 'Длина фамилии',
        'description' => "Фамилия: {$last_name_length} символов",
        'passed' => $last_name_length >= 2 && $last_name_length <= 50,
        'value' => $last_name_length
    ];
    
    $criteria[] = [
        'name' => 'Длина имени',
        'description' => "Имя: {$first_name_length} символов",
        'passed' => $first_name_length >= 2 && $first_name_length <= 50,
        'value' => $first_name_length
    ];
    
    $criteria[] = [
        'name' => 'Длина отчества',
        'description' => "Отчество: {$middle_name_length} символов",
        'passed' => $middle_name_length >= 5 && $middle_name_length <= 50,
        'value' => $middle_name_length
    ];
    
    $validation_percentage = round(($total_score / $max_score) * 100);
    
    return [
        'criteria' => $criteria,
        'total_score' => $total_score,
        'max_score' => $max_score,
        'validation_percentage' => $validation_percentage,
        'status' => $validation_percentage >= 75 ? 'high' : ($validation_percentage >= 50 ? 'medium' : 'low')
    ];
}

// Новая функция для проверки реалистичности ФИО
function isRealisticName($last_name, $first_name, $middle_name) {
    // Проверка на цифры
    if (preg_match('/[0-9]/', $last_name . $first_name . $middle_name)) {
        return false;
    }
    
    // Проверка на повторяющиеся символы (более 3 подряд)
    if (preg_match('/(.)\1{3,}/', $last_name . $first_name . $middle_name)) {
        return false;
    }
    
    // Проверка на слишком короткие компоненты
    if (mb_strlen($last_name) < 2 || mb_strlen($first_name) < 2 || mb_strlen($middle_name) < 5) {
        return false;
    }
    
    // Проверка на отсутствие только заглавных или только строчных букв
    if (mb_strtoupper($last_name) === $last_name || mb_strtolower($last_name) === $last_name) {
        // Допустимо для фамилии, но проверим другие компоненты
        if (mb_strtoupper($first_name) === $first_name || mb_strtolower($first_name) === $first_name) {
            return false;
        }
    }
    
    // Проверка на наличие хотя бы одной гласной в каждом компоненте
    $has_vowel_pattern = '/[аеёиоуыэюя]/iu';
    if (!preg_match($has_vowel_pattern, $last_name) || 
        !preg_match($has_vowel_pattern, $first_name) || 
        !preg_match($has_vowel_pattern, $middle_name)) {
        return false;
    }
    
    return true;
}

function containsOnlyRussianChars($text) {
    $text = trim($text);
    
    if (empty($text)) {
        return false;
    }
    
    $length = mb_strlen($text);
    for ($i = 0; $i < $length; $i++) {
        $char = mb_substr($text, $i, 1);
        $code = mb_ord($char, 'UTF-8');
        
        // Проверяем Unicode коды:
        // - Русские буквы: 1040-1103 (А-я) и 1105 (ё), 1025 (Ё)
        // - Пробел: 32
        // - Дефис: 45
        if (!(($code >= 1040 && $code <= 1103) || // А-я
              $code == 1105 || $code == 1025 ||   // ё, Ё
              $code == 32 || $code == 45)) {      // пробел, дефис
            error_log("Invalid character code: " . $char . " (Unicode: " . $code . ")");
            return false;
        }
    }
    
    return true;
}

// Вспомогательная функция, если mb_ord не существует
if (!function_exists('mb_ord')) {
    function mb_ord($char, $encoding = 'UTF-8') {
        if ($char === '') return false;
        $char = mb_substr($char, 0, 1, $encoding);
        $code = 0;
        for ($i = 0; $i < mb_strlen($char, $encoding); $i++) {
            $code = ($code << 8) | ord($char[$i]);
        }
        return $code;
    }
}

$db->close();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Эмуляция данных пациентов - МЕДИС</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .match-high { background-color: #d4edda; border-left: 4px solid #28a745; }
        .match-medium { background-color: #fff3cd; border-left: 4px solid #ffc107; }
        .match-low { background-color: #f8d7da; border-left: 4px solid #dc3545; }
        .progress-bar { 
            height: 20px; 
            background-color: #e9ecef; 
            border-radius: 10px; 
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-fill { 
            height: 100%; 
            background: linear-gradient(90deg, #dc3545, #ffc107, #28a745);
            transition: width 0.5s ease;
        }
        .result-card { margin-bottom: 20px; padding: 20px; border-radius: 8px; }
        .criteria-list { margin: 15px 0; }
        .criteria-item { margin: 5px 0; padding: 8px 12px; border-radius: 4px; border-left: 4px solid; }
        .criteria-met { background-color: #d4edda; color: #155724; border-left-color: #28a745; }
        .criteria-not-met { background-color: #f8d7da; color: #721c24; border-left-color: #dc3545; }
        .criteria-info { background-color: #e7f3ff; color: #004085; border-left-color: #007BFF; }
        .validation-header { 
            background: linear-gradient(135deg, #007BFF, #0056b3);
            color: white;
            padding: 15px 20px;
            margin: -20px -20px 20px -20px;
            border-radius: 8px 8px 0 0;
        }
        .data-comparison {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">МС</div>
                <div class="logo-text">МЕДИС - Валидация данных эмулятора</div>
            </div>
            <div class="user-info">
                <span><strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                <span class="user-role"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                <a href="patients.php" class="btn btn-secondary btn-sm">Назад к пациентам</a>
            </div>
        </div>
    </div>

    <div class="container">
        <h1>🔍 Валидация данных из эмулятора</h1>
        
        <!-- Форма поиска -->
        <div class="card">
            <div class="card-header">
                <h3>Получить и проверить данные из эмулятора</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Фамилия (для контекста)</label>
                            <input type="text" class="form-control" name="last_name" 
                                   value="<?php echo htmlspecialchars($last_name); ?>" 
                                   placeholder="Введите фамилию">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Имя (для контекста)</label>
                            <input type="text" class="form-control" name="first_name" 
                                   value="<?php echo htmlspecialchars($first_name); ?>" 
                                   placeholder="Введите имя">
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Действие</label>
                            <div style="display: flex; gap: 10px; align-items: flex-end;">
                                <button type="submit" class="btn btn-primary">Получить и проверить</button>
                                <a href="emulate.php" class="btn btn-secondary">Сбросить</a>
                            </div>
                        </div>
                    </div>
                </form>
                
                <?php if (!empty($last_name) || !empty($first_name)): ?>
                    <div style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                        <strong>Контекст поиска:</strong>
                        <?php 
                        $filters = [];
                        if (!empty($last_name)) $filters[] = "Фамилия: " . htmlspecialchars($last_name);
                        if (!empty($first_name)) $filters[] = "Имя: " . htmlspecialchars($first_name);
                        echo implode(', ', $filters);
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Результаты валидации -->
        <?php if (is_array($emulation_result)): ?>
            <?php if (isset($emulation_result['error'])): ?>
                <div class="alert alert-error">
                    <strong>Ошибка эмуляции:</strong> <?php echo htmlspecialchars($emulation_result['error']); ?>
                </div>
            <?php else: ?>
                <?php foreach ($emulation_result as $result): ?>
                    <div class="card">
                        <div class="validation-header">
                            <h3>📊 Результаты валидации данных эмулятора</h3>
                        </div>
                        <div class="card-body">
                            <!-- Данные из эмулятора -->
                            <div class="data-comparison">
                                <h4>📨 Данные получены из эмулятора:</h4>
                                <div style="background: white; padding: 15px; border-radius: 6px; margin: 10px 0;">
                                    <strong style="font-size: 1.2em;"><?php echo htmlspecialchars($result['random_data']['fullname']); ?></strong>
                                    <div style="margin-top: 10px; color: #6c757d;">
                                        Фамилия: <strong><?php echo htmlspecialchars($result['random_data']['last_name']); ?></strong> | 
                                        Имя: <strong><?php echo htmlspecialchars($result['random_data']['first_name']); ?></strong> | 
                                        Отчество: <strong><?php echo htmlspecialchars($result['random_data']['middle_name']); ?></strong>
                                    </div>
                                </div>
                            </div>

                            <!-- Контекст (если есть) -->
                            <?php if ($result['context_type'] === 'patient'): ?>
                                <div style="background: #e7f3ff; padding: 15px; border-radius: 6px; margin: 15px 0;">
                                    <h4>👤 Контекст пациента из БД:</h4>
                                    <p>
                                        <strong><?php echo htmlspecialchars($result['patient']['last_name'] . ' ' . $result['patient']['first_name'] . ' ' . ($result['patient']['middle_name'] ?? '')); ?></strong><br>
                                        Дата рождения: <?php echo htmlspecialchars($result['patient']['birth_date']); ?> | 
                                        Полис: <?php echo htmlspecialchars($result['patient']['insurance_policy']); ?>
                                    </p>
                                </div>
                            <?php elseif ($result['context_type'] === 'search' && (!empty($result['patient']['last_name']) || !empty($result['patient']['first_name']))): ?>
                                <div style="background: #e7f3ff; padding: 15px; border-radius: 6px; margin: 15px 0;">
                                    <h4>🔍 Контекст поиска:</h4>
                                    <p>
                                        Искали: <strong><?php echo htmlspecialchars($result['patient']['last_name'] . ' ' . $result['patient']['first_name']); ?></strong>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <!-- Результаты валидации -->
                            <?php 
                            $validation = $result['validation'];
                            $status_class = 'match-' . $validation['status'];
                            ?>
                            
                            <div class="result-card <?php echo $status_class; ?>">
                                <div class="row">
                                    <div class="col-8">
                                        <h4>✅ Критерии проверки данных:</h4>
                                        
                                        <div class="criteria-list">
                                            <?php foreach ($validation['criteria'] as $criterion): ?>
                                                <div class="criteria-item <?php echo $criterion['passed'] ? 'criteria-met' : 'criteria-not-met'; ?>">
                                                    <?php if ($criterion['passed']): ?>
                                                        ✓ <strong><?php echo htmlspecialchars($criterion['name']); ?>:</strong>
                                                    <?php else: ?>
                                                        ✗ <strong><?php echo htmlspecialchars($criterion['name']); ?>:</strong>
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($criterion['description']); ?>
                                                    <?php if (isset($criterion['value'])): ?>
                                                        <span style="float: right; font-weight: bold;">
                                                            <?php echo htmlspecialchars($criterion['value']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-4">
                                        <div style="text-align: center;">
                                            <div class="stat-number"><?php echo $validation['validation_percentage']; ?>%</div>
                                            <div class="stat-label">Проход проверки</div>
                                            
                                            <div class="progress-bar">
                                                <div class="progress-fill" style="width: <?php echo $validation['validation_percentage']; ?>%"></div>
                                            </div>
                                            
                                            <div style="margin-top: 15px;">
                                                <?php if ($validation['status'] == 'high'): ?>
                                                    <span class="status-badge status-completed">✅ Данные валидны</span>
                                                <?php elseif ($validation['status'] == 'medium'): ?>
                                                    <span class="status-badge status-scheduled">⚠️ Частично валидны</span>
                                                <?php else: ?>
                                                    <span class="status-badge status-cancelled">❌ Данные невалидны</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div style="margin-top: 10px; font-size: 14px; color: #6c757d;">
                                                Пройдено: <?php echo $validation['total_score']; ?> из <?php echo $validation['max_score']; ?> основных критериев
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Заключение -->
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 20px;">
    <h4>📋 Заключение:</h4>
    <p>
        <?php if ($validation['status'] == 'high'): ?>
            ✅ <strong>Данные из эмулятора прошли проверку.</strong> ФИО соответствует всем критериям: 
            корректная длина, русские символы, полная структура и реалистичность.
        <?php elseif ($validation['status'] == 'medium'): ?>
            ⚠️ <strong>Данные из эмулятора частично соответствуют критериям.</strong> 
            Требуется дополнительная проверка перед использованием в системе.
        <?php else: ?>
            ❌ <strong>Данные из эмулятора не прошли проверку.</strong> 
            Не соответствуют основным критериям валидности.
        <?php endif; ?>
    </p>
</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php elseif (!empty($last_name) || !empty($first_name)): ?>
            <div class="card">
                <div class="card-body">
                    <p style="text-align: center; color: #6c757d; padding: 40px;">
                        Для отображения результатов проверки нажмите "Получить и проверить"
                    </p>
                </div>
            </div>
        <?php endif; ?>

       <!-- Инструкция -->
<div class="card">
    <div class="card-header">
        <h3>ℹ️ Критерии проверки данных</h3>
    </div>
    <div class="card-body">
        <h4>Основные критерии валидации:</h4>
        <ol>
            <li><strong>Длина ФИО:</strong> Суммарная длина фамилии, имени и отчества не более 250 символов</li>
            <li><strong>Русские символы:</strong> ФИО должно содержать только русские буквы, пробелы и дефисы</li>
            <li><strong>Структура ФИО:</strong> Наличие всех трех компонентов (фамилия, имя, отчество)</li>
            <li><strong>Реалистичность:</strong> ФИО должно выглядеть реалистично (без цифр, без повторяющихся символов, с гласными буквами)</li>
        </ol>
        
        <h4>Дополнительные проверки:</h4>
        <ul>
            <li>Длина фамилии: 2-50 символов</li>
            <li>Длина имени: 2-50 символов</li>
            <li>Длина отчества: 5-50 символов</li>
        </ul>
        
        <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin-top: 15px;">
            <strong>⚠️ Что считается нереалистичным ФИО:</strong><br>
            - Содержит цифры<br>
            - Имеет повторяющиеся символы (например, "аааабв")<br>
            - Состоит только из заглавных или только из строчных букв<br>
            - Не содержит гласных букв<br>
            - Слишком короткие компоненты
        </div>
    </div>
</div>

    <script>
        // Горячие клавиши
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
                case 'ESCAPE':
                    e.preventDefault();
                    window.location.href = 'patients.php';
                    break;
            }
        });

        // Автофокус на поиске
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.querySelector('input[name="last_name"]');
            if (searchInput) {
                searchInput.focus();
            }
        });
    </script>
</body>
</html>