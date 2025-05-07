<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php'; // Establishes $conn and loads .env
require_once __DIR__ . '/../../utils/hash.php';      // For verifyPassword, hashPassword

$base_url = rtrim($_ENV['APP_URL'] ?? 'http://localhost/wsat-final_proj', '/');
$reset_form_url = $base_url . "/index.php?page=password_reset_form";
$login_page_url = $base_url . "/index.php?page=login";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: " . $reset_form_url . "&error=Invalid+request+method.");
    exit;
}

$email = trim($_POST['email'] ?? '');
$otp = trim($_POST['otp'] ?? '');
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$_SESSION['old_reset_form_input'] = [
    'email' => $email,
    'otp' => $otp
];

// --- Validations ---
if (empty($email) || empty($otp) || empty($new_password) || empty($confirm_password)) {
    header("Location: " . $reset_form_url . "&error=All+fields+are+required.");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: " . $reset_form_url . "&error=Invalid+email+format.");
    exit;
}

if (!preg_match('/^\d{6}$/', $otp)) {
    header("Location: " . $reset_form_url . "&error=Invalid+OTP+format.+It+must+be+6+digits.");
    exit;
}

if ($new_password !== $confirm_password) {
    header("Location: " . $reset_form_url . "&error=New+passwords+do+not+match.");
    exit;
}

// Password complexity: min 8 chars, letters, numbers, one special character
if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d\s])(.){8,}$/', $new_password)) {
    header("Location: " . $reset_form_url . "&error=Password+must+be+at+least+8+characters+long,+include+letters,+numbers,+and+at+least+one+special+character.");
    exit;
}

// --- Verify OTP ---
$stmt_otp = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE email = ? AND token = ?");
$stmt_otp->bind_param("ss", $email, $otp);
$stmt_otp->execute();
$otp_result = $stmt_otp->get_result();

if ($otp_result->num_rows !== 1) {
    header("Location: " . $reset_form_url . "&error=Invalid+or+expired+OTP.+Please+request+a+new+one.");
    exit;
}

$otp_data = $otp_result->fetch_assoc();
$user_id = $otp_data['user_id'];
$expires_at = strtotime($otp_data['expires_at']);

if (time() > $expires_at) {
    // Optionally delete expired OTP here
    $stmt_delete_expired = $conn->prepare("DELETE FROM password_resets WHERE email = ? AND token = ?");
    $stmt_delete_expired->bind_param("ss", $email, $otp);
    $stmt_delete_expired->execute();
    $stmt_delete_expired->close();

    header("Location: " . $reset_form_url . "&error=OTP+has+expired.+Please+request+a+new+one.");
    exit;
}
$stmt_otp->close();

// --- Update Password ---
$hashed_password = hashPassword($new_password);

$stmt_update_pass = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$stmt_update_pass->bind_param("ss", $hashed_password, $user_id);

if (!$stmt_update_pass->execute()) {
    error_log("Error updating password for user_id {$user_id}: " . $stmt_update_pass->error);
    header("Location: " . $reset_form_url . "&error=Could+not+update+password.+Please+try+again.");
    exit;
}
$stmt_update_pass->close();

// --- Invalidate OTP (Delete it) ---
$stmt_delete_otp = $conn->prepare("DELETE FROM password_resets WHERE email = ? AND token = ?");
$stmt_delete_otp->bind_param("ss", $email, $otp);

if (!$stmt_delete_otp->execute()) {
    error_log("Error deleting used OTP for email {$email}: " . $stmt_delete_otp->error);
    // Non-critical error for the user, password was reset, but log it.
}
$stmt_delete_otp->close();

// --- Success ---
unset($_SESSION['old_reset_form_input']);
header("Location: " . $login_page_url . "&success=Your+password+has+been+reset+successfully.+Please+login.");
exit;

$conn->close();
