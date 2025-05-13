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

    .history-row {
        cursor: pointer;
    }

    .history-details {
        display: none;
        background: #f8f9fa;
    }

    .history-details.active {
        display: table-row;
    }

    /* Animate chevron */
    .order-row i,
    .history-row i {
        transition: transform 0.3s cubic-bezier(.4, 2, .6, 1), color 0.2s;
    }

    .order-row.active i,
    .history-row.active i {
        color: #0d6efd;
    }

    /* Add to cart button animation */
    .add-to-cart-btn {
        transition: background 0.2s, color 0.2s, box-shadow 0.2s;
        border-radius: 50%;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 8px;
        font-size: 1.2rem;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.08);
        background: #f8f9fa;
        color: #0d6efd;
        border: none;
        outline: none;
        cursor: pointer;
    }

    .add-to-cart-btn.btn-success {
        background: #198754 !important;
        color: #fff !important;
        box-shadow: 0 4px 16px rgba(25, 135, 84, 0.15);
    }

    .add-to-cart-btn.btn-danger {
        background: #dc3545 !important;
        color: #fff !important;
        box-shadow: 0 4px 16px rgba(220, 53, 69, 0.15);
    }

    .add-to-cart-btn i.spin {
        animation: spin 0.7s linear infinite;
    }

    @keyframes spin {
        100% {
            transform: rotate(360deg);
        }
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
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th></th>
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
                    <tr class="history-row" data-order-id="<?php echo htmlspecialchars($order['id']); ?>">
                        <td><i class="bi bi-chevron-down"></i></td>
                        <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($order['epayment_reference_id']); ?></td>
                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                    </tr>
                    <tr class="history-details" id="history-details-<?php echo htmlspecialchars($order['id']); ?>">
                        <td colspan="7">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Items</h6>
                                    <ul class="list-group mb-2">
                                        <?php foreach ($order_items_map[$order['id']] ?? [] as $item): ?>
                                            <li class="list-group-item d-flex align-items-center">
                                                <?php
                                                // Fetch product image (show first image if exists)
                                                $img_path = null;
                                                $img_sql = "SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, id ASC LIMIT 1";
                                                $img_stmt = $conn->prepare($img_sql);
                                                $img_stmt->bind_param('s', $item['product_id']);
                                                $img_stmt->execute();
                                                $img_stmt->bind_result($img_path);
                                                $img_stmt->fetch();
                                                $img_stmt->close();
                                                ?>
                                                <?php if ($img_path): ?>
                                                    <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Product Image" style="width:50px;height:50px;object-fit:cover;margin-right:10px;" class="img-thumbnail">
                                                <?php else: ?>
                                                    <img src="assets/img/oos.jpg" alt="No Image" style="width:50px;height:50px;object-fit:cover;margin-right:10px;" class="img-thumbnail">
                                                <?php endif; ?>
                                                <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                                                <span class="ms-auto">₱<?php echo number_format($item['price_at_purchase'] * $item['quantity'], 2); ?></span>
                                                <button class="add-to-cart-btn ms-2" title="Quick Add to Cart" data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>">
                                                    <i class="bi bi-cart-plus"></i>
                                                </button>
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

    // Transaction History row toggle
    document.querySelectorAll('.history-row').forEach(row => {
        row.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const detailsRow = document.getElementById('history-details-' + orderId);
            if (detailsRow.classList.contains('active')) {
                detailsRow.classList.remove('active');
                this.querySelector('i').classList.remove('bi-chevron-up');
                this.querySelector('i').classList.add('bi-chevron-down');
            } else {
                document.querySelectorAll('.history-details').forEach(r => r.classList.remove('active'));
                document.querySelectorAll('.history-row i').forEach(i => {
                    i.classList.remove('bi-chevron-up');
                    i.classList.add('bi-chevron-down');
                });
                detailsRow.classList.add('active');
                this.querySelector('i').classList.remove('bi-chevron-down');
                this.querySelector('i').classList.add('bi-chevron-up');
            }
        });
    });

    // Quick Add to Cart (for Transaction History items)
    document.querySelectorAll('.history-details .add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const productId = this.dataset.productId;
            const icon = this.querySelector('i');
            icon.classList.remove('bi-cart-plus');
            icon.classList.add('bi-arrow-repeat', 'spin');
            fetch('api/cart/add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `product_id=${encodeURIComponent(productId)}&quantity=1`
                })
                .then(res => res.json())
                .then(data => {
                    icon.classList.remove('bi-arrow-repeat', 'spin');
                    if (data.success) {
                        icon.classList.add('bi-cart-check');
                        btn.classList.add('btn-success');
                        setTimeout(() => {
                            icon.classList.remove('bi-cart-check');
                            icon.classList.add('bi-cart-plus');
                            btn.classList.remove('btn-success');
                        }, 1200);
                    } else {
                        icon.classList.add('bi-exclamation-triangle');
                        btn.classList.add('btn-danger');
                        setTimeout(() => {
                            icon.classList.remove('bi-exclamation-triangle');
                            icon.classList.add('bi-cart-plus');
                            btn.classList.remove('btn-danger');
                        }, 1200);
                    }
                })
                .catch(() => {
                    icon.classList.remove('bi-arrow-repeat', 'spin');
                    icon.classList.add('bi-exclamation-triangle');
                    btn.classList.add('btn-danger');
                    setTimeout(() => {
                        icon.classList.remove('bi-exclamation-triangle');
                        icon.classList.add('bi-cart-plus');
                        btn.classList.remove('btn-danger');
                    }, 1200);
                });
        });
    });
</script>