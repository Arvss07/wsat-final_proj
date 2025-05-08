<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/uuid.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login&message=Please login to checkout.');
    exit;
}
$user_id = $_SESSION['user_id'];

// Fetch addresses
$addresses = [];
$sql = "SELECT id, street, barangay, city, province, country, postal_code, is_default FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $addresses[] = $row;
}
$stmt->close();

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

if (empty($cart_items)) {
    echo '<div class="container mt-5"><div class="alert alert-info text-center">Your cart is empty.<br><a href="index.php?page=home" class="btn btn-primary mt-3">Shop Now</a></div></div>';
    exit;
}
if (empty($addresses)) {
    echo '<div class="container mt-5"><div class="alert alert-warning text-center">You have no saved addresses. Please add one in your account.<br><a href="index.php?page=account" class="btn btn-primary mt-3">Go to Account</a></div></div>';
    exit;
}
?>
<div class="container py-5">
    <h2 class="mb-4"><i class="bi bi-credit-card"></i> Checkout</h2>
    <form id="checkoutForm">
        <div class="mb-4">
            <label class="form-label fw-bold">Select Address</label>
            <select name="address_id" class="form-select" required>
                <?php foreach ($addresses as $addr): ?>
                    <option value="<?php echo htmlspecialchars($addr['id']); ?>">
                        <?php echo htmlspecialchars($addr['street'] . ', ' . $addr['barangay'] . ', ' . $addr['city'] . ', ' . $addr['province'] . ', ' . $addr['country'] . ' ' . $addr['postal_code']); ?><?php if ($addr['is_default']) echo ' (Default)'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-4">
            <label class="form-label fw-bold">Payment Method</label>
            <select name="payment_method" id="paymentMethod" class="form-select" required>
                <option value="Cash on Delivery">Cash on Delivery</option>
                <option value="E-Payment">E-Payment</option>
            </select>
        </div>
        <div id="ePaymentOptions" class="mb-4" style="display:none;">
            <label class="form-label fw-bold">E-Payment Type</label>
            <select name="epayment_type" id="epaymentType" class="form-select">
                <option value="GCash">GCash</option>
                <option value="Maya">Maya</option>
            </select>
            <div class="mt-2">
                <label class="form-label">Reference Number</label>
                <input type="text" id="epaymentRef" name="epayment_reference_id" class="form-control" readonly>
            </div>
        </div>
        <div class="mb-4">
            <h5>Order Summary</h5>
            <ul class="list-group mb-2">
                <?php foreach ($cart_items as $item): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                        <span>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="text-end fw-bold">Total: ₱<?php echo number_format($total, 2); ?></div>
        </div>
        <button type="submit" class="btn btn-success btn-lg w-100">Place Order</button>
        <div id="checkoutMsg" class="mt-3"></div>
    </form>
</div>
<!-- Order Placed Modal -->
<div class="modal fade" id="orderPlacedModal" tabindex="-1" aria-labelledby="orderPlacedModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="orderPlacedModalLabel">Order Placed!</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <p>Your order has been placed! The seller will see your order in their dashboard.</p>
        <a href="index.php?page=home" class="btn btn-primary mt-3">Continue Shopping</a>
      </div>
    </div>
  </div>
</div>
<script>
function generateReference() {
    fetch('api/cart/generate_reference.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('epaymentRef').value = data.reference;
            }
        });
}
document.getElementById('paymentMethod').addEventListener('change', function() {
    const ePay = document.getElementById('ePaymentOptions');
    if (this.value === 'E-Payment') {
        ePay.style.display = '';
        generateReference();
    } else {
        ePay.style.display = 'none';
        document.getElementById('epaymentRef').value = '';
    }
});
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    fetch('api/cart/checkout.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            var modal = new bootstrap.Modal(document.getElementById('orderPlacedModal'));
            modal.show();
            updateCartBadge();
        } else {
            document.getElementById('checkoutMsg').innerHTML = '<div class="alert alert-danger">' + (data.message || 'Could not place order.') + '</div>';
        }
    });
});
</script> 