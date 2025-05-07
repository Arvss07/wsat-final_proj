<?php
// Ensure user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Seller') {
    header("Location: index.php?page=login&message=You must be logged in as a seller to add products.");
    exit();
}

require_once __DIR__ . '/../utils/uuid.php'; // Include UUID generator

// Ensure $conn is available (it should be, from index.php -> config/database.php)
if (!isset($conn) || $conn->connect_error) {
    $error_message = "Database connection is not available or failed. Please check the configuration.";
    if (!isset($conn)) {
        require_once __DIR__ . '/../config/database.php';
        if (!isset($conn) || $conn->connect_error) {
            // Still not available, display error and exit or handle appropriately
        }
    }
}

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
        $error_message = "Could not load categories. Please try again later.";
    }
}

// Placeholder for handling form submission (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $product_description = trim($_POST['product_description'] ?? '');
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $stock_quantity = filter_input(INPUT_POST, 'stock_quantity', FILTER_VALIDATE_INT);
    $seller_id = $_SESSION['user_id'];

    $errors = [];
    $posted_category_id_value = trim($_POST['category_id'] ?? '');
    $category_id = null; // Initialize category_id

    // --- Validation ---
    if (empty($product_name)) {
        $errors[] = "Product name is required.";
    }
    if (empty($product_description)) {
        $errors[] = "Product description is required.";
    }
    if ($price === false || $price <= 0) {
        $errors[] = "Valid price is required.";
    }
    if ($stock_quantity === false || $stock_quantity < 0) {
        $errors[] = "Valid stock quantity is required.";
    }

    // Updated category ID validation (string check)
    if (empty($posted_category_id_value)) {
        $errors[] = "Category is required. Please select a category.";
    } else {
        if (is_string($posted_category_id_value) && strlen($posted_category_id_value) > 0) {
            $category_id = $posted_category_id_value;
        } else {
            $errors[] = "Invalid category format selected.";
            error_log("Add Product: Invalid category format received. Raw value: '" . $posted_category_id_value . "'");
        }
    }

    // --- File Upload Handling ---
    $uploaded_images_paths = [];
    $image_files = $_FILES['product_images'] ?? null;
    $num_files = $image_files ? count($image_files['name']) : 0;
    $upload_dir = __DIR__ . '/../uploads/products/';
    $avif_support_needed = false;

    for ($i = 0; $i < $num_files; $i++) {
        if ($image_files['error'][$i] === UPLOAD_ERR_OK) {
            $tmp_name_check = $image_files['tmp_name'][$i];
            $file_mime_type_check = mime_content_type($tmp_name_check);
            if ($file_mime_type_check !== 'image/avif') {
                $avif_support_needed = true;
                break;
            }
        }
    }

    if ($avif_support_needed && !function_exists('imageavif')) {
        $errors[] = "Server does not support AVIF image conversion for non-AVIF files. Please upload AVIF directly or contact support.";
    }

    if (empty($errors)) { // Proceed only if AVIF support is present (if needed) or no errors yet
        for ($i = 0; $i < $num_files; $i++) {
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
                    // File is already AVIF, just move it
                    if (move_uploaded_file($tmp_name, $avif_destination)) {
                        $uploaded_images_paths[] = 'uploads/products/' . $avif_filename;
                    } else {
                        $errors[] = "Failed to move uploaded AVIF file: " . htmlspecialchars($original_name);
                    }
                } else {
                    // File is not AVIF, needs conversion
                    if (!function_exists('imageavif')) {
                        // This check is a fallback, should have been caught earlier if $avif_support_needed was true
                        $errors[] = "AVIF conversion support is not available on the server.";
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
                        $errors[] = "Failed to read image file for conversion: " . htmlspecialchars($original_name) . ". The file may be corrupted or an unsupported format.";
                        continue;
                    }

                    if (imageavif($source_image, $avif_destination)) {
                        $uploaded_images_paths[] = 'uploads/products/' . $avif_filename;
                    } else {
                        $errors[] = "Failed to convert image " . htmlspecialchars($original_name) . " to AVIF format.";
                    }
                    imagedestroy($source_image); // Free memory
                }
            } elseif ($image_files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                $errors[] = "Error with file " . htmlspecialchars($image_files['name'][$i]) . ": Error code " . $image_files['error'][$i];
            }
        }
    } else {
        $errors[] = "At least one product image is required.";
    }

    // --- Database Insertion ---
    if (empty($errors)) {
        try {
            $conn->begin_transaction();

            $product_id_uuid = generateUuidV4(); // Generate UUID for product

            $sql_product = "INSERT INTO products (id, name, description, price, stock_quantity, seller_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_product = $conn->prepare($sql_product);
            if ($stmt_product === false) {
                throw new Exception("Prepare statement for product failed: " . $conn->error);
            }
            $stmt_product->bind_param("sssdis", $product_id_uuid, $product_name, $product_description, $price, $stock_quantity, $seller_id);

            if ($stmt_product->execute()) {
                $stmt_product->close();

                // Insert into product_categories table
                if ($category_id !== null) { // Ensure category_id was successfully validated and is not null
                    $sql_prod_cat = "INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)";
                    $stmt_prod_cat = $conn->prepare($sql_prod_cat);
                    if ($stmt_prod_cat === false) {
                        throw new Exception("Prepare statement for product_categories failed: " . $conn->error);
                    }
                    $stmt_prod_cat->bind_param("ss", $product_id_uuid, $category_id);
                    if (!$stmt_prod_cat->execute()) {
                        throw new Exception("Failed to insert into product_categories: " . $stmt_prod_cat->error);
                    }
                    $stmt_prod_cat->close();
                } else if (empty($posted_category_id_value)) {
                    throw new Exception("Category ID was not provided for product_categories insertion.");
                }

                // Insert images into product_images table
                if (!empty($uploaded_images_paths)) {
                    $image_sql = "INSERT INTO product_images (id, product_id, image_path, is_primary) VALUES (?, ?, ?, ?)";
                    $image_stmt = $conn->prepare($image_sql);
                    if ($image_stmt === false) {
                        throw new Exception("Prepare image statement failed: " . $conn->error);
                    }
                    $is_primary = true; // First image is primary
                    foreach ($uploaded_images_paths as $image_path) {
                        $image_id_uuid = generateUuidV4(); // Generate UUID for each image
                        $image_stmt->bind_param("sssi", $image_id_uuid, $product_id_uuid, $image_path, $is_primary);
                        if (!$image_stmt->execute()) {
                            throw new Exception("Failed to insert image path: " . $image_stmt->error);
                        }
                        $is_primary = false; // Subsequent images are not primary
                    }
                    $image_stmt->close();
                }

                $conn->commit();
                $_SESSION['success_message'] = "Product added successfully!";
                header("Location: index.php?page=seller/my_products");
                exit();
            } else {
                throw new Exception("Failed to add product: " . $stmt_product->error);
            }
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Product addition failed: " . $e->getMessage());
            $errors[] = "An error occurred while adding the product. Please try again. Details: " . $e->getMessage();
        }
    }

    // If there were errors, they will be displayed above the form
    if (!empty($errors)) {
        $error_message = implode("<br>", $errors);
    }
}

$page_title = "Add New Product";

// Get previously submitted values for repopulation, if any
$prev_product_name = htmlspecialchars($_POST['product_name'] ?? '');
$prev_product_description = htmlspecialchars($_POST['product_description'] ?? '');
$prev_price = htmlspecialchars($_POST['price'] ?? '');
$prev_stock_quantity = htmlspecialchars($_POST['stock_quantity'] ?? '');
$prev_category_id = htmlspecialchars($_POST['category_id'] ?? '');

?>

<div class="container mt-4">
    <h2>Add New Product</h2>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error_message; // Already HTML, no need to escape again if errors are pre-formatted 
            ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($_SESSION['success_message']);
            unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <form action="index.php?page=seller/add_product" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="product_name" class="form-label">Product Name</label>
            <input type="text" class="form-control" id="product_name" name="product_name" value="<?php echo $prev_product_name; ?>" required>
        </div>

        <div class="mb-3">
            <label for="product_description" class="form-label">Description</label>
            <textarea class="form-control" id="product_description" name="product_description" rows="3" required><?php echo $prev_product_description; ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="price" class="form-label">Price (₱)</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" value="<?php echo $prev_price; ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo $prev_stock_quantity; ?>" required>
            </div>
            <div class="col-md-4 mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">Select Category</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo htmlspecialchars($category['id']); ?>" <?php if ($prev_category_id == $category['id']) echo 'selected'; ?>>
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
            <label for="product_images" class="form-label">Product Images</label>
            <input type="file" class="form-control" id="product_images" name="product_images[]" multiple accept="image/*">
            <small class="form-text text-muted">You can upload multiple images. The first image will be the primary.</small>
            <div id="imagePreviewContainer" class="mt-2 d-flex flex-wrap">
                <!-- Image previews will be shown here -->
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Add Product</button>
        <a href="index.php?page=seller/my_products" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('product_images');
        const previewContainer = document.getElementById('imagePreviewContainer');

        if (imageInput) {
            imageInput.addEventListener('change', function(event) {
                previewContainer.innerHTML = ''; // Clear previous previews
                if (this.files && this.files.length > 0) {
                    Array.from(this.files).forEach(file => {
                        if (!file.type.startsWith('image/')) {
                            // Optionally, display an error for non-image files
                            const errorMsg = document.createElement('p');
                            errorMsg.textContent = `File ${file.name} is not an image and will not be uploaded.`;
                            errorMsg.style.color = 'red';
                            previewContainer.appendChild(errorMsg);
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const colDiv = document.createElement('div');
                            colDiv.className = 'col-auto p-2'; // Bootstrap class for layout
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.style.maxWidth = '100px';
                            img.style.maxHeight = '100px';
                            img.style.objectFit = 'cover';
                            img.classList.add('img-thumbnail');
                            colDiv.appendChild(img);
                            previewContainer.appendChild(colDiv);
                        }
                        reader.readAsDataURL(file);
                    });
                }
            });
        }
    });
</script>