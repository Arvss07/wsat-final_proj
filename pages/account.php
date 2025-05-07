<?php
// File: pages/account.php
// Purpose: Displays user account information and forms for updates.

// Ensure user is logged in
// session_start(); // Already started in index.php or a global include
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login&message=Please login to access your account.');
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/hash.php'; // For password functions if needed here

$user_id = $_SESSION['user_id'];
$user = null;
$error_message = '';
$success_message = '';

// Fetch user data
try {
    $stmt = $conn->prepare("SELECT name, email, profile_picture_path FROM users WHERE id = ?");
    $stmt->bind_param("s", $user_id); // Bind the user_id parameter
    $stmt->execute();
    $result = $stmt->get_result(); // Get the result set from the prepared statement
    $user = $result->fetch_assoc(); // Fetch the data as an associative array
    $stmt->close(); // Close the statement
} catch (Exception $e) { // Catch generic Exception for MySQLi errors or other issues
    $error_message = "Error fetching account details: " . $e->getMessage();
}

if (!$user) {
    // Should not happen if session is valid, but good to check
    header('Location: index.php?page=login&message=User not found.');
    exit();
}

// Handle messages from handler
if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2>My Account</h2>
            <hr>

            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    Account Details
                </div>
                <div class="card-body">
                    <form action="api/user/update_account_handler.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_details">
                        <div class="mb-3 row">
                            <label for="name" class="col-sm-3 col-form-label">Name</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="email" class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="profile_picture" class="col-sm-3 col-form-label">Profile Picture</label>
                            <div class="col-sm-9">
                                <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                                <?php if ($user['profile_picture_path']): ?>
                                    <img src="<?php echo htmlspecialchars($user['profile_picture_path']); ?>" alt="Profile Picture" class="img-thumbnail mt-2" style="max-height: 150px;">
                                <?php else: ?>
                                    <p class="form-text text-muted">No profile picture uploaded.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">Update Details</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    Change Password
                </div>
                <div class="card-body">
                    <form action="api/user/update_account_handler.php" method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="mb-3 row">
                            <label for="current_password" class="col-sm-3 col-form-label">Current Password</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="new_password" class="col-sm-3 col-form-label">New Password</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="confirm_new_password" class="col-sm-3 col-form-label">Confirm New Password</label>
                            <div class="col-sm-9">
                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>