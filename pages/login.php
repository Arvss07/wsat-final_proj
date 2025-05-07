<?php
// Placeholder for login logic
// For now, just a simple message

// If login form is submitted, you would handle it here
// Example: include __DIR__ . '/../api/auth/login_handler.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Login</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_GET['message'])): ?>
                            <div class="alert alert-info"><?php echo htmlspecialchars($_GET['message']); ?></div>
                        <?php endif; ?>
                        <p>Login form will go here.</p>
                        <p>For now, this is a placeholder. Please implement the login form and logic.</p>
                        <a href="index.php?page=register">Don't have an account? Register here.</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>