<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json');

$response = [
    'success' => false,
    'products' => [],
    'pagination' => [
        'total_items' => 0,
        'total_pages' => 0,
        'current_page' => 1,
        'limit' => 12
    ]
];

$query = trim($_GET['query'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 12;
$offset = ($page - 1) * $limit;

if ($query === '') {
    $response['message'] = 'No search query provided.';
    echo json_encode($response);
    exit;
}

// Count total matching products
$sql_count = "SELECT COUNT(*) as total FROM products WHERE name LIKE ? OR description LIKE ?";
$stmt_count = $conn->prepare($sql_count);
$like_query = '%' . $query . '%';
$stmt_count->bind_param('ss', $like_query, $like_query);
$stmt_count->execute();
$res_count = $stmt_count->get_result();
$total_items = ($row = $res_count->fetch_assoc()) ? (int)$row['total'] : 0;
$stmt_count->close();

$total_pages = $limit > 0 ? ceil($total_items / $limit) : 1;

// Fetch matching products with their primary image
$sql = "SELECT p.id, p.name, p.price, p.description, (
            SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 ORDER BY created_at DESC LIMIT 1
        ) AS image_path
        FROM products p
        WHERE p.name LIKE ? OR p.description LIKE ?
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssii', $like_query, $like_query, $limit, $offset);
$stmt->execute();
$res = $stmt->get_result();
$products = [];
while ($row = $res->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();

$response['success'] = true;
$response['products'] = $products;
$response['pagination'] = [
    'total_items' => $total_items,
    'total_pages' => $total_pages,
    'current_page' => $page,
    'limit' => $limit
];
echo json_encode($response); 