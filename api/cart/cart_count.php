<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];
$sql = 'SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?';
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$count = (int)($row['total'] ?? 0);
$stmt->close();

echo json_encode(['success' => true, 'count' => $count]); 