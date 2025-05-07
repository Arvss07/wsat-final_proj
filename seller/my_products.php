<?php
// Ensure user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Seller') {
    if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Seller') {
        echo "Unauthorized access.";
        exit();
    }
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
}

?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col">
            <h2>My Products Dashboard</h2>
        </div>
        <div class="col text-end">
            <a href="add_product.php" class="btn btn-success">
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
                <p class="text-center">You have not added any products yet. <a href="add_product.php">Add your first product!</a></p>
            <?php else: ?>
                <table class="table table-hover">
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
                                <td>$<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($product['stock_quantity']); ?></td>
                                <td>
                                    <a href="edit_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Edit</a>
                                    <a href="delete_product.php?id=<?php echo htmlspecialchars($product['id']); ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?');"><i class="fas fa-trash"></i> Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>