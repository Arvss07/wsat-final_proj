<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php'; // Ensure PHPMailer is loaded

if (!function_exists('sendPasswordResetEmail')) {
    /**
     * Sends a password reset OTP email.
     *
     * @param string $toEmail The recipient's email address.
     * @param string $userName The recipient's name.
     * @param string $otp The One-Time Password.
     * @return bool True if email was sent successfully, false otherwise.
     */
    function sendPasswordResetEmail(string $toEmail, string $userName, string $otp): bool
    {
        $mail = new PHPMailer(true);
        $resetFormLink = rtrim($_ENV['APP_URL'] ?? 'http://localhost/wsat-final_proj', '/') . '/index.php?page=password_reset_form';

        try {
            // Server settings from .env
            $mail->SMTPDebug = $_ENV['DEBUG'] === 'true' ? SMTP::DEBUG_SERVER : SMTP::DEBUG_OFF; // Enable verbose debug output in debug mode
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.example.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'] ?? 'user@example.com';
            $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? 'secret';
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);

            // Recipients
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com', $_ENV['MAIL_FROM_NAME'] ?? 'Shoe Store');
            $mail->addAddress($toEmail, $userName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Password Reset OTP - ' . ($_ENV['APP_NAME'] ?? 'Shoe Store');
            $mail->Body    = "
                <p>Hello {$userName},</p>
                <p>You recently requested to reset your password for your " . htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store') . " account.</p>
                <p>Your One-Time Password (OTP) is: <strong>{$otp}</strong></p>
                <p>Please go to the following page and enter your email and this OTP to reset your password. This OTP is valid for 10 minutes (or as configured):</p>
                <p><a href='{$resetFormLink}'>{$resetFormLink}</a></p>
                <p>If you did not request a password reset, please ignore this email or contact support if you have concerns.</p>
                <p>Thanks,<br>The " . htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store') . " Team</p>
            ";
            $mail->AltBody = "
                Hello {$userName},

                You recently requested to reset your password for your " . htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store') . " account.

                Your One-Time Password (OTP) is: {$otp}

                Please go to the following page ({$resetFormLink}) and enter your email and this OTP to reset your password. This OTP is valid for 10 minutes (or as configured).

                If you did not request a password reset, please ignore this email or contact support if you have concerns.

                Thanks,
                The " . htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store') . " Team
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log the error in a real application
            error_log("Mailer Error (Password Reset OTP): {$mail->ErrorInfo}");
            return false;
        }
    }
}
