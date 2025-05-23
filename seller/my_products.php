<?php
// Ensure user is logged in and is a seller
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=login&message=Please log in to view your products.");
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Seller') {
    header("Location: index.php?page=unauthorized&message=You do not have permission to access the products page. Seller access is required.");
    exit();
}

$seller_id = $_SESSION['user_id'];
$products = [];

// Ensure $conn is available (it should be, from index.php -> config/database.php)
if (!isset($conn) || $conn->connect_error) {
    echo "Database connection is not available or failed. Please check the configuration.";
    exit();
}

try {
    $sql = "SELECT 
                p.id, 
                p.name, 
                p.price, 
                p.stock_quantity, 
                pi.image_path AS primary_image_path 
            FROM 
                products p 
            LEFT JOIN 
                product_images pi ON p.id = pi.product_id AND pi.is_primary = TRUE 
            WHERE 
                p.seller_id = ? 
            ORDER BY 
                p.created_at DESC";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param('s', $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    error_log("Database error fetching seller products (MySQLi): " . $e->getMessage());
    // It might be good to set an error message to display to the user here too
    // $_SESSION['error_message'] = "Could not retrieve products at this time."; 
}

// Display and clear session messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;

if ($success_message) {
    unset($_SESSION['success_message']);
}
if ($error_message) {
    unset($_SESSION['error_message']);
}

?>

<div class="container mt-4">
    <?php if ($success_message): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col">
            <h2>My Products Dashboard</h2>
        </div>
        <div class="col text-end">
            <a href="index.php?page=seller/add_product" class="btn btn-success">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        </div>
    </div>

    <!-- Placeholder for Sales Overview/Charts -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Sales Overview
                </div>
                <div class="card-body">
                    <p class="text-muted"><em>Sales charts and summaries will be displayed here.</em></p>
                    <!-- Example: <canvas id="salesChart"></canvas> -->
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    Product Performance
                </div>
                <div class="card-body">
                    <p class="text-muted"><em>Key product performance metrics will be shown here.</em></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Product List Section -->
    <div class="card">
        <div class="card-header">
            <h4>My Product Listings</h4>
        </div>
        <div class="card-body">
            <?php if (empty($products)): ?>
                <p class="text-center">You have not added any products yet. <a href="index.php?page=seller/add_product">Add your first product!</a></p>
            <?php else: ?>
                <table id="productTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($product['primary_image_path'] ?? '../assets/img/placeholder.png'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>₱<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($product['stock_quantity']); ?></td>
                                <td>
                                    <a href="index.php?page=seller/view_product&id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a>
                                    <a href="index.php?page=seller/edit_product&id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="index.php?page=seller/delete_product&id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?');"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>