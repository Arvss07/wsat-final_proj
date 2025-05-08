<?php
// homepage_products_handler.php
// This script will handle requests for products to display on the homepage.

// Include necessary files
require_once __DIR__ . '/../../config/database.php'; // Adjusted path

// Logic to fetch homepage products
$products = [];
$error_message = null;
$limit = 12; // Number of products to fetch for the homepage

if ($conn) {
    // Fetch newest products with their primary image
    $sql = "SELECT p.id, p.name, p.description, p.price, p.stock_quantity, pi.image_path 
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE
            ORDER BY p.created_at DESC
            LIMIT ?";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $stmt->close();
    } else {
        // Log error instead of echoing directly in production
        error_log("Error fetching homepage products: " . $conn->error);
        $error_message = "Failed to retrieve homepage products.";
    }
    $conn->close();
} else {
    $error_message = "Database connection failed.";
}

// Set header to JSON
header('Content-Type: application/json');

// Return products as JSON
if ($error_message) {
    echo json_encode(['success' => false, 'message' => $error_message]);
} else {
    echo json_encode(['success' => true, 'data' => $products]);
}
