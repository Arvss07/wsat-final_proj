<?php
// seller/view_order_details.php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Seller', 'Admin'])) {
    header('Location: ../index.php?page=login&message=Access denied.');
    exit;
}

global $conn; // From index.php

$order_id = $_GET['order_id'] ?? null;
if (!$order_id) {
    header('Location: index.php?page=seller/manage_orders&error_message=Order ID is missing.');
    exit;
}

$seller_id = $_SESSION['user_id'];
$order_details = null;
$order_items = [];

// Fetch general order details
// Ensure the seller viewing the order is actually a seller of one of the items in the order, or an admin
$stmt_order = $conn->prepare("
    SELECT 
        o.*, 
        u_customer.name AS customer_name, 
        u_customer.email AS customer_email
    FROM orders o
    LEFT JOIN users u_customer ON o.user_id = u_customer.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.id = ? AND (p.seller_id = ? OR ? = 'Admin')
    LIMIT 1
");

if ($stmt_order) {
    $stmt_order->bind_param("sss", $order_id, $seller_id, $_SESSION['role']);
    $stmt_order->execute();
    $result_order = $stmt_order->get_result();
    $order_details = $result_order->fetch_assoc();
    $stmt_order->close();
} else {
    error_log("Error preparing order statement: " . $conn->error);
    // Handle error, maybe redirect with a message
}

if (!$order_details) {
    header('Location: index.php?page=seller/manage_orders&error_message=Order not found or you do not have permission to view it.');
    exit;
}

// Fetch order items specific to this seller for this order
// If Admin, they see all items. If Seller, they only see their items.
$sql_items = "
    SELECT oi.quantity, oi.price_at_purchase, p.name AS product_name, p.id AS product_id, pi.image_path
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE oi.order_id = ?
";
if ($_SESSION['role'] === 'Seller') {
    $sql_items .= " AND p.seller_id = ?";
}

$stmt_items = $conn->prepare($sql_items);

if ($stmt_items) {
    if ($_SESSION['role'] === 'Seller') {
        $stmt_items->bind_param("ss", $order_id, $seller_id);
    } else { // Admin
        $stmt_items->bind_param("s", $order_id);
    }
    $stmt_items->execute();
    $result_items = $stmt_items->get_result();
    while ($row = $result_items->fetch_assoc()) {
        $order_items[] = $row;
    }
    $stmt_items->close();
} else {
    error_log("Error preparing order items statement: " . $conn->error);
    // Handle error
}

function getStatusBadgeClassDetails($status)
{
    switch ($status) {
        case 'Pending':
            return 'secondary';
        case 'Awaiting Payment':
            return 'warning';
        case 'Processing':
            return 'info';
        case 'Shipped':
            return 'primary';
        case 'Delivered':
            return 'success';
        case 'Cancelled':
            return 'danger';
        default:
            return 'light';
    }
}

?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Order Details #<?php echo htmlspecialchars($order_details['id']); ?></h2>
        <a href="index.php?page=seller/manage_orders" class="btn btn-outline-secondary">Back to Orders</a>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            Order Information
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_details['id']); ?></p>
                    <p><strong>Order Date:</strong> <?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($order_details['order_date']))); ?></p>
                    <p><strong>Status:</strong>
                        <span class="badge bg-<?php echo getStatusBadgeClassDetails($order_details['status']); ?>">
                            <?php echo htmlspecialchars($order_details['status']); ?>
                        </span>
                    </p>
                    <p><strong>Total Amount:</strong> <?php echo htmlspecialchars(number_format((float)$order_details['total_amount'], 2)); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order_details['payment_method']); ?></p>
                    <?php if ($order_details['payment_method'] === 'E-Payment'): ?>
                        <p><strong>E-Payment Type:</strong> <?php echo htmlspecialchars($order_details['epayment_type'] ?? 'N/A'); ?></p>
                        <p><strong>E-Payment Ref ID:</strong> <?php echo htmlspecialchars($order_details['epayment_reference_id'] ?? 'N/A'); ?></p>
                        <p><strong>E-Payment Details:</strong> <?php echo nl2br(htmlspecialchars($order_details['epayment_details'] ?? 'N/A')); ?></p>
                    <?php endif; ?>
                    <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order_details['payment_status']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            Customer Information
        </div>
        <div class="card-body">
            <p><strong>Name:</strong> <?php echo htmlspecialchars($order_details['customer_name'] ?? 'Guest User'); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($order_details['customer_email'] ?? 'N/A'); ?></p>
            <hr>
            <h5>Shipping Address</h5>
            <p>
                <?php echo htmlspecialchars($order_details['shipping_street']); ?><br>
                <?php echo htmlspecialchars($order_details['shipping_city']); ?>, <?php echo htmlspecialchars($order_details['shipping_postal_code']); ?><br>
                <?php echo htmlspecialchars($order_details['shipping_country']); ?>
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            Items in this Order <?php echo ($_SESSION['role'] === 'Seller') ? "(Sold by You)" : "(All Items)"; ?>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Image</th>
                        <th>Quantity</th>
                        <th>Price at Purchase</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $seller_items_total = 0;
                    if (!empty($order_items)):
                    ?>
                        <?php foreach ($order_items as $item):
                            $subtotal = (float)$item['price_at_purchase'] * (int)$item['quantity'];
                            $seller_items_total += $subtotal;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td>
                                    <img src="<?php echo htmlspecialchars(empty($item['image_path']) ? 'assets/img/default_product.png' : $item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" style="width: 50px; height: auto;">
                                </td>
                                <td><?php echo htmlspecialchars((string)$item['quantity']); ?></td>
                                <td><?php echo htmlspecialchars(number_format((float)$item['price_at_purchase'], 2)); ?></td>
                                <td><?php echo htmlspecialchars(number_format($subtotal, 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No items found for this order <?php echo ($_SESSION['role'] === 'Seller') ? "sold by you." : "."; ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if ($_SESSION['role'] === 'Seller' && !empty($order_items)): ?>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end"><strong>Total for Your Items:</strong></td>
                            <td><strong><?php echo htmlspecialchars(number_format($seller_items_total, 2)); ?></strong></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <div class="mt-3 mb-3">
        <!-- Action buttons like update status could be here if not on the main list, or if more granular control is needed -->
        <!-- For now, actions are on the manage_orders.php page -->
    </div>

</div>