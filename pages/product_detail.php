<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/product_functions.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login&message=Please login to view product details.');
    exit;
}

$product_id = $_GET['id'] ?? '';
if (!$product_id) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Product not found.<br><a href="index.php?page=home" class="btn btn-outline-primary mt-3">Go Back</a></div></div>';
    exit;
}

// Fetch product details
$sql = "SELECT p.*, u.name AS seller_name FROM products p JOIN users u ON p.seller_id = u.id WHERE p.id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    echo '<div class="container mt-5"><div class="alert alert-danger">Product not found.<br><a href="index.php?page=home" class="btn btn-outline-primary mt-3">Go Back</a></div></div>';
    exit;
}

// Fetch all images for the product
$images = [];
$sql_img = "SELECT image_path, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, created_at ASC";
$stmt_img = $conn->prepare($sql_img);
$stmt_img->bind_param('s', $product_id);
$stmt_img->execute();
$res_img = $stmt_img->get_result();
while ($row = $res_img->fetch_assoc()) {
    $images[] = $row['image_path'];
}
$stmt_img->close();

$main_image = $images[0] ?? 'assets/img/default_avatar.png';

$stock = (int)($product['stock_quantity'] ?? 0);
$is_out_of_stock = $stock < 1;

?>
<style>
.product-detail-title { color: #0d6efd; font-weight: 700; }
.product-detail-price { color: #0d6efd; font-size: 2rem; font-weight: 600; }
.product-detail-stock { font-size: 1.1rem; }
.product-detail-gallery img { border: 2px solid #e9ecef; transition: border 0.2s; }
.product-detail-gallery img:hover { border: 2px solid #0d6efd; }
</style>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="mb-3 text-center">
                <img id="mainProductImage" src="<?php echo htmlspecialchars($main_image); ?>" class="img-fluid border rounded shadow-sm" style="max-height:400px; object-fit:contain; background:#f8f9fa;" alt="Product image">
            </div>
            <?php if (count($images) > 1): ?>
                <div class="d-flex flex-row gap-2 justify-content-center product-detail-gallery">
                    <?php foreach ($images as $img): ?>
                        <img src="<?php echo htmlspecialchars($img); ?>" class="img-thumbnail product-thumb" style="width:70px; height:70px; object-fit:cover; cursor:pointer;" onclick="document.getElementById('mainProductImage').src=this.src;">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <h2 class="product-detail-title mb-2"><?php echo htmlspecialchars($product['name']); ?></h2>
            <p class="text-muted mb-1">By: <?php echo htmlspecialchars($product['seller_name']); ?></p>
            <div class="product-detail-price mb-3">₱<?php echo number_format($product['price'], 2); ?></div>
            <p class="mb-2"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            <p class="product-detail-stock mb-2">Stock: <span class="fw-bold <?php echo $is_out_of_stock ? 'text-danger' : 'text-success'; ?>"><?php echo $stock; ?></span></p>
            <form id="addToCartForm" class="mt-4">
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" min="1" max="<?php echo $stock; ?>" value="1" class="form-control" id="quantity" name="quantity" <?php echo $is_out_of_stock ? 'disabled' : ''; ?>>
                </div>
                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
                <button type="submit" class="btn btn-primary w-100" <?php echo $is_out_of_stock ? 'disabled style=\"background:#dc3545;border-color:#dc3545;\"' : ''; ?>><?php echo $is_out_of_stock ? 'Out of Stock' : 'Add to Cart'; ?></button>
            </form>
            <div id="addToCartMsg" class="mt-3"></div>
            <a href="index.php?page=home" class="btn btn-outline-secondary mt-3">Go Back</a>
        </div>
    </div>
</div>
<script>
document.getElementById('addToCartForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    fetch('api/cart/add_to_cart.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(res => res.json())
    .then(data => {
        const msgDiv = document.getElementById('addToCartMsg');
        if (data.success) {
            msgDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            updateCartBadge();
        } else {
            msgDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Could not add to cart. Please try again.') + '</div>';
        }
    })
    .catch(() => {
        document.getElementById('addToCartMsg').innerHTML = '<div class=\"alert alert-danger\">Could not add to cart. Please try again.</div>';
    });
});
function updateCartBadge() {
    fetch('api/cart/cart_count.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const badge = document.getElementById('cart-badge');
                if (badge) badge.textContent = data.count;
            }
        });
}
</script> 