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

        // Check for recent password reset requests for this email
        $stmt_check_recent = $conn->prepare("SELECT created_at FROM password_resets WHERE email = ? ORDER BY created_at DESC LIMIT 1");
        $stmt_check_recent->bind_param("s", $email);
        $stmt_check_recent->execute();
        $recent_result = $stmt_check_recent->get_result();

        if ($recent_result->num_rows === 1) {
            $last_request_data = $recent_result->fetch_assoc();
            $last_request_timestamp_str = $last_request_data['created_at'];
            $last_request_time_unix = strtotime($last_request_timestamp_str);
            $current_time_unix = time();
            $rate_limit_duration_seconds = 120; // 2 minutes

            if ($last_request_time_unix === false) {
                error_log("Rate Limiter: Failed to parse 'created_at' timestamp ('" . $last_request_timestamp_str . "') for email: " . $email . ". Allowing request for now, but this should be investigated.");
                // Allowing the request to proceed if timestamp is unparseable, to avoid undue user blocking.
                // Depending on security policy, could also block with a generic error.
            } else {
                $seconds_to_wait = 0;
                if ($last_request_time_unix > $current_time_unix) {
                    // Timestamp is in the future. This is an anomaly.
                    // User should wait the standard rate_limit_duration_seconds from the current time.
                    $seconds_to_wait = $rate_limit_duration_seconds;
                    error_log("Rate Limiter: Future 'created_at' timestamp ('" . $last_request_timestamp_str . "') detected for email: " . $email . ". Current time: " . date('Y-m-d H:i:s', $current_time_unix) . ". Applying standard " . $rate_limit_duration_seconds . "s wait.");
                } else {
                    $seconds_since_last = $current_time_unix - $last_request_time_unix;
                    if ($seconds_since_last < $rate_limit_duration_seconds) {
                        $seconds_to_wait = $rate_limit_duration_seconds - $seconds_since_last;
                    }
                }

                if ($seconds_to_wait > 0) {
                    // Ensure at least 1 second for ceil to produce a minimum of 1 minute in the message,
                    // and to prevent messages like "wait 0 minutes".
                    $display_wait_seconds = max(1, $seconds_to_wait); // Ensure at least 1 second
                    $minutes_to_wait_display = ceil($display_wait_seconds / 60);
                    header("Location: " . $redirect_page_url . "&error=Too+many+requests.+Please+wait+" . $minutes_to_wait_display . "+minute(s)+before+requesting+another+OTP.");
                    exit;
                }
            }
        }
        $stmt_check_recent->close();

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
