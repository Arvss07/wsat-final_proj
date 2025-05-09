<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/uuid.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}
$user_id = $_SESSION['user_id'];

$address_id = $_POST['address_id'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'Cash on Delivery';
$epayment_type = $_POST['epayment_type'] ?? null;
$epayment_reference_id = $_POST['epayment_reference_id'] ?? null;

// Fetch address
$address = null;
if ($address_id) {
    $stmt = $conn->prepare('SELECT * FROM addresses WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ss', $address_id, $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $address = $res->fetch_assoc();
    $stmt->close();
}
if (!$address) {
    echo json_encode(['success' => false, 'message' => 'Invalid address.']);
    exit;
}

// Fetch cart items
$sql = "SELECT ci.id as cart_id, ci.quantity, p.id as product_id, p.price, p.stock_quantity
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$cart_items = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Your cart is empty.']);
    exit;
}

// Check stock for all items
foreach ($cart_items as $item) {
    if ($item['quantity'] > $item['stock_quantity']) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock for one or more items.']);
        exit;
    }
}

// Create order
$order_id = generateUuidV4();
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}
$sql = "INSERT INTO orders (id, user_id, total_amount, payment_method, payment_status, shipping_street, shipping_barangay, shipping_city, shipping_province, shipping_country, shipping_postal_code, status, epayment_type, epayment_reference_id) VALUES (?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, 'Pending', ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssdsssssssss', $order_id, $user_id, $total_amount, $payment_method, $address['street'], $address['barangay'], $address['city'], $address['province'], $address['country'], $address['postal_code'], $epayment_type, $epayment_reference_id);
$stmt->execute();
$stmt->close();



// Insert order items and update stock
foreach ($cart_items as $item) {
    $order_item_id = generateUuidV4();
    $sql = "INSERT INTO order_items (id, order_id, product_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssii', $order_item_id, $order_id, $item['product_id'], $item['quantity'], $item['price']);
    $stmt->execute();
    $stmt->close();
    // Update product stock
    $sql = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $item['quantity'], $item['product_id']);
    $stmt->execute();
    $stmt->close();
}
// Clear cart
$stmt = $conn->prepare('DELETE FROM cart_items WHERE user_id = ?');
$stmt->bind_param('s', $user_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Your order has been placed! The seller will see your order in their dashboard.']);
