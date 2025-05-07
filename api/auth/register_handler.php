<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php'; // Establishes $conn and loads .env
require_once __DIR__ . '/../../utils/hash.php';      // For hashPassword()
require_once __DIR__ . '/../../utils/uuid.php';      // For generateUuidV4()

// Define a base URL for redirects, using APP_URL from .env
$base_url = rtrim($_ENV['APP_URL'] ?? 'http://localhost/wsat-final_proj', '/');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Retrieve and sanitize input
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role_name = $_POST['role'] ?? ''; // 'Shopper' or 'Seller'

    // Store input for repopulation in case of error
    $_SESSION['old_register_input'] = [
        'name' => $name,
        'email' => $email,
        'role' => $role_name
    ];

    // 2. Perform validation
    $errors = [];
    if (empty($name)) {
        $errors[] = "Full name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (empty($role_name) || !in_array($role_name, ['Shopper', 'Seller'])) {
        $errors[] = "Invalid role selected.";
    }

    if (!empty($errors)) {
        // Redirect back with errors
        $error_query = http_build_query(['error' => implode("<br>", $errors)]);
        header("Location: " . $base_url . "/index.php?page=register&" . $error_query);
        exit;
    }

    // 3. Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        unset($_SESSION['old_register_input']['email']); // Don't repopulate email if it exists
        header("Location: " . $base_url . "/index.php?page=register&error=Email+already+exists.");
        exit;
    }
    $stmt->close();

    // 4. Get role_id from roles table
    $stmt = $conn->prepare("SELECT id FROM roles WHERE name = ?");
    $stmt->bind_param("s", $role_name);
    $stmt->execute();
    $role_result = $stmt->get_result();
    if ($role_result->num_rows === 0) {
        header("Location: " . $base_url . "/index.php?page=register&error=Invalid+role+specified.");
        exit;
    }
    $role_row = $role_result->fetch_assoc();
    $role_id = $role_row['id'];
    $stmt->close();

    // 5. Generate UUID and hash password
    $user_id = generateUuidV4();
    $hashed_password = hashPassword($password);

    if ($hashed_password === false) {
        header("Location: " . $base_url . "/index.php?page=register&error=Password+hashing+failed.");
        exit;
    }

    // 6. Insert new user into the database
    $insert_stmt = $conn->prepare("INSERT INTO users (id, name, email, password, role_id) VALUES (?, ?, ?, ?, ?)");
    $insert_stmt->bind_param("sssss", $user_id, $name, $email, $hashed_password, $role_id);

    if ($insert_stmt->execute()) {
        unset($_SESSION['old_register_input']); // Clear old input on success
        header("Location: " . $base_url . "/index.php?page=login&success=Registration+successful.+Please+login.");
        exit;
    } else {
        // Log error: $insert_stmt->error
        error_log("User registration failed: " . $insert_stmt->error);
        header("Location: " . $base_url . "/index.php?page=register&error=Registration+failed.+Please+try+again.");
        exit;
    }
    $insert_stmt->close();
} else {
    // Not a POST request, redirect to registration page or show an error
    header("Location: " . $base_url . "/index.php?page=register&error=Invalid+request+method.");
    exit;
}

$conn->close();
