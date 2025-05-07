<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/mailer.php'; // For sending emails
require_once __DIR__ . '/../../utils/uuid.php'; // If needed for any operations, though unlikely here

// Ensure the user is a seller or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Seller', 'Admin'])) {
    // If accessed directly without proper session, redirect or show error
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied.');
}

global $conn;
$redirect_url = '../../index.php?page=seller/manage_orders';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['update_order_status']) || isset($_POST['cancel_order_action']))) {
    $order_id = $_POST['order_id'] ?? null;
    $new_status = $_POST['new_status'] ?? null;
    $tracking_number = $_POST['tracking_number'] ?? null;
    $custom_message = $_POST['custom_message'] ?? null;

    if (!$order_id || !$new_status) {
        header("Location: {$redirect_url}&error_message=Missing order ID or new status.");
        exit;
    }

    // Validate new_status against allowed ENUM values in DB to be safe
    $allowed_statuses = ['Pending', 'Awaiting Payment', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        header("Location: {$redirect_url}&error_message=Invalid order status provided.");
        exit;
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Fetch order details for notification and seller verification
        $stmt_order_info = $conn->prepare("
            SELECT o.user_id, o.status as current_db_status, u.email as customer_email, u.name as customer_name
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        if (!$stmt_order_info) throw new Exception("Failed to prepare order info statement: " . $conn->error);
        $stmt_order_info->bind_param("s", $order_id);
        $stmt_order_info->execute();
        $order_result = $stmt_order_info->get_result();
        $order_data = $order_result->fetch_assoc();
        $stmt_order_info->close();

        if (!$order_data) {
            throw new Exception("Order not found.");
        }

        // Security check: Ensure the seller has at least one product in the order
        if ($_SESSION['role'] === 'Seller') {
            $seller_id = $_SESSION['user_id'];
            $stmt_permission = $conn->prepare("
                SELECT 1
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ? AND p.seller_id = ?
                LIMIT 1
            ");
            if (!$stmt_permission) throw new Exception("Failed to prepare permission check statement: " . $conn->error);
            $stmt_permission->bind_param("ss", $order_id, $seller_id);
            $stmt_permission->execute();
            $permission_result = $stmt_permission->get_result();
            if ($permission_result->num_rows === 0) {
                throw new Exception("You do not have permission to modify this order.");
            }
            $stmt_permission->close();
        }

        $current_db_status = $order_data['current_db_status'];
        // Prevent updating status if it's already Cancelled or Delivered, unless the new status is the same (no change)
        if (($current_db_status === 'Cancelled' || $current_db_status === 'Delivered') && $current_db_status !== $new_status) {
            throw new Exception("Order is already {$current_db_status} and cannot be changed further.");
        }

        // Update order status
        $stmt_update_order = $conn->prepare("UPDATE orders SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if (!$stmt_update_order) throw new Exception("Failed to prepare update order statement: " . $conn->error);
        $stmt_update_order->bind_param("ss", $new_status, $order_id);
        $stmt_update_order->execute();
        $stmt_update_order->close();

        $email_subject = "";
        $email_body = "";
        $customer_name_for_email = $order_data['customer_name'] ?? 'Valued Customer';

        // If order is cancelled, restock items
        if ($new_status === 'Cancelled' && $current_db_status !== 'Cancelled') {
            $stmt_order_items = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            if (!$stmt_order_items) throw new Exception("Failed to prepare order items statement for stock update: " . $conn->error);
            $stmt_order_items->bind_param("s", $order_id);
            $stmt_order_items->execute();
            $items_result = $stmt_order_items->get_result();

            while ($item = $items_result->fetch_assoc()) {
                $stmt_update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                if (!$stmt_update_stock) throw new Exception("Failed to prepare update stock statement: " . $conn->error);
                $stmt_update_stock->bind_param("is", $item['quantity'], $item['product_id']);
                $stmt_update_stock->execute();
                $stmt_update_stock->close();
            }
            $stmt_order_items->close();
            $email_subject = "Order #{$order_id} Cancelled";
            $email_body = "Dear {$customer_name_for_email},<br><br>Your order with ID #{$order_id} has been cancelled. Any items from this order have been restocked. If you have any questions, please contact support.<br><br>Thank you.";
        }

        // If order is shipped, potentially use tracking number and custom message
        if ($new_status === 'Shipped') {
            $email_subject = "Order #{$order_id} Shipped!";
            $email_body = "Dear {$customer_name_for_email},<br><br>Your order with ID #{$order_id} has been shipped.";
            if (!empty($tracking_number)) {
                $email_body .= "<br>Tracking Number: " . htmlspecialchars($tracking_number);
            }
            if (!empty($custom_message)) {
                $email_body .= "<br><br>Message from seller:<br>" . nl2br(htmlspecialchars($custom_message));
            }
            $email_body .= "<br><br>Thank you for your purchase!";
        }

        // Generic status update email if not covered above
        if (empty($email_subject) && $new_status !== $current_db_status) {
            $email_subject = "Order #{$order_id} Status Update";
            $email_body = "Dear {$customer_name_for_email},<br><br>The status of your order with ID #{$order_id} has been updated to: <strong>{$new_status}</strong>.<br><br>Thank you.";
        }

        // Send email if customer email is available and subject/body are set
        if ($order_data['customer_email'] && !empty($email_subject) && !empty($email_body)) {
            // Use the new sendGenericEmail function
            if (function_exists('sendGenericEmail')) {
                sendGenericEmail(
                    $order_data['customer_email'],
                    $email_subject,
                    $email_body,
                    strip_tags($email_body), // Basic alt body
                    $customer_name_for_email
                );
                // Log email sending success or failure if needed
            } else {
                // Log that sendGenericEmail function doesn't exist, or handle error
                error_log("sendGenericEmail function not found. Email not sent for order {$order_id}.");
            }
        }

        $conn->commit();
        header("Location: {$redirect_url}&success_message=Order status updated successfully.");
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Order update failed: " . $e->getMessage() . " for order_id: " . $order_id . " by user_id: " . ($_SESSION['user_id'] ?? 'N/A'));
        header("Location: {$redirect_url}&error_message=Order update failed: " . urlencode($e->getMessage()));
    }
    exit;
} else {
    // Not a POST request or missing action parameter
    header("Location: {$redirect_url}&error_message=Invalid request.");
    exit;
}
