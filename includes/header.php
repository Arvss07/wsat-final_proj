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

$brand_link = $app_url;
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Admin') {
        $brand_link = $app_url . 'index.php?page=admin/dashboard';
    } elseif ($_SESSION['role'] === 'Seller') {
        $brand_link = $app_url . 'index.php?page=seller/my_products';
    } else {
        $brand_link = $app_url . 'index.php?page=home';
    }
} else {
    $brand_link = $app_url . 'index.php?page=home';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40/MKBW2W4Rhis/DbILU74C1vSrLJxCq57o941Ym01SwNsOMqvEBFlcgUa6xLiPY/NS5R+E6ztJQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="<?php echo asset('assets/css/style.css'); ?>">
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $brand_link; ?>"><?php echo htmlspecialchars($app_name); ?></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php
                    // Determine the current page for active link highlighting
                    $current_page = $_GET['page'] ?? 'home'; // Default to 'home' if no page is set
                    ?>

                    <?php if (!isset($_SESSION['user_id'])): // User is not logged in 
                    ?>
                        <li class="nav-item">
                            <a class="nav-link <?php if ($current_page === 'home') echo 'active'; ?>" href="index.php?page=home">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php if ($current_page === 'login') echo 'active'; ?>" href="index.php?page=login">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php if ($current_page === 'register') echo 'active'; ?>" href="index.php?page=register">Register</a>
                        </li>
                    <?php else: // User is logged in 
                    ?>
                        <?php $user_role = $_SESSION['role'] ?? null; ?>

                        <?php if ($user_role === 'Admin'): ?>
                            <li class="nav-item"><a class="nav-link <?php if ($current_page === 'admin/dashboard') echo 'active'; ?>" href="index.php?page=admin/dashboard">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link <?php if ($current_page === 'logout') echo 'active'; ?>" href="index.php?page=logout">Logout</a></li>
                        <?php elseif ($user_role === 'Seller'): ?>
                            <li class="nav-item"><a class="nav-link <?php if ($current_page === 'seller/my_products') echo 'active'; ?>" href="index.php?page=seller/my_products">My Products</a></li>
                            <li class="nav-item"><a class="nav-link <?php if ($current_page === 'seller/manage_orders') echo 'active'; ?>" href="index.php?page=seller/manage_orders">Manage Orders</a></li>
                            <li class="nav-item"><a class="nav-link <?php if ($current_page === 'account') echo 'active'; ?>" href="index.php?page=account">My Account</a></li>
                            <li class="nav-item"><a class="nav-link <?php if ($current_page === 'logout') echo 'active'; ?>" href="index.php?page=logout">Logout</a></li>
                        <?php elseif ($user_role === 'Shopper'): ?>
                            <li class="nav-item"><a class="nav-link <?php if ($current_page === 'home') echo 'active'; ?>" href="index.php?page=home">Home</a></li>
                            <li class="nav-item"><a class="nav-link <?php if ($current_page === 'account') echo 'active'; ?>" href="index.php?page=account">My Account</a></li>
                            <li class="nav-item position-relative">
                                <a class="nav-link <?php if ($current_page === 'cart') echo 'active'; ?>" href="index.php?page=cart">
                                    <i class="bi bi-cart3"></i> Cart
                                    <span id="cart-badge" class="badge bg-primary position-absolute top-0 start-100 translate-middle rounded-pill" style="font-size:0.8em;">0</span>
                                </a>
                            </li>
                            <li class="nav-item"><a class="nav-link <?php if ($current_page === 'orders') echo 'active'; ?>" href="index.php?page=orders">View Orders</a></li>
                            <li class="nav-item"><a class="nav-link <?php if ($current_page === 'logout') echo 'active'; ?>" href="index.php?page=logout">Logout</a></li>
                        <?php endif; ?>
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

<script>
function updateCartBadge() {
    fetch('api/cart/cart_count.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('cart-badge');
                if (badge) badge.textContent = data.count;
            }
        });
}
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('cart-badge')) {
        updateCartBadge();
    }
});
</script>