<?php
// list_categories_handler.php
// This script will handle requests to list all product categories.

// Include necessary files
require_once __DIR__ . '/../../config/database.php'; // Adjusted path

// Logic to fetch all categories
$categories = [];
$error_message = null;

if ($conn) {
    $sql = "SELECT id, name, description FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        $result->free();
    } else {
        // Log error instead of echoing directly in production
        error_log("Error fetching categories: " . $conn->error);
        $error_message = "Failed to retrieve categories.";
    }
    $conn->close();
} else {
    $error_message = "Database connection failed.";
}

// Set header to JSON
header('Content-Type: application/json');

// Return categories as JSON
if ($error_message) {
    echo json_encode(['success' => false, 'message' => $error_message]);
} else {
    echo json_encode(['success' => true, 'data' => $categories]);
}
