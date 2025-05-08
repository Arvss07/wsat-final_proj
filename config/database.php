<?php

// Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables from .env file
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} else {
    // Fallback or error handling if .env file is not found
    // For production, you might want to ensure this doesn't happen
    // or have default fallbacks, but for development, an error is informative.
    die('Error: .env file not found. Please create one from .env.example.');
}

// Database credentials from environment variables
// $db_host = $_ENV['DB_HOST'] ?? 'localhost';
// $db_user = $_ENV['DB_USERNAME'] ?? 'root';
// $db_pass = $_ENV['DB_PASSWORD'] ?? '';
// $db_name = $_ENV['DB_DATABASE'] ?? 'wsat_finalP';
// $db_port = $_ENV['DB_PORT'] ?? 3306;

// TEMPORARY DATABASE CREDENTIALS (AIVEN)
$db_host = $_ENV['DB_AIVEN_HOST'];
$db_user = $_ENV['DB_AIVEN_USERNAME'];
$db_pass = $_ENV['DB_AIVEN_PASSWORD'];
$db_name = $_ENV['DB_AIVEN_DATABASE'];
$db_port = $_ENV['DB_AIVEN_PORT'];

// Create a new MySQLi connection
$conn = new MySQLi($db_host, $db_user, $db_pass, $db_name, (int)$db_port);

// Check connection
if ($conn->connect_error) {
    // Log the error to a file or error tracking system in a real application
    error_log("Connection failed: " . $conn->connect_error);
    // Display a user-friendly error message
    die("Database connection failed. Please try again later or contact support.");
}

// Set character set to utf8mb4 (recommended)
if (!$conn->set_charset("utf8mb4")) {
    error_log("Error loading character set utf8mb4: " . $conn->error);
    // Optionally, you could die here as well if charset is critical
}

// The $conn object is now available for use in any script that includes this file.
// Example: require_once __DIR__ . '/config/database.php';
//          $result = $conn->query("SELECT * FROM users");
