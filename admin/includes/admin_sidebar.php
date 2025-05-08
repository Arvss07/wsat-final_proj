<?php
// admin/includes/admin_sidebar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is admin, otherwise redirect or show error
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    // This check is more of a safeguard; actual routing should prevent non-admins here.
    // For direct access attempts, you might redirect to login or an unauthorized page.
    // echo "Access Denied.";
    // exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
if (isset($_GET['page'])) {
    $current_page = $_GET['page'];
}

$app_url = $_ENV['APP_URL'] ?? 'http://localhost/wsat-final_proj';

?>
<div class="list-group">
    <a href="<?php echo $app_url . '/index.php?page=admin/dashboard'; ?>" class="list-group-item list-group-item-action <?php echo ($current_page === 'admin/dashboard') ? 'active' : ''; ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    <a href="<?php echo $app_url . '/index.php?page=admin/users'; ?>" class="list-group-item list-group-item-action <?php echo ($current_page === 'admin/users') ? 'active' : ''; ?>">
        <i class="bi bi-people"></i> Manage Accounts
    </a>
    <a href="<?php echo $app_url . '/index.php?page=admin/products'; ?>" class="list-group-item list-group-item-action <?php echo ($current_page === 'admin/products') ? 'active' : ''; ?>">
        <i class="bi bi-box-seam"></i> Manage Products
    </a>
    <a href="<?php echo $app_url . '/index.php?page=admin/settings'; ?>" class="list-group-item list-group-item-action <?php echo ($current_page === 'admin/settings') ? 'active' : ''; ?>">
        <i class="bi bi-gear"></i> Settings
    </a>
</div>