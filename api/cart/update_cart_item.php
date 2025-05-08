<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}
$user_id = $_SESSION['user_id'];
$cart_id = $_POST['cart_id'] ?? '';
$quantity = max(1, (int)($_POST['quantity'] ?? 1));

// Get cart item and product
$stmt = $conn->prepare('SELECT ci.product_id, p.price, p.stock_quantity FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.id = ? AND ci.user_id = ?');
$stmt->bind_param('ss', $cart_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
$item = $res->fetch_assoc();
$stmt->close();
if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Cart item not found.']);
    exit;
}
if ($quantity > $item['stock_quantity']) {
    echo json_encode(['success' => false, 'message' => 'Not enough stock.']);
    exit;
}
// Update cart item
$stmt = $conn->prepare('UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?');
$stmt->bind_param('iss', $quantity, $cart_id, $user_id);
$stmt->execute();
$stmt->close();
$subtotal = $item['price'] * $quantity;
// Get new cart total
$stmt = $conn->prepare('SELECT SUM(ci.quantity * p.price) as total FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = ?');
$stmt->bind_param('s', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$total = (float)($row['total'] ?? 0);
$stmt->close();
echo json_encode(['success' => true, 'subtotal' => $subtotal, 'total' => $total]); 