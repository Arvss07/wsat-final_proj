<?php
// This is pages/unauthorized.php
// It should only output the content for the unauthorized page area.
// The main HTML structure, Bootstrap CSS, header, and footer are handled by index.php and its includes.

$custom_message = "Unauthorized Access. You do not have permission to view this page.";
if (isset($_GET['message']) && !empty(trim($_GET['message']))) {
    $custom_message = htmlspecialchars(trim($_GET['message']));
}
?>
<div class="p-5 mb-4 bg-light rounded-3 text-center">
    <h1 class="display-1">403</h1>
    <p class="lead"><?php echo $custom_message; ?></p>
    <a href="index.php?page=home" class="btn btn-primary mt-3">Go Home</a>
</div>