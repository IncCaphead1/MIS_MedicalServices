<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->checkAccess('Администратор');

$system = new MedicalSystem();
$message = '';

// Создание бэкапа
if (isset($_POST['action']) && $_POST['action'] === 'create_backup') {
    try {
        $backupFile = $system->backupDatabase();
        $message = "Бэкап успешно создан: " . basename($backupFile);
    } catch (Exception $e) {
        $message = "Ошибка при создании бэкапа: " . $e->getMessage();
    }
}

// Очистка старых бэкапов
if (isset($_POST['action']) && $_POST['action'] === 'cleanup_backups') {
    try {
        $system->cleanupOldBackups();
        $message = "Старые бэкапы успешно удалены";
    } catch (Exception $e) {
        $message = "Ошибка при очистке бэкапов: " . $e->getMessage();
    }
}

// Получение списка бэкапов
$backupFiles = glob('backup/*.sql');
rsort($backupFiles); // Сортировка по убыванию (новые сначала)
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Резервное копирование - МЕДИС</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h1>Резервное копирование</h1>
        
        <?php if ($message): ?>
            <div class="alert <?php echo strpos($message, 'Ошибка') !== false ? 'alert-error' : 'alert-success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header">Действия с бэкапами</div>
                    <div class="card-body">
                        <form method="POST">
                            <button type="submit" name="action" value="create_backup" class="btn btn-primary">
                                Создать новый бэкап БД
                            </button>
                            <button type="submit" name="action" value="cleanup_backups" class="btn btn-warning">
                                Очистить старые бэкапы (>30 дней)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col">
                <div class="card">
                    <div class="card-header">Информация о бэкапах</div>
                    <div class="card-body">
                        <p>Всего бэкапов: <strong><?php echo count($backupFiles); ?></strong></p>
                        <p>Директория: <code>./backup/</code></p>
                        <p>Срок хранения: 30 дней</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Список бэкапов</div>
            <div class="card-body">
                <?php if (empty($backupFiles)): ?>
                    <p>Бэкапы не найдены</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Имя файла</th>
                                <th>Размер</th>
                                <th>Дата создания</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backupFiles as $file): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(basename($file)); ?></td>
                                <td><?php echo round(filesize($file) / 1024, 2); ?> KB</td>
                                <td><?php echo date('Y-m-d H:i:s', filemtime($file)); ?></td>
                                <td>
                                    <a href="<?php echo $file; ?>" download class="btn btn-primary">Скачать</a>
                                    <a href="?action=delete&file=<?php echo urlencode(basename($file)); ?>" class="btn btn-danger"
                                       onclick="return confirm('Удалить бэкап?')">Удалить</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>