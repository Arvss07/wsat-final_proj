<?php
// Ensure user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Seller') {
    header("Location: index.php?page=login&message=You must be logged in as a seller to edit products.");
    exit();
}

require_once __DIR__ . '/../utils/uuid.php'; // Include UUID generator

// Ensure $conn is available
if (!isset($conn) || $conn->connect_error) {
    $error_message = "Database connection is not available or failed.";
    // Attempt to re-establish connection if not set
    if (!isset($conn)) {
        require_once __DIR__ . '/../config/database.php';
        if (!isset($conn) || $conn->connect_error) {
            // Still not available, display error and exit or handle appropriately
            // For now, we'll let the page display a generic error if $conn is still null later
        }
    }
}

$product_id = $_GET['id'] ?? null;
$product = null;
$current_images = [];
$current_category_id = null;

if (!$product_id) {
    $_SESSION['error_message'] = "No product ID specified for editing.";
    header("Location: index.php?page=seller/my_products");
    exit();
}

// Fetch product details
if (isset($conn)) {
    try {
        // Fetch product core details
        $stmt_product = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
        if (!$stmt_product) throw new Exception("Prepare failed (product): " . $conn->error);
        $stmt_product->bind_param("ss", $product_id, $_SESSION['user_id']);
        $stmt_product->execute();
        $result_product = $stmt_product->get_result();
        $product = $result_product->fetch_assoc();
        $stmt_product->close();

        if (!$product) {
            $_SESSION['error_message'] = "Product not found or you do not have permission to edit it.";
            header("Location: index.php?page=seller/my_products");
            exit();
        }

        // Fetch product images
        $stmt_images = $conn->prepare("SELECT id, image_path, is_primary FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, created_at ASC");
        if (!$stmt_images) throw new Exception("Prepare failed (images): " . $conn->error);
        $stmt_images->bind_param("s", $product_id);
        $stmt_images->execute();
        $result_images = $stmt_images->get_result();
        while ($row = $result_images->fetch_assoc()) {
            $current_images[] = $row;
        }
        $stmt_images->close();

        // Fetch product category
        $stmt_cat = $conn->prepare("SELECT category_id FROM product_categories WHERE product_id = ?");
        if (!$stmt_cat) throw new Exception("Prepare failed (category): " . $conn->error);
        $stmt_cat->bind_param("s", $product_id);
        $stmt_cat->execute();
        $result_cat = $stmt_cat->get_result();
        if ($row_cat = $result_cat->fetch_assoc()) {
            $current_category_id = $row_cat['category_id'];
        }
        $stmt_cat->close();
    } catch (Exception $e) {
        error_log("Error fetching product details for edit: " . $e->getMessage());
        $error_message = "Could not load product details. Please try again later.";
        // Prevent further execution if product details can't be loaded
    }
}


// Fetch categories for the dropdown
$categories = [];
if (isset($conn)) {
    try {
        $category_sql = "SELECT id, name FROM categories ORDER BY name ASC";
        $category_result = $conn->query($category_sql);
        if ($category_result) {
            while ($row = $category_result->fetch_assoc()) {
                $categories[] = $row;
            }
        } else {
            throw new Exception("Failed to fetch categories: " . $conn->error);
        }
    } catch (Exception $e) {
        error_log("Database error fetching categories: " . $e->getMessage());
        $error_message = isset($error_message) ? $error_message . "<br>" : ""; // Append if other errors exist
        $error_message .= "Could not load categories for selection.";
    }
}


// Handle form submission (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($product)) {
    $product_name = trim($_POST['product_name'] ?? $product['name']);
    $product_description = trim($_POST['product_description'] ?? $product['description']);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);
    $new_category_id = trim($_POST['category_id'] ?? $current_category_id);

    $errors = [];

    // --- Validation ---
    if (empty($product_name)) $errors[] = "Product name is required.";
    if (empty($product_description)) $errors[] = "Product description is required.";
    if ($price === false || $price <= 0) $errors[] = "Valid price is required.";
    if ($stock_quantity === false || $stock_quantity < 0) $errors[] = "Valid stock quantity is required.";
    if (empty($new_category_id)) $errors[] = "Category is required.";


    // --- File Upload Handling (for new images) ---
    $uploaded_images_paths_new = []; // Store paths of newly uploaded and converted images
    $image_files = $_FILES['product_images_new'] ?? null;
    $num_new_files = $image_files ? count($image_files['name']) : 0;
    $upload_dir = __DIR__ . '/../uploads/products/';
    $avif_support_needed_for_new = false;

    if ($num_new_files > 0 && $image_files['error'][0] !== UPLOAD_ERR_NO_FILE) {
        for ($i = 0; $i < $num_new_files; $i++) {
            if ($image_files['error'][$i] === UPLOAD_ERR_OK) {
                $tmp_name_check = $image_files['tmp_name'][$i];
                $file_mime_type_check = mime_content_type($tmp_name_check);
                if ($file_mime_type_check !== 'image/avif') {
                    $avif_support_needed_for_new = true;
                    break;
                }
            }
        }

        if ($avif_support_needed_for_new && !function_exists('imageavif')) {
            $errors[] = "Server does not support AVIF image conversion for new non-AVIF files. Please upload AVIF directly or contact support.";
        }
    }

    // --- Image Deletion Handling ---
    $images_to_delete = $_POST['delete_images'] ?? [];

    if (empty($errors)) { // Proceed only if AVIF support is present (if needed for new files) or no errors yet
        // Process new image uploads
        if ($num_new_files > 0 && $image_files['error'][0] !== UPLOAD_ERR_NO_FILE) {
            for ($i = 0; $i < $num_new_files; $i++) {
                if ($image_files['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $image_files['tmp_name'][$i];
                    $original_name = $image_files['name'][$i];
                    $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
                    $file_mime_type = mime_content_type($tmp_name);

                    if (!in_array($file_mime_type, $allowed_mime_types)) {
                        $errors[] = "Invalid file type for " . htmlspecialchars($original_name) . ". Only JPG, PNG, GIF, WEBP, or AVIF are allowed.";
                        continue;
                    }

                    $avif_filename = uniqid('product_', true) . '.avif';
                    $avif_destination = $upload_dir . $avif_filename;

                    if ($file_mime_type === 'image/avif') {
                        if (move_uploaded_file($tmp_name, $avif_destination)) {
                            $uploaded_images_paths_new[] = ['path' => 'uploads/products/' . $avif_filename, 'is_primary' => false]; // New images are not primary by default
                        } else {
                            $errors[] = "Failed to move uploaded AVIF file: " . htmlspecialchars($original_name);
                        }
                    } else {
                        if (!function_exists('imageavif')) {
                            $errors[] = "AVIF conversion support is not available on the server (new images).";
                            continue;
                        }
                        $source_image = null;
                        switch ($file_mime_type) {
                            case 'image/jpeg':
                                $source_image = @imagecreatefromjpeg($tmp_name);
                                break;
                            case 'image/png':
                                $source_image = @imagecreatefrompng($tmp_name);
                                break;
                            case 'image/gif':
                                $source_image = @imagecreatefromgif($tmp_name);
                                break;
                            case 'image/webp':
                                $source_image = @imagecreatefromwebp($tmp_name);
                                break;
                        }

                        if (!$source_image) {
                            $errors[] = "Failed to read image file for conversion: " . htmlspecialchars($original_name);
                            continue;
                        }
                        if (imageavif($source_image, $avif_destination)) {
                            $uploaded_images_paths_new[] = ['path' => 'uploads/products/' . $avif_filename, 'is_primary' => false];
                        } else {
                            $errors[] = "Failed to convert image " . htmlspecialchars($original_name) . " to AVIF format.";
                        }
                        imagedestroy($source_image);
                    }
                } elseif ($image_files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $errors[] = "Error with new file " . htmlspecialchars($image_files['name'][$i]) . ": Error code " . $image_files['error'][$i];
                }
            }
        }
    }


    // --- Database Update ---
    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            // Update product details
            $sql_update_product = "UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ? WHERE id = ? AND seller_id = ?";
            $stmt_update_product = $conn->prepare($sql_update_product);
            if (!$stmt_update_product) throw new Exception("Prepare failed (update product): " . $conn->error);
            $stmt_update_product->bind_param("ssdiss", $product_name, $product_description, $price, $stock_quantity, $product_id, $_SESSION['user_id']);
            if (!$stmt_update_product->execute()) {
                throw new Exception("Failed to update product: " . $stmt_update_product->error);
            }
            $stmt_update_product->close();

            // Update product category
            // First, remove existing category mapping if it exists
            $stmt_delete_old_cat = $conn->prepare("DELETE FROM product_categories WHERE product_id = ?");
            if (!$stmt_delete_old_cat) throw new Exception("Prepare failed (delete old category): " . $conn->error);
            $stmt_delete_old_cat->bind_param("s", $product_id);
            $stmt_delete_old_cat->execute();
            $stmt_delete_old_cat->close();
            // Then, insert new category mapping
            if (!empty($new_category_id)) {
                $sql_prod_cat = "INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)";
                $stmt_prod_cat = $conn->prepare($sql_prod_cat);
                if (!$stmt_prod_cat) throw new Exception("Prepare failed (product_categories): " . $conn->error);
                $stmt_prod_cat->bind_param("ss", $product_id, $new_category_id);
                if (!$stmt_prod_cat->execute()) {
                    throw new Exception("Failed to update product category: " . $stmt_prod_cat->error);
                }
                $stmt_prod_cat->close();
            }

            // Delete marked images
            if (!empty($images_to_delete)) {
                $image_delete_placeholders = implode(',', array_fill(0, count($images_to_delete), '?'));
                $sql_delete_images = "SELECT image_path FROM product_images WHERE id IN ($image_delete_placeholders) AND product_id = ?";
                $stmt_select_deleted = $conn->prepare($sql_delete_images);
                if (!$stmt_select_deleted) throw new Exception("Prepare failed (select deleted images): " . $conn->error);
                $types = str_repeat('s', count($images_to_delete)) . 's';
                $params_for_select_deleted = $images_to_delete;
                $params_for_select_deleted[] = $product_id; // Add product_id to the array of parameters
                $stmt_select_deleted->bind_param($types, ...$params_for_select_deleted);
                $stmt_select_deleted->execute();
                $result_deleted_paths = $stmt_select_deleted->get_result();
                $paths_to_unlink = [];
                while ($row = $result_deleted_paths->fetch_assoc()) {
                    $paths_to_unlink[] = __DIR__ . '/../' . $row['image_path'];
                }
                $stmt_select_deleted->close();

                $stmt_delete_img = $conn->prepare("DELETE FROM product_images WHERE id IN ($image_delete_placeholders) AND product_id = ?");
                if (!$stmt_delete_img) throw new Exception("Prepare failed (delete images): " . $conn->error);
                $params_for_delete = $images_to_delete;
                $params_for_delete[] = $product_id; // Add product_id to the array of parameters
                $stmt_delete_img->bind_param($types, ...$params_for_delete);
                if (!$stmt_delete_img->execute()) {
                    throw new Exception("Failed to delete images from database: " . $stmt_delete_img->error);
                }
                $stmt_delete_img->close();

                // Actually delete files from server
                foreach ($paths_to_unlink as $file_path_to_unlink) {
                    if (file_exists($file_path_to_unlink)) {
                        unlink($file_path_to_unlink);
                    }
                }
            }

            // Add new images
            if (!empty($uploaded_images_paths_new)) {
                $image_sql = "INSERT INTO product_images (id, product_id, image_path, is_primary) VALUES (?, ?, ?, ?)";
                $image_stmt = $conn->prepare($image_sql);
                if (!$image_stmt) throw new Exception("Prepare failed (insert new images): " . $conn->error);

                // Determine if a primary image needs to be set from new uploads
                // This happens if all old images were deleted OR if there were no old images and new ones are added.
                $existing_images_after_delete = count($current_images) - count($images_to_delete);
                $set_new_primary = ($existing_images_after_delete <= 0);

                foreach ($uploaded_images_paths_new as $idx => $image_data) {
                    $image_id_uuid = generateUuidV4();
                    $is_primary_flag = ($set_new_primary && $idx === 0) ? 1 : 0; // Set first new image as primary if needed
                    $image_stmt->bind_param("sssi", $image_id_uuid, $product_id, $image_data['path'], $is_primary_flag);
                    if (!$image_stmt->execute()) {
                        throw new Exception("Failed to insert new image path: " . $image_stmt->error);
                    }
                }
                $image_stmt->close();
            }

            // Handle primary image update if 'set_primary_image' is submitted
            $primary_image_id_to_set = $_POST['set_primary_image'] ?? null;
            if ($primary_image_id_to_set) {
                // First, set all images for this product to not primary
                $stmt_reset_primary = $conn->prepare("UPDATE product_images SET is_primary = 0 WHERE product_id = ?");
                if (!$stmt_reset_primary) throw new Exception("Prepare failed (reset primary): " . $conn->error);
                $stmt_reset_primary->bind_param("s", $product_id);
                $stmt_reset_primary->execute();
                $stmt_reset_primary->close();

                // Then, set the selected image as primary
                $stmt_set_primary = $conn->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?");
                if (!$stmt_set_primary) throw new Exception("Prepare failed (set primary): " . $conn->error);
                $stmt_set_primary->bind_param("ss", $primary_image_id_to_set, $product_id);
                $stmt_set_primary->execute();
                $stmt_set_primary->close();
            } else {
                // If no specific primary is set via radio, and there are images, ensure one is primary.
                // This is crucial if all images were deleted and new ones added, or if the primary was deleted.
                $stmt_check_primary = $conn->prepare("SELECT COUNT(*) as primary_count FROM product_images WHERE product_id = ? AND is_primary = 1");
                if (!$stmt_check_primary) throw new Exception("Prepare failed (check primary): " . $conn->error);
                $stmt_check_primary->bind_param("s", $product_id);
                $stmt_check_primary->execute();
                $primary_count_result = $stmt_check_primary->get_result()->fetch_assoc()['primary_count'];
                $stmt_check_primary->close();

                if ($primary_count_result == 0) {
                    // No primary image. Set the first available image as primary.
                    $stmt_get_first_img = $conn->prepare("SELECT id FROM product_images WHERE product_id = ? ORDER BY created_at ASC LIMIT 1");
                    if (!$stmt_get_first_img) throw new Exception("Prepare failed (get first image): " . $conn->error);
                    $stmt_get_first_img->bind_param("s", $product_id);
                    $stmt_get_first_img->execute();
                    $first_img_result = $stmt_get_first_img->get_result();
                    if ($first_img_row = $first_img_result->fetch_assoc()) {
                        $first_image_id = $first_img_row['id'];
                        $stmt_force_primary = $conn->prepare("UPDATE product_images SET is_primary = 1 WHERE id = ?");
                        if (!$stmt_force_primary) throw new Exception("Prepare failed (force primary): " . $conn->error);
                        $stmt_force_primary->bind_param("s", $first_image_id);
                        $stmt_force_primary->execute();
                        $stmt_force_primary->close();
                    }
                    $stmt_get_first_img->close();
                }
            }


            $conn->commit();
            $_SESSION['success_message'] = "Product updated successfully!";
            // Refresh product data after update for display
            header("Location: index.php?page=seller/edit_product&id=" . $product_id . "&message=Product updated successfully!");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Product update failed: " . $e->getMessage());
            $errors[] = "An error occurred while updating the product. Please try again. Details: " . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $error_message = implode("<br>", $errors);
    }
}


$page_title = "Edit Product";

// Values for form repopulation (use product data if not POSTing or if POST fails)
$form_product_name = htmlspecialchars($_POST['product_name'] ?? ($product['name'] ?? ''));
$form_product_description = htmlspecialchars($_POST['product_description'] ?? ($product['description'] ?? ''));
$form_price = htmlspecialchars($_POST['price'] ?? ($product['price'] ?? ''));
$form_stock_quantity = htmlspecialchars($_POST['stock_quantity'] ?? ($product['stock_quantity'] ?? ''));
$form_category_id = htmlspecialchars($_POST['category_id'] ?? ($current_category_id ?? ''));

?>

<div class="container mt-4">
    <h2>Edit Product: <?php echo htmlspecialchars($product['name'] ?? 'Product'); ?></h2>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['message'])): // For success messages from redirect 
    ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($_GET['message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($_SESSION['success_message']);
            unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($_SESSION['error_message']);
            unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>


    <?php if ($product): // Only show form if product was loaded 
    ?>
        <form action="index.php?page=seller/edit_product&id=<?php echo htmlspecialchars($product_id); ?>" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="product_name" class="form-label">Product Name</label>
                <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo $form_product_name; ?>" required>
            </div>

            <div class="mb-3">
                <label for="product_description" class="form-label">Description</label>
                <textarea class="form-control" id="product_description" name="product_description" rows="3" required><?php echo $form_product_description; ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="price" class="form-label">Price (₱)</label>
                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo $form_price; ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="stock_quantity" class="form-label">Stock Quantity</label>
                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo $form_stock_quantity; ?>" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php if (!empty($categories)): ?>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php if ($form_category_id == $category['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="" disabled>No categories found</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Product Images</label>
                <div id="currentImagePreviewContainer" class="mt-2 d-flex flex-wrap border p-2">
                    <?php if (!empty($current_images)): ?>
                        <?php foreach ($current_images as $image): ?>
                            <div class="col-auto p-2 position-relative current-image-item">
                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" alt="Product Image" style="width: 100px; height: 100px; object-fit: cover;" class="img-thumbnail">
                                <div class="mt-1">
                                    <input type="radio" name="set_primary_image" value="<?php echo htmlspecialchars($image['id']); ?>" id="primary_<?php echo htmlspecialchars($image['id']); ?>" <?php if ($image['is_primary']) echo 'checked'; ?>>
                                    <label for="primary_<?php echo htmlspecialchars($image['id']); ?>" class="form-check-label small">Set Primary</label>
                                </div>
                                <div class="mt-1">
                                    <input type="checkbox" name="delete_images[]" value="<?php echo htmlspecialchars($image['id']); ?>" id="delete_<?php echo htmlspecialchars($image['id']); ?>">
                                    <label for="delete_<?php echo htmlspecialchars($image['id']); ?>" class="form-check-label small text-danger">Delete</label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No images currently uploaded for this product.</p>
                    <?php endif; ?>
                </div>
                <small class="form-text text-muted">Manage current images. Select one image to be the primary display image. Check 'Delete' to remove an image upon saving.</small>
            </div>


            <div class="mb-3">
                <label for="product_images_new" class="form-label">Add New Product Images</label>
                <input type="file" class="form-control" id="product_images_new" name="product_images_new[]" multiple accept="image/*">
                <small class="form-text text-muted">You can upload multiple new images. If no primary image is set from current images, the first new image uploaded might become primary if all old ones are deleted.</small>
                <div id="newImagePreviewContainer" class="mt-2 d-flex flex-wrap">
                    <!-- New image previews will be shown here -->
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="index.php?page=seller/my_products" class="btn btn-secondary">Cancel</a>
        </form>
    <?php else: ?>
        <p>Product could not be loaded for editing. <?php if (!isset($conn)) echo "Database connection issue."; ?></p>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const newImageInput = document.getElementById('product_images_new');
        const newPreviewContainer = document.getElementById('newImagePreviewContainer');

        if (newImageInput) {
            newImageInput.addEventListener('change', function(event) {
                newPreviewContainer.innerHTML = ''; // Clear previous new previews
                if (this.files && this.files.length > 0) {
                    Array.from(this.files).forEach(file => {
                        if (!file.type.startsWith('image/')) {
                            const errorMsg = document.createElement('p');
                            errorMsg.textContent = `File ${file.name} is not an image and will not be uploaded.`;
                            errorMsg.style.color = 'red';
                            newPreviewContainer.appendChild(errorMsg);
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const colDiv = document.createElement('div');
                            colDiv.className = 'col-auto p-2';
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.style.maxWidth = '100px';
                            img.style.maxHeight = '100px';
                            img.style.objectFit = 'cover';
                            img.classList.add('img-thumbnail');
                            colDiv.appendChild(img);
                            newPreviewContainer.appendChild(colDiv);
                        }
                        reader.readAsDataURL(file);
                    });
                }
            });
        }
    });
</script>