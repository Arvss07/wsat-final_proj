<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to add to cart.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = $_POST['product_id'] ?? '';
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    exit;
}

// Check stock
$stmt = $conn->prepare('SELECT stock_quantity FROM products WHERE id = ?');
$stmt->bind_param('s', $product_id);
$stmt->execute();
$res = $stmt->get_result();
$product = $res->fetch_assoc();
$stmt->close();
if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}
if ($product['stock_quantity'] < $quantity) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
    exit;
}

// Check if already in cart
$stmt = $conn->prepare('SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?');
$stmt->bind_param('ss', $user_id, $product_id);
$stmt->execute();
$res = $stmt->get_result();
$cart_item = $res->fetch_assoc();
$stmt->close();

if ($cart_item) {
    // Update quantity (but not above stock)
    $new_qty = min($product['stock_quantity'], $cart_item['quantity'] + $quantity);
    $stmt = $conn->prepare('UPDATE cart_items SET quantity = ? WHERE id = ?');
    $stmt->bind_param('is', $new_qty, $cart_item['id']);
    $stmt->execute();
    $stmt->close();
} else {
    // Insert new cart item
    require_once __DIR__ . '/../../utils/uuid.php';
    $cart_id = generateUuidV4();
    $stmt = $conn->prepare('INSERT INTO cart_items (id, user_id, product_id, quantity) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('sssi', $cart_id, $user_id, $product_id, $quantity);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true, 'message' => 'Added to cart!']); 