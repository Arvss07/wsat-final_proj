<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1'); // Should be 0 in production, errors logged instead

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load Composer's autoloader for .env and other libraries
require_once __DIR__ . '/vendor/autoload.php';

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    die('Critical error: .env file not found. Please ensure it exists in the project root.');
}

// Database Connection (this file creates $conn)
require_once __DIR__ . '/config/database.php';

// Utility functions (e.g., for password hashing, UUID generation if needed here)
require_once __DIR__ . '/utils/hash.php';
require_once __DIR__ . '/utils/uuid.php';

// --- Routing Logic ---
$page = $_GET['page'] ?? 'home'; // Default page is 'home'

// Define public pages that do not require login
$public_pages = ['home', 'login', 'register', 'product', 'products', 'contact', 'about', 'password_reset_request', 'password_reset_form'];

// Define pages that require login
$protected_pages = ['profile', 'cart', 'checkout', 'orders', 'logout'];

// Define pages specific to roles
$admin_pages = ['admin/dashboard', 'admin/users', 'admin/products', 'admin/settings'];
$seller_pages = ['seller/dashboard', 'seller/my_products', 'seller/add_product', 'seller/edit_product', 'seller/manage_orders', 'seller/orders', 'seller/settings', 'seller/view_product', 'seller/delete_product', 'seller/view_order_details', 'seller/edit_order', 'seller/cancel_order']; // Added seller/view_product and seller/delete_product

$page_path = '';
$page_title = ucfirst(str_replace("_", " ", $page)); // Default page title

// Check if the user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['role'] ?? null;

// --- Page Protection Logic ---
if (in_array($page, $protected_pages) && !$is_logged_in) {
    // User tries to access a protected page without being logged in
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI']; // Save intended destination
    header("Location: index.php?page=login&message=Please login to access this page.");
    exit;
} elseif ($page === 'login' && $is_logged_in) {
    // User tries to access login page while already logged in
    header("Location: index.php?page=home"); // Redirect to home or dashboard
    exit;
}

// Role-based access control
if (strpos($page, 'admin/') === 0 && (!in_array($page, $admin_pages) || $user_role !== 'Admin')) {
    // Trying to access an admin page without being an admin or if page is not in defined admin_pages
    $page = 'unauthorized'; // Or redirect to a specific unauthorized page
} elseif (strpos($page, 'seller/') === 0 && (!in_array($page, $seller_pages) || !in_array($user_role, ['Seller', 'Admin']))) {
    // Trying to access a seller page without being a seller/admin or if page is not in defined seller_pages
    // Admins can typically access seller pages too for oversight
    $page = 'unauthorized'; // Or redirect to a specific unauthorized page
}

// --- Determine Page Path ---
switch ($page) {
    // Public Pages
    case 'home':
        $page_path = __DIR__ . '/pages/home.php';
        $page_title = 'Welcome';
        break;
    case 'login':
        $page_path = __DIR__ . '/pages/login.php';
        $page_title = 'Login';
        break;
    case 'register':
        $page_path = __DIR__ . '/pages/register.php';
        $page_title = 'Register';
        break;
    case 'password_reset_request':
        $page_path = __DIR__ . '/pages/password_reset_request.php';
        $page_title = 'Request Password Reset';
        break;
    case 'password_reset_form':
        $page_path = __DIR__ . '/pages/password_reset_form.php';
        $page_title = 'Reset Password';
        break;
    case 'logout':
        // Handle logout logic here (e.g., in api/auth/logout_handler.php or directly)
        session_unset();
        session_destroy();
        header("Location: index.php?page=login&message=You have been logged out.");
        exit;
        break;

    // Protected Pages (Shopper, Seller, Admin)
    case 'profile':
        $page_path = __DIR__ . '/pages/profile.php';
        $page_title = 'My Profile';
        break;
    case 'cart':
        $page_path = __DIR__ . '/pages/cart.php';
        $page_title = 'Shopping Cart';
        break;
    // ... other protected pages

    // Admin Pages
    case 'admin/dashboard':
        $page_path = __DIR__ . '/admin/dashboard.php';
        $page_title = 'Admin Dashboard';
        break;
    case 'admin/users':
        $page_path = __DIR__ . '/admin/users.php';
        $page_title = 'Manage Users';
        break;
    case 'admin/products':
        $page_path = __DIR__ . '/admin/products.php';
        $page_title = 'Manage Products';
        break;
    case 'admin/settings':
        $page_path = __DIR__ . '/admin/settings.php';
        $page_title = 'Admin Settings';
        break;
    // ... other admin pages

    // Seller Pages
    case 'seller/dashboard': // Added for the main seller dashboard link
        $page_path = __DIR__ . '/seller/my_products.php';
        $page_title = 'Seller Dashboard';
        break;
    case 'seller/my_products': // Keep this for direct access or other links
        $page_path = __DIR__ . '/seller/my_products.php';
        $page_title = 'My Products';
        break;
    case 'seller/add_product': // Placeholder for add product page
        $page_path = __DIR__ . '/seller/add_product.php';
        $page_title = 'Add New Product';
        break;
    case 'seller/edit_product': // Placeholder for edit product page
        $page_path = __DIR__ . '/seller/edit_product.php';
        $page_title = 'Edit Product';
        break;
    case 'seller/manage_orders': // Added for managing shopper orders
        $page_path = __DIR__ . '/seller/manage_orders.php';
        $page_title = 'Manage Orders';
        break;
    case 'seller/view_product':
        $page_path = __DIR__ . '/seller/view_product.php';
        $page_title = 'View Product';
        break;
    case 'seller/delete_product':
        $page_path = __DIR__ . '/seller/delete_product.php';
        $page_title = 'Delete Product'; // Title might not be shown as it usually redirects
        break;
    case 'seller/view_order_details':
        $page_path = __DIR__ . '/seller/view_order_details.php';
        $page_title = 'View Order Details';
        break;
    case 'seller/edit_order':
        $page_path = __DIR__ . '/seller/edit_order.php'; // You may need to create this file
        $page_title = 'Edit Order';
        break;
    case 'seller/cancel_order':
        $page_path = __DIR__ . '/seller/cancel_order.php'; // You may need to create this file
        $page_title = 'Cancel Order';
        break;
    // ... other seller pages

    case 'unauthorized':
        $page_path = __DIR__ . '/pages/unauthorized.php';
        $page_title = 'Unauthorized Access';
        http_response_code(403);
        break;

    default:
        $page_path = __DIR__ . '/pages/404.php';
        $page_title = 'Page Not Found';
        http_response_code(404);
        break;
}

// --- Include Header, Page Content, and Footer ---
if (file_exists(__DIR__ . '/includes/header.php')) {
    require_once __DIR__ . '/includes/header.php';
} else {
    echo "<!DOCTYPE html><html><head><title>$page_title</title></head><body>"; // Minimal fallback
}

if (file_exists($page_path)) {
    require_once $page_path;
} else {
    // This case should ideally be caught by the default in switch, leading to 404.php
    // But as a fallback if $page_path is somehow empty or invalid after switch:
    http_response_code(404);
    if (file_exists(__DIR__ . '/pages/404.php')) {
        require_once __DIR__ . '/pages/404.php';
    } else {
        echo "<h1>404 - Page Not Found</h1><p>The requested page could not be found.</p>";
    }
}

if (file_exists(__DIR__ . '/includes/footer.php')) {
    require_once __DIR__ . '/includes/footer.php';
} else {
    echo "</body></html>"; // Minimal fallback
}

// Close the database connection
if (isset($conn) && $conn instanceof MySQLi) {
    $conn->close();
}
