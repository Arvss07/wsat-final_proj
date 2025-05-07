<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php'; // Establishes $conn and loads .env
require_once __DIR__ . '/../../utils/uuid.php';      // For generateUuidV4()
require_once __DIR__ . '/../../utils/mailer.php';    // For sendPasswordResetEmail()

$base_url = rtrim($_ENV['APP_URL'] ?? 'http://localhost/wsat-final_proj', '/');
$redirect_page_url = $base_url . "/index.php?page=password_reset_request";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $_SESSION['old_reset_request_input'] = ['email' => $email]; // For repopulating form

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: " . $redirect_page_url . "&error=Invalid+email+address+provided.");
        exit;
    }

    // Check if user exists
    $stmt_user = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND is_blocked = FALSE");
    $stmt_user->bind_param("s", $email);
    $stmt_user->execute();
    $user_result = $stmt_user->get_result();

    if ($user_result->num_rows === 1) {
        $user = $user_result->fetch_assoc();
        $user_id = $user['id'];
        $user_name = $user['name'];

        // Generate a 6-digit OTP
        try {
            $otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log("Error generating OTP: " . $e->getMessage());
            header("Location: " . $redirect_page_url . "&error=Could+not+process+request.+Please+try+again+later.");
            exit;
        }

        $reset_id = generateUuidV4(); // UUID for the password_resets table primary key
        // OTPs typically have a shorter expiry, e.g., 10 minutes
        $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        // Store the OTP (as token) in the database
        $stmt_token = $conn->prepare("INSERT INTO password_resets (id, user_id, email, token, expires_at) VALUES (?, ?, ?, ?, ?)");
        // The `token` column now stores the OTP
        $stmt_token->bind_param("sssss", $reset_id, $user_id, $email, $otp, $expires_at);

        if ($stmt_token->execute()) {
            // Send the email with OTP
            if (sendPasswordResetEmail($email, $user_name, $otp)) {
                unset($_SESSION['old_reset_request_input']);
                header("Location: " . $redirect_page_url . "&success=If+an+account+with+that+email+exists,+an+OTP+has+been+sent+to+reset+your+password.");
                exit;
            } else {
                error_log("Password reset OTP email sending failed for: " . $email);
                header("Location: " . $redirect_page_url . "&error=Could+not+send+OTP+email.+Please+try+again+later+or+contact+support.");
                exit;
            }
        } else {
            error_log("Error storing password reset OTP: " . $stmt_token->error);
            header("Location: " . $redirect_page_url . "&error=Could+not+process+request.+Database+error.");
            exit;
        }
        $stmt_token->close();
    } else {
        error_log("Password reset OTP attempt for non-existent or blocked email: " . $email);
        unset($_SESSION['old_reset_request_input']);
        header("Location: " . $redirect_page_url . "&success=If+an+account+with+that+email+exists,+an+OTP+has+been+sent+to+reset+your+password.");
        exit;
    }
    $stmt_user->close();
} else {
    header("Location: " . $redirect_page_url . "&error=Invalid+request+method.");
    exit;
}

$conn->close();
