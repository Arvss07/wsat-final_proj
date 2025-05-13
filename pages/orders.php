<?php
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Shopper') {
    header('Location: index.php?page=login&message=Please login as a shopper to view your orders.');
    exit;
}
$user_id = $_SESSION['user_id'];

// Fetch all orders for this user
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$orders = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch all order items for all orders
$order_items_map = [];
if ($orders) {
    $order_ids = array_column($orders, 'id');
    if ($order_ids) {
        $in = str_repeat('?,', count($order_ids) - 1) . '?';
        $types = str_repeat('s', count($order_ids));
        $sql = "SELECT oi.*, p.name, p.price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id IN ($in)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$order_ids);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $order_items_map[$row['order_id']][] = $row;
        }
        $stmt->close();
    }
}

// Filter orders for My Orders and Transaction History
$my_orders = array_filter($orders, function ($order) {
    return in_array($order['status'], ['Pending', 'Processing', 'Shipped']);
});
$history_orders = array_filter($orders, function ($order) {
    return in_array($order['status'], ['Delivered', 'Cancelled']);
});
?>
<style>
    .order-row {
        cursor: pointer;
    }

    .order-details {
        display: none;
        background: #f8f9fa;
    }

    .order-details.active {
        display: table-row;
    }
</style>
<div class="container py-5">
    <h2 class="mb-4"><i class="bi bi-list-check"></i> My Orders</h2>
    <?php if (empty($my_orders)): ?>
        <div class="alert alert-info text-center">You have no active orders.<br><a href="index.php?page=home" class="btn btn-primary mt-3">Shop Now</a></div>
    <?php else: ?>
        <div class="table-responsive mb-5">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th></th>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Payment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($my_orders as $order): ?>
                        <tr class="order-row" data-order-id="<?php echo htmlspecialchars($order['id']); ?>">
                            <td><i class="bi bi-chevron-down"></i></td>
                            <td><?php echo htmlspecialchars($order['id']); ?></td>
                            <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                            <td><?php echo htmlspecialchars($order['status']); ?></td>
                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                        </tr>
                        <tr class="order-details" id="details-<?php echo htmlspecialchars($order['id']); ?>">
                            <td colspan="6">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Items</h6>
                                        <ul class="list-group mb-2">
                                            <?php foreach ($order_items_map[$order['id']] ?? [] as $item): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                                                    <span>₱<?php echo number_format($item['price_at_purchase'] * $item['quantity'], 2); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Shipping Address</h6>
                                        <div class="mb-2">
                                            <?php echo htmlspecialchars($order['shipping_street'] . ', ' . $order['shipping_city'] . ', ' . $order['shipping_postal_code'] . ', ' . $order['shipping_country']); ?>
                                        </div>
                                        <h6>Payment</h6>
                                        <div>Method: <?php echo htmlspecialchars($order['payment_method']); ?></div>
                                        <?php if ($order['payment_method'] === 'E-Payment'): ?>
                                            <div>Type: <?php echo htmlspecialchars($order['epayment_type']); ?></div>
                                            <div>Reference: <span class="fw-bold text-primary"><?php echo htmlspecialchars($order['epayment_reference_id']); ?></span></div>
                                        <?php endif; ?>
                                        <div>Status: <span class="fw-bold"><?php echo htmlspecialchars($order['status']); ?></span></div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <h4 class="mb-3"><i class="bi bi-clock-history"></i> Transaction History</h4>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Order #</th>
                    <th>Payment</th>
                    <th>Reference</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history_orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($order['epayment_reference_id']); ?></td>
                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    document.querySelectorAll('.order-row').forEach(row => {
        row.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const detailsRow = document.getElementById('details-' + orderId);
            if (detailsRow.classList.contains('active')) {
                detailsRow.classList.remove('active');
                this.querySelector('i').classList.remove('bi-chevron-up');
                this.querySelector('i').classList.add('bi-chevron-down');
            } else {
                document.querySelectorAll('.order-details').forEach(r => r.classList.remove('active'));
                document.querySelectorAll('.order-row i').forEach(i => {
                    i.classList.remove('bi-chevron-up');
                    i.classList.add('bi-chevron-down');
                });
                detailsRow.classList.add('active');
                this.querySelector('i').classList.remove('bi-chevron-down');
                this.querySelector('i').classList.add('bi-chevron-up');
            }
        });
    });
</script>