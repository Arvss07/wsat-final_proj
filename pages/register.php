<?php
// This page might have messages passed via GET from the registration handler
$error_message = $_GET['error'] ?? null;
$success_message = $_GET['success'] ?? null;

// Preserve old input if validation fails and redirects back
$old_name = $_SESSION['old_register_input']['name'] ?? '';
$old_email = $_SESSION['old_register_input']['email'] ?? '';
$old_role = $_SESSION['old_register_input']['role'] ?? '';
unset($_SESSION['old_register_input']); // Clear after use

?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-header">
                <h3>Register New Account</h3>
            </div>
            <div class="card-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <form action="api/auth/register_handler.php" method="POST">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($old_name); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($old_email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div id="passwordHelp" class="form-text">Password must be at least 8 characters long.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Register as</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="" disabled <?php echo !$old_role ? 'selected' : ''; ?>>Select a role</option>
                            <option value="Shopper" <?php echo $old_role === 'Shopper' ? 'selected' : ''; ?>>Shopper</option>
                            <option value="Seller" <?php echo $old_role === 'Seller' ? 'selected' : ''; ?>>Seller</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
                <p class="mt-3 text-center">
                    Already have an account? <a href="index.php?page=login">Login here</a>.
                </p>
            </div>
        </div>
    </div>
</div>