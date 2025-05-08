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
if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item.']);
    exit;
}
$stmt = $conn->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?');
$stmt->bind_param('ss', $cart_id, $user_id);
$stmt->execute();
$stmt->close();
// Get new cart total
$stmt = $conn->prepare('SELECT SUM(ci.quantity * p.price) as total FROM cart_items ci JOIN products p ON ci.product_id = p.id WHERE ci.user_id = ?');
$stmt->bind_param('s', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$total = (float)($row['total'] ?? 0);
$stmt->close();
echo json_encode(['success' => true, 'total' => $total]); 