<?php
// admin/settings.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php?page=login&error=Unauthorized+access");
    exit;
}

require_once __DIR__ . '/../config/database.php';

$page_title = "Admin Settings";

// Placeholder for settings. Could include:
// - Site name, description
// - Email settings (view only, or allow changes if secure)
// - Maintenance mode toggle
// - etc.

$feedback = [];
$errors = [];

// Example: Handle form submission if settings are made editable
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//    // Sanitize and save settings
//    // $setting_value = $_POST['some_setting'];
//    // Update database or .env (carefully!)
//    $feedback[] = "Settings updated successfully (example).";
// }

?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo htmlspecialchars($page_title); ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=admin/dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Settings</li>
    </ol>

    <?php if (!empty($feedback)): ?>
        <div class="alert alert-success">
            <?php foreach ($feedback as $msg): echo htmlspecialchars($msg) . "<br>";
            endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): echo htmlspecialchars($err) . "<br>";
            endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-gear-fill me-1"></i>
            Application Settings
        </div>
        <div class="card-body">
            <p>This page is a placeholder for various administrative settings.</p>
            <p>Future settings could include:</p>
            <ul>
                <li>Site Name & Description (currently from .env)</li>
                <li>Default items per page for pagination</li>
                <li>Email configuration overview</li>
                <li>Maintenance mode toggle</li>
            </ul>
            <hr>
            <h5>Environment Configuration (Read-only)</h5>
            <p>These values are typically set in the <code>.env</code> file and are critical for application operation.</p>
            <dl class="row">
                <dt class="col-sm-3">App Name:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($_ENV['APP_NAME'] ?? 'Not Set'); ?></dd>

                <dt class="col-sm-3">App URL:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($_ENV['APP_URL'] ?? 'Not Set'); ?></dd>

                <dt class="col-sm-3">Debug Mode:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($_ENV['DEBUG'] ?? 'Not Set'); ?></dd>

                <dt class="col-sm-3">Mail From Address:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($_ENV['MAIL_FROM_ADDRESS'] ?? 'Not Set'); ?></dd>

                <dt class="col-sm-3">Mail From Name:</dt>
                <dd class="col-sm-9"><?php echo htmlspecialchars($_ENV['MAIL_FROM_NAME'] ?? 'Not Set'); ?></dd>
            </dl>
        </div>
    </div>
</div>