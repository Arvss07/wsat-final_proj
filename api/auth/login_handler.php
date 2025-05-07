<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php'; // Establishes $conn and loads .env
require_once __DIR__ . '/../../utils/hash.php';      // For verifyPassword()

// Define a base URL for redirects, using APP_URL from .env
$base_url = rtrim($_ENV['APP_URL'] ?? 'http://localhost/wsat-final_proj', '/');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    // $remember_me = isset($_POST['remember_me']); // Logic for remember me not yet implemented

    $_SESSION['old_login_input'] = ['email' => $email];

    if (empty($email) || empty($password)) {
        header("Location: " . $base_url . "/index.php?page=login&error=Email+and+password+are+required.");
        exit;
    }

    // Fetch user and their role name
    $stmt = $conn->prepare(
        "SELECT u.id, u.name, u.email, u.password as hashed_password, u.is_blocked, r.name as role_name 
         FROM users u 
         JOIN roles r ON u.role_id = r.id 
         WHERE u.email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if ($user['is_blocked']) {
            header("Location: " . $base_url . "/index.php?page=login&error=Your+account+has+been+blocked.");
            exit;
        }

        if (verifyPassword($password, $user['hashed_password'])) {
            // Password is correct, login successful
            session_regenerate_id(true); // Prevent session fixation

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role_name']; // Store role name (e.g., "Admin", "Shopper")

            unset($_SESSION['old_login_input']); // Clear old input

            // Redirect logic
            $redirect_page = 'home'; // Default redirect
            if (isset($_SESSION['redirect_to'])) {
                // Attempt to parse the query string of the redirect_to URL
                $redirect_url_parts = parse_url($_SESSION['redirect_to']);
                if (isset($redirect_url_parts['query'])) {
                    parse_str($redirect_url_parts['query'], $query_params);
                    if (isset($query_params['page'])) {
                        $redirect_page = $query_params['page'];
                        // Potentially append other query params from the original redirect_to if needed
                        // For now, just using the page parameter for simplicity
                        $redirect_target = $base_url . "/index.php?page=" . urlencode($redirect_page);
                        unset($_SESSION['redirect_to']);
                        header("Location: " . $redirect_target);
                        exit;
                    }
                }
                // If parsing fails or no page param, fall through to role-based redirect
                unset($_SESSION['redirect_to']);
            }

            // Role-based default redirect if no specific redirect_to was handled
            switch ($user['role_name']) {
                case 'Admin':
                    $redirect_page = 'admin/dashboard';
                    break;
                case 'Seller':
                    $redirect_page = 'seller/dashboard'; // Or seller/products
                    break;
                case 'Shopper':
                default:
                    $redirect_page = 'home'; // Or profile, or products page
                    break;
            }
            header("Location: " . $base_url . "/index.php?page=" . $redirect_page);
            exit;
        } else {
            // Invalid password
            header("Location: " . $base_url . "/index.php?page=login&error=Invalid+email+or+password.");
            exit;
        }
    } else {
        // User not found
        header("Location: " . $base_url . "/index.php?page=login&error=Invalid+email+or+password.");
        exit;
    }
    $stmt->close();
} else {
    // Not a POST request
    header("Location: " . $base_url . "/index.php?page=login&error=Invalid+request+method.");
    exit;
}

$conn->close();
