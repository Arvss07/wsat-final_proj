<?php
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login&message=Please login to view your cart.');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch cart items
$sql = "SELECT ci.id as cart_id, ci.quantity, p.id as product_id, p.name, p.price, p.stock_quantity, pi.image_path
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE ci.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$cart_items = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<style>
.cart-table th, .cart-table td { vertical-align: middle; }
.cart-img-thumb { width: 70px; height: 70px; object-fit: cover; border-radius: 8px; background: #f8f9fa; }
.cart-remove-btn { color: #dc3545; cursor: pointer; }
.cart-remove-btn:hover { text-decoration: underline; }
</style>
<div class="container py-5">
    <h2 class="mb-4"><i class="bi bi-cart3"></i> My Cart</h2>
    <?php if (empty($cart_items)): ?>
        <div class="alert alert-info text-center">Your cart is empty.<br><a href="index.php?page=home" class="btn btn-primary mt-3">Shop Now</a></div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table cart-table align-middle">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cartBody">
                    <?php foreach ($cart_items as $item): ?>
                        <tr data-cart-id="<?php echo htmlspecialchars($item['cart_id']); ?>">
                            <td><img src="<?php echo htmlspecialchars($item['image_path'] ?? 'assets/img/default_avatar.png'); ?>" class="cart-img-thumb"></td>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>₱<?php echo number_format($item['price'], 2); ?></td>
                            <td style="max-width:100px;">
                                <input type="number" min="1" max="<?php echo $item['stock_quantity']; ?>" value="<?php echo $item['quantity']; ?>" class="form-control cart-qty-input" data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>">
                            </td>
                            <td class="cart-subtotal">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td><span class="cart-remove-btn" title="Remove">Remove</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end align-items-center mt-4">
            <h4 class="me-4">Total: <span id="cartTotal">₱<?php echo number_format($total, 2); ?></span></h4>
            <button id="checkoutBtn" class="btn btn-success btn-lg">Checkout</button>
        </div>
        <div id="cartMsg" class="mt-3"></div>
    <?php endif; ?>
</div>
<script>
// Update quantity
const cartBody = document.getElementById('cartBody');
if (cartBody) {
    cartBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('cart-qty-input')) {
            const row = e.target.closest('tr');
            const cartId = row.getAttribute('data-cart-id');
            const qty = parseInt(e.target.value);
            fetch('api/cart/update_cart_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `cart_id=${encodeURIComponent(cartId)}&quantity=${qty}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    row.querySelector('.cart-subtotal').textContent = '₱' + (data.subtotal).toFixed(2);
                    document.getElementById('cartTotal').textContent = '₱' + (data.total).toFixed(2);
                    updateCartBadge();
                } else {
                    document.getElementById('cartMsg').innerHTML = '<div class="alert alert-danger">' + (data.message || 'Could not update cart.') + '</div>';
                }
            });
        }
    });
    // Remove item
    cartBody.addEventListener('click', function(e) {
        if (e.target.classList.contains('cart-remove-btn')) {
            const row = e.target.closest('tr');
            const cartId = row.getAttribute('data-cart-id');
            fetch('api/cart/remove_cart_item.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `cart_id=${encodeURIComponent(cartId)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    row.remove();
                    document.getElementById('cartTotal').textContent = '₱' + (data.total).toFixed(2);
                    updateCartBadge();
                    if (cartBody.children.length === 0) {
                        location.reload();
                    }
                } else {
                    document.getElementById('cartMsg').innerHTML = '<div class="alert alert-danger">' + (data.message || 'Could not remove item.') + '</div>';
                }
            });
        }
    });
}
// Checkout
const checkoutBtn = document.getElementById('checkoutBtn');
if (checkoutBtn) {
    checkoutBtn.addEventListener('click', function() {
        fetch('api/cart/checkout.php', {
            method: 'POST',
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.querySelector('.container').innerHTML = '<div class="alert alert-success text-center"><h3>Order Placed!</h3><p>' + data.message + '</p><a href="index.php?page=home" class="btn btn-primary mt-3">Continue Shopping</a></div>';
                updateCartBadge();
            } else {
                document.getElementById('cartMsg').innerHTML = '<div class="alert alert-danger">' + (data.message || 'Could not place order.') + '</div>';
            }
        });
    });
}
</script> 