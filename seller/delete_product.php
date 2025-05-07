<?php
// Ensure user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Seller') {
    header("Location: index.php?page=login&message=You must be logged in as a seller to delete products.");
    exit();
}

// Ensure $conn is available
if (!isset($conn) || $conn->connect_error) {
    $_SESSION['error_message'] = "Database connection failed.";
    header("Location: index.php?page=seller/my_products");
    exit();
}

$product_id = $_GET['id'] ?? null;
$seller_id = $_SESSION['user_id'];

if (!$product_id) {
    $_SESSION['error_message'] = "No product ID specified for deletion.";
    header("Location: index.php?page=seller/my_products");
    exit();
}

try {
    $conn->begin_transaction();

    // Check if the product belongs to the seller
    $stmt_check = $conn->prepare("SELECT id FROM products WHERE id = ? AND seller_id = ?");
    if (!$stmt_check) throw new Exception("Prepare failed (check product): " . $conn->error);
    $stmt_check->bind_param("ss", $product_id, $seller_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows === 0) {
        $_SESSION['error_message'] = "Product not found or you do not have permission to delete it.";
        header("Location: index.php?page=seller/my_products");
        exit();
    }
    $stmt_check->close();

    // Get image paths to delete from server
    $stmt_img_paths = $conn->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
    if (!$stmt_img_paths) throw new Exception("Prepare failed (get image paths): " . $conn->error);
    $stmt_img_paths->bind_param("s", $product_id);
    $stmt_img_paths->execute();
    $result_img_paths = $stmt_img_paths->get_result();
    $image_paths_to_delete = [];
    while ($row = $result_img_paths->fetch_assoc()) {
        $image_paths_to_delete[] = __DIR__ . '/../' . $row['image_path'];
    }
    $stmt_img_paths->close();

    // Delete from product_images
    $stmt_del_images = $conn->prepare("DELETE FROM product_images WHERE product_id = ?");
    if (!$stmt_del_images) throw new Exception("Prepare failed (delete images): " . $conn->error);
    $stmt_del_images->bind_param("s", $product_id);
    if (!$stmt_del_images->execute()) {
        throw new Exception("Failed to delete product images: " . $stmt_del_images->error);
    }
    $stmt_del_images->close();

    // Delete from product_categories
    $stmt_del_cats = $conn->prepare("DELETE FROM product_categories WHERE product_id = ?");
    if (!$stmt_del_cats) throw new Exception("Prepare failed (delete categories): " . $conn->error);
    $stmt_del_cats->bind_param("s", $product_id);
    if (!$stmt_del_cats->execute()) {
        throw new Exception("Failed to delete product categories: " . $stmt_del_cats->error);
    }
    $stmt_del_cats->close();

    // Delete from products table
    $stmt_del_prod = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
    if (!$stmt_del_prod) throw new Exception("Prepare failed (delete product): " . $conn->error);
    $stmt_del_prod->bind_param("ss", $product_id, $seller_id);
    if (!$stmt_del_prod->execute()) {
        throw new Exception("Failed to delete product: " . $stmt_del_prod->error);
    }
    $stmt_del_prod->close();

    // If all DB operations are successful, delete actual image files
    foreach ($image_paths_to_delete as $file_path) {
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }

    $conn->commit();
    $_SESSION['success_message'] = "Product deleted successfully!";
} catch (Exception $e) {
    $conn->rollback();
    error_log("Product deletion failed: " . $e->getMessage());
    $_SESSION['error_message'] = "An error occurred while deleting the product. " . $e->getMessage();
}

header("Location: index.php?page=seller/my_products");
exit();
