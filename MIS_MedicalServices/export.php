<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$auth = new Auth();
$auth->checkAccess('Администратор');

$system = new MedicalSystem();

// Экспорт данных
if (isset($_POST['action']) && $_POST['action'] === 'export') {
    $exportType = $_POST['export_type'];
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;
    
    try {
        switch ($exportType) {
            case 'patients':
                $result = $db->query("SELECT * FROM patients");
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $system->exportToExcel($data, 'patients_export_' . date('Y-m-d'));
                break;
                
            case 'appointments':
                $sql = "SELECT a.*, p.last_name, p.first_name, d.last_name as doctor_name, s.name as service_name 
                        FROM appointments a 
                        JOIN patients p ON a.patient_id = p.id 
                        JOIN doctors d ON a.doctor_id = d.id 
                        JOIN services s ON a.service_id = s.id";
                
                if ($startDate && $endDate) {
                    $sql .= " WHERE a.appointment_date BETWEEN ? AND ?";
                    $result = $db->query($sql, [$startDate, $endDate]);
                } else {
                    $result = $db->query($sql);
                }
                
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $system->exportToExcel($data, 'appointments_export_' . date('Y-m-d'));
                break;
                
            case 'revenue':
                $revenue = $system->calculateRevenue($startDate, $endDate);
                $data = [['Период', 'Доход'], ["$startDate - $endDate", $revenue]];
                $system->exportToExcel($data, 'revenue_export_' . date('Y-m-d'));
                break;
        }
    } catch (Exception $e) {
        $message = "Ошибка при экспорте: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Экспорт данных - МЕДИС</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h1>Экспорт данных</h1>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">Экспорт данных в Excel</div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Тип экспорта</label>
                        <select class="form-control" name="export_type" id="exportType" required>
                            <option value="">Выберите тип данных</option>
                            <option value="patients">Пациенты</option>
                            <option value="appointments">Приемы</option>
                            <option value="revenue">Финансовый отчет</option>
                        </select>
                    </div>
                    
                    <div id="dateRange" style="display: none;">
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label class="form-label">Период с</label>
                                    <input type="date" class="form-control" name="start_date" id="startDate">
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label class="form-label">по</label>
                                    <input type="date" class="form-control" name="end_date" id="endDate">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="action" value="export" class="btn btn-primary">
                        Экспортировать в Excel
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Доход за период</div>
            <div class="card-body">
                <form method="POST" action="analytics.php">
                    <div class="row">
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">Период с</label>
                                <input type="date" class="form-control" name="start_date" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="form-group">
                                <label class="form-label">по</label>
                                <input type="date" class="form-control" name="end_date" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" name="action" value="revenue" class="btn btn-primary">
                        Рассчитать доход
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('exportType').addEventListener('change', function() {
            const dateRange = document.getElementById('dateRange');
            if (this.value === 'appointments' || this.value === 'revenue') {
                dateRange.style.display = 'block';
            } else {
                dateRange.style.display = 'none';
            }
        });
    </script>
</body>
</html>