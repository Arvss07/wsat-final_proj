<?php
// Determine the base URL from the .env file or a global config
$app_url = $_ENV['APP_URL'] ?? 'http://localhost/wsat-final_proj';
$app_name = $_ENV['APP_NAME'] ?? 'Shoe Store';

// Define a function to easily create absolute URLs for assets
if (!function_exists('asset')) {
    function asset($path)
    {
        global $app_url;
        // Remove leading slashes from path to prevent double slashes
        return rtrim($app_url, '/') . '/' . ltrim($path, '/');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' . htmlspecialchars($app_name) : htmlspecialchars($app_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $app_url; ?>"><?php echo htmlspecialchars($app_name); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php?page=home">Home</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=profile">Profile</a></li>
                        <?php if ($_SESSION['role'] === 'Admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=admin/dashboard">Admin Dashboard</a></li>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] === 'Seller'): ?>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=seller/dashboard">Seller Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=seller/my_products">My Products</a></li>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=seller/manage_orders">Manage Orders</a></li>
                        <?php endif; ?>
                        <?php if ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Seller'): // Show Cart only for Shoppers 
                        ?>
                            <li class="nav-item"><a class="nav-link" href="index.php?page=cart">Cart</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="index.php?page=logout">Logout</a></li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=login">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php?page=register">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php
        // Check if the current page is an admin page
        $is_admin_page = isset($_GET['page']) && strpos($_GET['page'], 'admin/') === 0;
        if ($is_admin_page && isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') :
        ?>
            <div class="row">
                <div class="col-md-3">
                    <?php include_once __DIR__ . '/../admin/includes/admin_sidebar.php'; ?>
                </div>
                <div class="col-md-9">
                    <!-- Page content will be loaded here for admin pages -->
                <?php else : ?>
                    <!-- Page content will be loaded here for non-admin pages -->
                <?php endif; ?>