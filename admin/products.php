<?php
// admin/products.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php?page=login&error=Unauthorized+access");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/uuid.php';

$page_title = "Manage Products (Admin View)";

$action = $_GET['action'] ?? 'list'; // list, (admin does not add/edit products directly, only views/removes)
$product_id_action = $_GET['id'] ?? null;

$feedback = [];
$errors = [];

// Handle POST requests for delete/soft-delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    try {
        if (isset($_POST['deleteProduct'])) {
            $product_id_to_delete = $_POST['product_id_to_delete'] ?? '';
            $delete_type = $_POST['delete_type'] ?? 'soft'; // 'soft' or 'permanent'

            if (!empty($product_id_to_delete)) {
                if ($delete_type === 'permanent') {
                    // Permanent delete: also remove from product_categories, product_images before products
                    $stmt_cat = $conn->prepare("DELETE FROM product_categories WHERE product_id = ?");
                    $stmt_cat->bind_param("s", $product_id_to_delete);
                    $stmt_cat->execute();
                    $stmt_cat->close();

                    $stmt_img = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
                    // TODO: Also delete actual image files from server for product_images
                    $stmt_img->bind_param("s", $product_id_to_delete);
                    $stmt_img->execute();
                    $stmt_img->close();

                    $stmt_delete = $conn->prepare("DELETE FROM products WHERE id = ?");
                    $stmt_delete->bind_param("s", $product_id_to_delete);
                    if ($stmt_delete->execute()) {
                        $feedback[] = "Product permanently deleted successfully.";
                    } else {
                        $errors[] = "Failed to permanently delete product: " . $stmt_delete->error;
                    }
                    $stmt_delete->close();
                } else { // Soft delete (mark as inactive - assuming an 'is_active' column)
                    // The current schema does not have an 'is_active' or similar for soft delete on products table.
                    // For now, permanent delete is the only option shown to admin for products.
                    // If soft delete is implemented, it would be: UPDATE products SET is_active = 0 WHERE id = ?
                    $errors[] = "Soft delete not yet implemented. Choose permanent delete.";
                }
            } else {
                $errors[] = "Product ID not provided for deletion.";
            }
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "An error occurred: " . $e->getMessage();
    }
}

?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo htmlspecialchars($page_title); ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=admin/dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Products</li>
    </ol>

    <?php if (!empty($feedback)): ?>
        <div class="alert alert-success">
            <?php foreach ($feedback as $msg): echo htmlspecialchars($msg) . "<br>";
            endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): echo htmlspecialchars($err) . "<br>";
            endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-box-seam me-1"></i>
            Product List (All Sellers)
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="productsDataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Seller</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql_all_products = "SELECT p.id, p.name, p.price, p.stock_quantity, p.created_at, u.name as seller_name 
                                           FROM products p 
                                           JOIN users u ON p.seller_id = u.id 
                                           ORDER BY p.created_at DESC";
                        $result_all_products = $conn->query($sql_all_products);
                        if ($result_all_products && $result_all_products->num_rows > 0) {
                            while ($product = $result_all_products->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($product['seller_name']) . "</td>";
                                echo "<td>$" . htmlspecialchars(number_format((float)$product['price'], 2)) . "</td>";
                                echo "<td>" . htmlspecialchars($product['stock_quantity']) . "</td>";
                                echo "<td>" . htmlspecialchars(date("M d, Y", strtotime($product['created_at']))) . "</td>";
                                echo "<td>";
                                // Admin can view product (link to a public product page if exists)
                                // echo "<a href='index.php?page=product&id=" . htmlspecialchars($product['id']) . "' class='btn btn-sm btn-info me-1' title='View' target='_blank'><i class='bi bi-eye-fill'></i></a>";

                                // Permanent Delete Form
                                echo "<form method='POST' action='index.php?page=admin/products' style='display: inline-block;' onsubmit=\"return confirm('Are you sure you want to PERMANENTLY delete this product? This action cannot be undone.');\">";
                                echo "<input type='hidden' name='product_id_to_delete' value='" . htmlspecialchars($product['id']) . "'>";
                                echo "<input type='hidden' name='delete_type' value='permanent'>";
                                echo "<button type='submit' name='deleteProduct' class='btn btn-sm btn-danger' title='Permanent Delete'><i class='bi bi-trash-fill'></i></button>";
                                echo "</form>";
                                // TODO: Add soft delete option if 'is_active' field is added to products table
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No products found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <p class="text-muted"><small>Note: Admin product management currently supports permanent deletion. Soft deletion (marking as inactive) can be added if an 'is_active' field is introduced to the products table. DataTables JS library is not yet integrated for advanced table features.</small></p>
</div>

<!-- Include DataTables CSS/JS in footer if you decide to use it -->