<?php
// pages/password_reset_request.php
$error_message = $_GET['error'] ?? null;
$success_message = $_GET['success'] ?? null;

$old_email = $_SESSION['old_reset_request_input']['email'] ?? '';
unset($_SESSION['old_reset_request_input']);

$page_title = "Request Password Reset";
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3>Reset Password</h3>
            </div>
            <div class="card-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <p>Enter your email address and we will send you a link to reset your password.</p>

                <form action="api/auth/password_reset_request_handler.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($old_email); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send Password Reset Link</button>
                </form>
                <p class="mt-3 text-center">
                    Remembered your password? <a href="index.php?page=login">Login here</a>.
                </p>
            </div>
        </div>
    </div>
</div>