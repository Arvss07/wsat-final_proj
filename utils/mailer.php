<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/vendor/autoload.php'; // Ensure PHPMailer is loaded

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
            $mail->SMTPDebug = SMTP::DEBUG_OFF; // Disable verbose debug output
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

if (!function_exists('sendWelcomeEmail')) {
    /**
     * Sends a welcome email to a new user.
     *
     * @param string $toEmail The recipient's email address.
     * @param string $username The recipient's username.
     * @return bool True if email was sent successfully, false otherwise.
     */
    function sendWelcomeEmail(string $toEmail, string $username): bool
    {
        $mail = new PHPMailer(true);
        $emailTemplatePath = dirname(__DIR__) . '/templates/emails/welcome_email.php';

        if (!file_exists($emailTemplatePath)) {
            error_log("Welcome email template not found at {$emailTemplatePath}");
            return false;
        }

        // Start output buffering to capture the email content
        ob_start();
        // Make $username available to the template
        include $emailTemplatePath;
        $emailBody = ob_get_clean();

        try {
            // Server settings from .env
            $mail->SMTPDebug = SMTP::DEBUG_OFF; // Disable verbose debug output for production
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.example.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'] ?? 'user@example.com';
            $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? 'secret';
            $mail->SMTPSecure = ($_ENV['MAIL_ENCRYPTION'] ?? 'tls') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);

            // Recipients
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com', $_ENV['MAIL_FROM_NAME'] ?? 'Our Platform');
            $mail->addAddress($toEmail, $username);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Welcome to ' . ($_ENV['APP_NAME'] ?? 'Our Platform') . '!';
            $mail->Body    = $emailBody;
            // Consider creating a plain text version for $mail->AltBody if needed
            $mail->AltBody = 'Welcome to ' . ($_ENV['APP_NAME'] ?? 'Our Platform') . '! Thank you for registering.';


            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error (Welcome Email to {$toEmail}): {$mail->ErrorInfo}");
            return false;
        }
    }
}

if (!function_exists('sendRegistrationConfirmationEmail')) {
    /**
     * Sends a registration confirmation email.
     *
     * @param string $email The recipient's email address.
     * @param string $name The recipient's name.
     * @return bool True if email was sent successfully, false otherwise.
     */
    function sendRegistrationConfirmationEmail(string $email, string $name): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF; // Disable verbose debug output
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.example.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'] ?? 'user@example.com';
            $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? 'secret';
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);

            // Recipients
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com', $_ENV['MAIL_FROM_NAME'] ?? 'Shoe Store');
            $mail->addAddress($email, $name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Welcome to ' . ($_ENV['APP_NAME'] ?? 'Shoe Store');
            $mail->Body    = "
                <p>Hello {$name},</p>
                <p>Thank you for registering with " . htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store') . ".</p>
                <p>We are excited to have you on board!</p>
                <p>Thanks,<br>The " . htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store') . " Team</p>
            ";
            $mail->AltBody = "
                Hello {$name},

                Thank you for registering with " . htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store') . ".

                We are excited to have you on board!

                Thanks,
                The " . htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store') . " Team
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log the error in a real application
            error_log("Mailer Error (Registration Confirmation): {$mail->ErrorInfo}");
            return false;
        }
    }
}

if (!function_exists('sendOrderConfirmationEmail')) {
    /**
     * Sends an order confirmation email.
     *
     * @param string $email The recipient's email address.
     * @param string $name The recipient's name.
     * @param string $orderId The order ID.
     * @param array $orderDetails The order details.
     * @return bool True if email was sent successfully, false otherwise.
     */
    function sendOrderConfirmationEmail(string $email, string $name, string $orderId, array $orderDetails): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_OFF; // Disable verbose debug output
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.example.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'] ?? 'user@example.com';
            $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? 'secret';
            $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);

            // Recipients
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com', $_ENV['MAIL_FROM_NAME'] ?? 'Shoe Store');
            $mail->addAddress($email, $name);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Order Confirmation - ' . ($_ENV['APP_NAME'] ?? 'Shoe Store');
            $mail->Body    = "
                <p>Hello {$name},</p>
                <p>Thank you for your order with " . htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store') . ".</p>
                <p>Your order ID is: <strong>{$orderId}</strong></p>
                <p>Order Details:</p>
                <ul>
                    " . implode('', array_map(fn($item) => "<li>{$item}</li>", $orderDetails)) . "
                </ul>
                <p>Thanks,<br>The " . htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store') . " Team</p>
            ";
            $mail->AltBody = "
                Hello {$name},

                Thank you for your order with " . htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store') . ".

                Your order ID is: {$orderId}

                Order Details:
                " . implode("\n", $orderDetails) . "

                Thanks,
                The " . htmlspecialchars($_ENV['APP_NAME'] ?? 'Shoe Store') . " Team
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log the error in a real application
            error_log("Mailer Error (Order Confirmation): {$mail->ErrorInfo}");
            return false;
        }
    }
}

if (!function_exists('sendGenericEmail')) {
    /**
     * Sends a generic email.
     *
     * @param string $toEmail The recipient's email address.
     * @param string $subject The email subject.
     * @param string $htmlBody The HTML email body.
     * @param string $altBody The plain text alternative body (optional).
     * @param string $recipientName The recipient's name (optional, for personalization).
     * @return bool True if email was sent successfully, false otherwise.
     */
    function sendGenericEmail(string $toEmail, string $subject, string $htmlBody, string $altBody = '', string $recipientName = 'User'): bool
    {
        $mail = new PHPMailer(true);

        try {
            // Server settings from .env
            $mail->SMTPDebug = SMTP::DEBUG_OFF; // Disable verbose debug output
            $mail->isSMTP();
            $mail->Host       = $_ENV['MAIL_HOST'] ?? 'smtp.example.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USERNAME'] ?? 'user@example.com';
            $mail->Password   = $_ENV['MAIL_PASSWORD'] ?? 'secret';
            $mail->SMTPSecure = ($_ENV['MAIL_ENCRYPTION'] ?? 'tls') === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = (int)($_ENV['MAIL_PORT'] ?? 587);

            // Recipients
            $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@example.com', $_ENV['MAIL_FROM_NAME'] ?? 'Shoe Store');
            $mail->addAddress($toEmail, $recipientName);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;
            $mail->AltBody = !empty($altBody) ? $altBody : strip_tags($htmlBody); // Basic alt body if not provided

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error (Generic Email to {$toEmail} with subject '{$subject}'): {$mail->ErrorInfo}");
            return false;
        }
    }
}
