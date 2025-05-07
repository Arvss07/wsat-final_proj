<?php
// pages/password_reset_form.php
$error_message = $_GET['error'] ?? null;
$success_message = $_GET['success'] ?? null; // Though success usually redirects to login

// Repopulate email if redirected back with an error
$old_email = $_SESSION['old_reset_form_input']['email'] ?? '';
$old_otp = $_SESSION['old_reset_form_input']['otp'] ?? '';
unset($_SESSION['old_reset_form_input']);

$page_title = "Reset Your Password";
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card">
            <div class="card-header">
                <h3>Set New Password</h3>
            </div>
            <div class="card-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <p>Please enter your email, the OTP sent to you, and your new password.</p>

                <form action="api/auth/password_reset_handler.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($old_email); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="otp" class="form-label">OTP (One-Time Password)</label>
                        <input type="text" class="form-control" id="otp" name="otp" value="<?php echo htmlspecialchars($old_otp); ?>" required minlength="6" maxlength="6">
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div id="passwordHelpBlock" class="form-text">
                            Your password must be at least 8 characters long, contain letters and numbers, and at least one special character.
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                </form>
                <p class="mt-3 text-center">
                    Remembered your password? <a href="index.php?page=login">Login here</a>.
                </p>
                <p class="mt-2 text-center">
                    Didn't receive an OTP? <a href="index.php?page=password_reset_request">Request a new one</a>.
                </p>
            </div>
        </div>
    </div>
</div>