<?php
$custom_message = "Unauthorized Access. You do not have permission to view this page.";
if (isset($_GET['message']) && !empty(trim($_GET['message']))) {
    $custom_message = htmlspecialchars(trim($_GET['message']));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }
    </style>
</head>

<body>
    <div class="container text-center">
        <h1 class="display-1">403</h1>
        <p class="lead"><?php echo $custom_message; ?></p>
        <a href="index.php?page=home" class="btn btn-primary">Go Home</a>
    </div>
</body>

</html>