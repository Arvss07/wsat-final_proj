<?php
// Ensure user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Seller') {
    header("Location: index.php?page=login&message=You must be logged in as a seller to view this page.");
    exit();
}

// Ensure $conn is available
if (!isset($conn) || $conn->connect_error) {
    $error_message = "Database connection is not available or failed.";
    if (!isset($conn)) {
        require_once __DIR__ . '/../config/database.php';
    }
}

$product_id = $_GET['id'] ?? null;
$product = null;
$product_images = [];
$product_category_name = 'N/A';

if (!$product_id) {
    $_SESSION['error_message'] = "No product ID specified for viewing.";
    header("Location: index.php?page=seller/my_products");
    exit();
}

if (isset($conn) && !$conn->connect_error) {
    try {
        // Fetch product core details
        $stmt_product = $conn->prepare("SELECT p.*, s.name as seller_username FROM products p JOIN users s ON p.seller_id = s.id WHERE p.id = ? AND p.seller_id = ?");
        if (!$stmt_product) throw new Exception("Prepare failed (product): " . $conn->error);
        $stmt_product->bind_param("ss", $product_id, $_SESSION['user_id']);
        $stmt_product->execute();
        $result_product = $stmt_product->get_result();
        $product = $result_product->fetch_assoc();
        $stmt_product->close();

        if (!$product) {
            $_SESSION['error_message'] = "Product not found or you do not have permission to view it.";
            header("Location: index.php?page=seller/my_products");
            exit();
        }

        // Fetch product images
        $stmt_images = $conn->prepare("SELECT image_path, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, created_at ASC");
        if (!$stmt_images) throw new Exception("Prepare failed (images): " . $conn->error);
        $stmt_images->bind_param("s", $product_id);
        $stmt_images->execute();
        $result_images = $stmt_images->get_result();
        while ($row = $result_images->fetch_assoc()) {
            $product_images[] = $row;
        }
        $stmt_images->close();

        // Fetch product category
        $stmt_cat = $conn->prepare("SELECT c.name FROM product_categories pc JOIN categories c ON pc.category_id = c.id WHERE pc.product_id = ?");
        if (!$stmt_cat) throw new Exception("Prepare failed (category): " . $conn->error);
        $stmt_cat->bind_param("s", $product_id);
        $stmt_cat->execute();
        $result_cat = $stmt_cat->get_result();
        if ($row_cat = $result_cat->fetch_assoc()) {
            $product_category_name = $row_cat['name'];
        }
        $stmt_cat->close();
    } catch (Exception $e) {
        error_log("Error fetching product details for view: " . $e->getMessage());
        $error_message = "Could not load product details. Please try again later.";
    }
} else {
    $error_message = "Database connection error.";
}

$page_title = $product ? "View Product: " . htmlspecialchars($product['name']) : "View Product";

?>

<div class="container mt-4">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error_message; ?>
        </div>
        <a href="index.php?page=seller/my_products" class="btn btn-primary">Back to My Products</a>
    <?php elseif ($product): ?>
        <div class="row">
            <div class="col-md-8">
                <h2><?php echo htmlspecialchars($product['name']); ?></h2>
                <p class="text-muted">Category: <?php echo htmlspecialchars($product_category_name); ?></p>
                <hr>
                <h4>Product Description</h4>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                <hr>
                <p><strong>Price:</strong> ₱<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></p>
                <p><strong>Stock Quantity:</strong> <?php echo htmlspecialchars($product['stock_quantity']); ?></p>
                <p><strong>Date Added:</strong> <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($product['created_at']))); ?></p>
                <p><strong>Last Updated:</strong> <?php echo htmlspecialchars(date("F j, Y, g:i a", strtotime($product['updated_at']))); ?></p>

                <a href="index.php?page=seller/edit_product&id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Edit Product</a>
                <a href="index.php?page=seller/my_products" class="btn btn-secondary">Back to My Products</a>
            </div>
            <div class="col-md-4">
                <h4>Product Images</h4>
                <?php if (!empty($product_images)): ?>
                    <div id="productImageViewCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($product_images as $index => $image): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo htmlspecialchars($image['image_path']); ?>" class="d-block w-100 img-fluid" alt="Product Image <?php echo $index + 1; ?>" style="max-height: 400px; object-fit: contain;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($product_images) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#productImageViewCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#productImageViewCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php if (count($product_images) > 1): ?>
                        <div class="mt-2 d-flex flex-wrap justify-content-center">
                            <?php foreach ($product_images as $index => $image): ?>
                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>"
                                    class="img-thumbnail m-1"
                                    alt="Thumbnail <?php echo $index + 1; ?>"
                                    style="width: 60px; height: 60px; object-fit: cover; cursor: pointer;"
                                    data-bs-target="#productImageViewCarousel"
                                    data-bs-slide-to="<?php echo $index; ?>">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p>No images available for this product.</p>
                    <img src="../assets/img/placeholder.png" class="img-fluid" alt="Placeholder Image">
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning" role="alert">
            Product details could not be loaded. It might have been removed or an error occurred.
        </div>
        <a href="index.php?page=seller/my_products" class="btn btn-primary">Back to My Products</a>
    <?php endif; ?>
</div>

<script>
    // Optional: Add script for manual carousel control if Bootstrap JS is loaded
    document.addEventListener('DOMContentLoaded', function() {
        var imageCarousel = document.querySelector('#productImageViewCarousel');
        if (imageCarousel) {
            // Example: if you want to initialize it with options or attach event listeners
            // var carousel = new bootstrap.Carousel(imageCarousel, {
            //  interval: 5000,
            //  wrap: true
            // });
        }
    });
</script>