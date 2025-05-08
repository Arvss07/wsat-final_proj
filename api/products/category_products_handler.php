<?php
// api/products/category_products_handler.php

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/database.php'; // Adjust path as needed
require_once __DIR__ . '/../../utils/uuid.php'; // For generate_uuid if ever needed, though not directly for fetching

// Function to fetch products by category (adapted from home.php)
function get_products_by_category(mysqli $conn, string $categoryId, int $limit = 12, int $offset = 0): array
{
    $products = [];
    $sql = "SELECT p.id, p.name, p.description, p.price, p.stock_quantity, pi.image_path 
            FROM products p 
            JOIN product_categories pc ON p.id = pc.product_id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE 
            WHERE pc.category_id = ?
            ORDER BY p.created_at DESC 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("sii", $categoryId, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        error_log("API MySQLi prepare failed (get_products_by_category): " . $conn->error);
    }
    return $products;
}

// Function to count products in a category (adapted from home.php)
function count_products_by_category(mysqli $conn, string $categoryId): int
{
    $count = 0;
    $sql = "SELECT COUNT(DISTINCT p.id) as total 
            FROM products p
            JOIN product_categories pc ON p.id = pc.product_id
            WHERE pc.category_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = (int) $row['total'];
        $stmt->close();
    } else {
        error_log("API MySQLi prepare failed (count_products_by_category_api): " . $conn->error);
    }
    return $count;
}

$response = [
    'success' => false,
    'message' => '',
    'data' => [
        'products' => [],
        'pagination' => [
            'total_items' => 0,
            'total_pages' => 0,
            'current_page' => 1,
            'limit' => 12
        ]
    ]
];

if (!$conn) {
    $response['message'] = 'Database connection failed.';
    echo json_encode($response);
    exit;
}

$category_id = $_GET['category_id'] ?? null;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;

if (empty($category_id)) {
    $response['message'] = 'Category ID is required.';
    echo json_encode($response);
    exit;
}

if ($page < 1) $page = 1;
if ($limit < 1) $limit = 12;
$offset = ($page - 1) * $limit;

$products = get_products_by_category($conn, $category_id, $limit, $offset);
$total_items = count_products_by_category($conn, $category_id);
$total_pages = ceil($total_items / $limit);

if ($products !== null) {
    $response['success'] = true;
    $response['data']['products'] = $products;
    $response['data']['pagination']['total_items'] = $total_items;
    $response['data']['pagination']['total_pages'] = (int)$total_pages;
    $response['data']['pagination']['current_page'] = $page;
    $response['data']['pagination']['limit'] = $limit;
} else {
    $response['message'] = 'Could not fetch products for the category.';
}

$conn->close();
echo json_encode($response);
