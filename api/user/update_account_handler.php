<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/hash.php'; // For verifyPassword and hashPassword
require_once __DIR__ . '/../../utils/uuid.php'; // For generating UUIDs if needed for filenames, though less common for this

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php?page=login&message=Please login to perform this action.');
    exit();
}

$user_id = $_SESSION['user_id'];
$redirect_url = '../../index.php?page=account'; // Base redirect URL

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect_url . '&error=Invalid request method.');
    exit();
}

if (!isset($_POST['action'])) {
    header('Location: ' . $redirect_url . '&error=No action specified.');
    exit();
}

$action = $_POST['action'];

try {
    if ($action === 'update_details') {
        // --- Update Account Details --- 
        if (!isset($_POST['name'], $_POST['email'])) {
            header('Location: ' . $redirect_url . '&error=Name and email are required.');
            exit();
        }

        $name = trim($_POST['name']);
        $email = trim($_POST['email']);

        if (empty($name) || empty($email)) {
            header('Location: ' . $redirect_url . '&error=Name and email cannot be empty.');
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ' . $redirect_url . '&error=Invalid email format.');
            exit();
        }

        // Check if email is already taken by another user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("ss", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_assoc()) {
            $stmt->close();
            header('Location: ' . $redirect_url . '&error=Email already in use by another account.');
            exit();
        }
        $stmt->close();

        $profile_picture_path_to_update = null;

        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../uploads/profile_pictures/';
            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0777, true)) {
                header('Location: ' . $redirect_url . '&error=Failed to create upload directory.');
                exit();
            }

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
            $file_type = mime_content_type($_FILES['profile_picture']['tmp_name']);
            if (!in_array($file_type, $allowed_types)) {
                header('Location: ' . $redirect_url . '&error=Invalid file type. Only JPG, PNG, GIF, AVIF, WEBP allowed.');
                exit();
            }

            if ($_FILES['profile_picture']['size'] > 5 * 1024 * 1024) { // 5MB limit
                header('Location: ' . $redirect_url . '&error=File size exceeds 5MB limit.');
                exit();
            }

            // Fetch current profile picture path to delete old one if it exists
            $stmt_old_pic = $conn->prepare("SELECT profile_picture_path FROM users WHERE id = ?");
            $stmt_old_pic->bind_param("s", $user_id);
            $stmt_old_pic->execute();
            $result_old_pic = $stmt_old_pic->get_result();
            $old_pic_data = $result_old_pic->fetch_assoc();
            $stmt_old_pic->close();
            $old_profile_picture_path = $old_pic_data ? $old_pic_data['profile_picture_path'] : null;

            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
            $destination = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $destination)) {
                $profile_picture_path_to_update = 'uploads/profile_pictures/' . $new_filename;

                // Delete old profile picture if it exists and is not a default/placeholder
                if ($old_profile_picture_path && file_exists(__DIR__ . '/../../' . $old_profile_picture_path)) {
                    if (strpos($old_profile_picture_path, 'default') === false) { // Avoid deleting a potential default image
                        unlink(__DIR__ . '/../../' . $old_profile_picture_path);
                    }
                }
            } else {
                header('Location: ' . $redirect_url . '&error=Failed to upload profile picture.');
                exit();
            }
        }

        // Update user details in the database
        if ($profile_picture_path_to_update) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, profile_picture_path = ? WHERE id = ?");
            $stmt->bind_param("ssss", $name, $email, $profile_picture_path_to_update, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("sss", $name, $email, $user_id);
        }
        $stmt->execute();
        $stmt->close();

        header('Location: ' . $redirect_url . '&success=Account details updated successfully.');
        exit();
    } elseif ($action === 'change_password') {
        // --- Change Password --- 
        if (!isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_new_password'])) {
            header('Location: ' . $redirect_url . '&error=All password fields are required.');
            exit();
        }

        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_new_password = $_POST['confirm_new_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
            header('Location: ' . $redirect_url . '&error=Password fields cannot be empty.');
            exit();
        }

        if ($new_password !== $confirm_new_password) {
            header('Location: ' . $redirect_url . '&error=New passwords do not match.');
            exit();
        }

        if (strlen($new_password) < 8) { // Basic password strength check
            header('Location: ' . $redirect_url . '&error=New password must be at least 8 characters long.');
            exit();
        }

        // Fetch current password hash
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();

        if (!$user_data || !verifyPassword($current_password, $user_data['password'])) {
            header('Location: ' . $redirect_url . '&error=Incorrect current password.');
            exit();
        }

        // Hash new password and update
        $hashed_new_password = hashPassword($new_password);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("ss", $hashed_new_password, $user_id);
        $stmt->execute();
        $stmt->close();

        header('Location: ' . $redirect_url . '&success=Password changed successfully.');
        exit();
    } else {
        header('Location: ' . $redirect_url . '&error=Invalid action specified.');
        exit();
    }
} catch (Exception $e) { // Changed from PDOException to generic Exception
    error_log("Account update error: " . $e->getMessage()); // Log the detailed error
    header('Location: ' . $redirect_url . '&error=Database error occurred. Please try again. Details: ' . $e->getCode());
    exit();
}
