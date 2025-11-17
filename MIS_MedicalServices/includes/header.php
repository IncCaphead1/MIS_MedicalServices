<?php if (isset($_SESSION['user_id'])): ?>
<div class="header">
    <div class="header-content">
        <div class="logo">
            <img src="images/logo_mis.png" alt="Логотип МЕДИС">
            <div class="logo-text">МЕДИС - Медицинская Информационная Система</div>
        </div>
        <div class="user-info">
            <span>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
            <span>(<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
            <a href="change_password.php" class="btn btn-secondary">Сменить пароль</a>
            <a href="logout.php" class="btn btn-danger">Выйти</a>
        </div>
    </div>
</div>

<nav class="nav-menu">
    <div class="nav-content">
        <a href="dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">Главная</a>
        <a href="patients.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'patients.php' ? 'active' : ''; ?>">Пациенты</a>
        <a href="doctors.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'doctors.php' ? 'active' : ''; ?>">Врачи</a>
        <a href="appointments.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'appointments.php' ? 'active' : ''; ?>">Приемы</a>
        <a href="services.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'services.php' ? 'active' : ''; ?>">Услуги</a>
        <?php if ($_SESSION['role'] === 'Администратор'): ?>
            <a href="backup.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'backup.php' ? 'active' : ''; ?>">Резервное копирование</a>
            <a href="export.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) === 'export.php' ? 'active' : ''; ?>">Экспорт данных</a>
        <?php endif; ?>
    </div>
</nav>
<?php endif; ?>