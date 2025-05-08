<?php
// includes/components/product_card.php
// Displays a single product card.
// Expects:
// - $product: An array containing product details (id, name, price, image_path).
// - $app_url: The base URL of the application.

if (!isset($product) || !is_array($product) || !isset($app_url)) {
    // Log error or handle more gracefully in a real application
    // error_log("Product card component: Missing required variables or product is not an array.");
    return; // Do not render anything if essential data is missing
}

$product_id = htmlspecialchars($product['id'] ?? 'unknown-product');
$product_name = htmlspecialchars($product['name'] ?? 'Unnamed Product');
$product_price = htmlspecialchars(number_format($product['price'] ?? 0, 2));
// Use default_avatar.png as fallback if placeholder.png is not available
$image_path = htmlspecialchars($app_url . ($product['image_path'] ?? 'assets/img/default_avatar.png'));
$alt_text = $product_name . " image";
$details_link = htmlspecialchars($app_url . 'index.php?page=product_detail&id=' . $product_id);

?>
<div class="col mb-4">
    <div class="card h-100 shadow-sm">
        <a href="<?php echo $details_link; ?>">
            <img src="<?php echo $image_path; ?>" class="card-img-top" alt="<?php echo $alt_text; ?>" style="height: 200px; object-fit: cover;">
        </a>
        <div class="card-body d-flex flex-column">
            <h5 class="card-title">
                <a href="<?php echo $details_link; ?>" class="text-decoration-none text-dark"><?php echo $product_name; ?></a>
            </h5>
            <p class="card-text text-muted">Price: ₱<?php echo $product_price; ?></p>
            <div class="mt-auto">
                <a href="<?php echo $details_link; ?>" class="btn btn-primary w-100">View Details</a>
            </div>
        </div>
    </div>
</div>