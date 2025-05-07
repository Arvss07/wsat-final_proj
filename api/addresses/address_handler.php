<?php
session_start();

require_once __DIR__ . '/../../config/database.php'; // Establishes $conn
require_once __DIR__ . '/../../utils/uuid.php';    // For generateUuidV4()

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated. Please login.']);
    http_response_code(401); // Unauthorized
    exit();
}

$user_id = $_SESSION['user_id'];
// Use role name from session, consistent with login_handler.php and account.php
$user_role = $_SESSION['role'] ?? null;

if (!$user_role) {
    echo json_encode(['success' => false, 'message' => 'User role not found in session.']);
    http_response_code(403); // Forbidden
    exit();
}

$is_seller = ($user_role === 'Seller');
$response = ['success' => false, 'message' => 'Invalid request.']; // Default response

// --- Helper function to sanitize input ---
function sanitize_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// --- Helper function to validate address fields ---
function validate_address_fields($post_data)
{
    $errors = [];
    if (empty($post_data['street'])) $errors[] = "Street is required.";
    if (empty($post_data['city'])) $errors[] = "City is required.";
    if (empty($post_data['province'])) $errors[] = "Province is required.";
    if (empty($post_data['postal_code'])) $errors[] = "Postal code is required.";
    if (empty($post_data['country'])) $errors[] = "Country is required.";
    return $errors;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['action'])) {
        $response['message'] = 'No action specified.';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    $action = $_POST['action'];

    try {
        $conn->begin_transaction();

        switch ($action) {
            case 'add_address':
            case 'update_address': // Combined logic for add/update for simplicity, especially for seller

                $validation_errors = validate_address_fields($_POST);
                if (!empty($validation_errors)) {
                    $response['message'] = implode(" ", $validation_errors);
                    http_response_code(400);
                    break;
                }

                $street = sanitize_input($_POST['street']);
                $city = sanitize_input($_POST['city']);
                $province = sanitize_input($_POST['province']);
                $postal_code = sanitize_input($_POST['postal_code']);
                $country = sanitize_input($_POST['country']);
                $is_default = isset($_POST['is_default']) && $_POST['is_default'] == '1' ? 1 : 0;
                $address_id = isset($_POST['address_id']) ? sanitize_input($_POST['address_id']) : null;

                if ($is_seller) {
                    // Seller can only have one address. If address_id is provided, it's an update.
                    // If not, check if one exists. If so, update it. Otherwise, add new.
                    $stmt_check = $conn->prepare("SELECT id FROM addresses WHERE user_id = ?");
                    $stmt_check->bind_param("s", $user_id);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    $existing_address = $result_check->fetch_assoc();
                    $stmt_check->close();

                    if ($existing_address) {
                        $address_id = $existing_address['id']; // Force update of existing address
                        $action = 'update_address'; // Ensure it's treated as update
                    } else {
                        $action = 'add_address'; // Ensure it's treated as add
                    }
                    $is_default = 1; // Seller's address is always their "default" and only address
                }

                if ($action === 'add_address') {
                    if ($is_seller && $existing_address) { // Should have been caught above, but double check
                        $response['message'] = 'Seller can only have one address. Please update the existing one.';
                        http_response_code(400);
                        break;
                    }

                    $new_address_id = generateUuidV4();
                    $stmt = $conn->prepare("INSERT INTO addresses (id, user_id, street, city, province, postal_code, country, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssssssi", $new_address_id, $user_id, $street, $city, $province, $postal_code, $country, $is_default);

                    if ($stmt->execute()) {
                        if (!$is_seller && $is_default) {
                            // If new default is set for shopper, unset other defaults
                            $stmt_unset = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND id != ?");
                            $stmt_unset->bind_param("ss", $user_id, $new_address_id);
                            $stmt_unset->execute();
                            $stmt_unset->close();
                        }
                        $response = ['success' => true, 'message' => 'Address added successfully.', 'address' => ['id' => $new_address_id, 'street' => $street, 'city' => $city, 'province' => $province, 'postal_code' => $postal_code, 'country' => $country, 'is_default' => $is_default]];
                    } else {
                        $response['message'] = 'Failed to add address. Error: ' . $stmt->error;
                        http_response_code(500);
                    }
                    $stmt->close();
                } elseif ($action === 'update_address') {
                    if (empty($address_id)) {
                        $response['message'] = 'Address ID is required for update.';
                        http_response_code(400);
                        break;
                    }
                    // Verify address belongs to user (especially for shoppers)
                    if (!$is_seller) {
                        $stmt_verify = $conn->prepare("SELECT id FROM addresses WHERE id = ? AND user_id = ?");
                        $stmt_verify->bind_param("ss", $address_id, $user_id);
                        $stmt_verify->execute();
                        if ($stmt_verify->get_result()->num_rows === 0) {
                            $response['message'] = 'Address not found or permission denied.';
                            http_response_code(404);
                            $stmt_verify->close();
                            break;
                        }
                        $stmt_verify->close();
                    }

                    $stmt = $conn->prepare("UPDATE addresses SET street = ?, city = ?, province = ?, postal_code = ?, country = ?, is_default = ? WHERE id = ? AND user_id = ?");
                    $stmt->bind_param("sssssiss", $street, $city, $province, $postal_code, $country, $is_default, $address_id, $user_id);

                    if ($stmt->execute()) {
                        if (!$is_seller && $is_default) {
                            // If this address is set to default for shopper, unset other defaults
                            $stmt_unset = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND id != ?");
                            $stmt_unset->bind_param("ss", $user_id, $address_id);
                            $stmt_unset->execute();
                            $stmt_unset->close();
                        }
                        $response = ['success' => true, 'message' => 'Address updated successfully.', 'address' => ['id' => $address_id, 'street' => $street, 'city' => $city, 'province' => $province, 'postal_code' => $postal_code, 'country' => $country, 'is_default' => $is_default]];
                    } else {
                        $response['message'] = 'Failed to update address. Error: ' . $stmt->error;
                        http_response_code(500);
                    }
                    $stmt->close();
                }
                break;

            case 'delete_address':
                if ($is_seller) {
                    $response['message'] = 'Sellers cannot delete their primary address. Please update it instead.';
                    http_response_code(403); // Forbidden
                    break;
                }
                if (!isset($_POST['address_id']) || empty($_POST['address_id'])) {
                    $response['message'] = 'Address ID is required.';
                    http_response_code(400);
                    break;
                }
                $address_id_to_delete = sanitize_input($_POST['address_id']);

                // Check if it's the default address, if so, prevent deletion or handle (e.g. pick new default - not implemented here)
                // For now, we allow deleting default. The frontend might need to handle UI for no default.
                $stmt = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ss", $address_id_to_delete, $user_id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $response = ['success' => true, 'message' => 'Address deleted successfully.'];
                    } else {
                        $response['message'] = 'Address not found or already deleted.';
                        http_response_code(404);
                    }
                } else {
                    $response['message'] = 'Failed to delete address. Error: ' . $stmt->error;
                    http_response_code(500);
                }
                $stmt->close();
                break;

            case 'set_default_address':
                if ($is_seller) {
                    $response['message'] = 'Seller address is always the default.';
                    http_response_code(400);
                    break;
                }
                if (!isset($_POST['address_id']) || empty($_POST['address_id'])) {
                    $response['message'] = 'Address ID is required.';
                    http_response_code(400);
                    break;
                }
                $address_id_to_default = sanitize_input($_POST['address_id']);

                // Set all other addresses for this user to not default
                $stmt_unset = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ? AND id != ?");
                $stmt_unset->bind_param("ss", $user_id, $address_id_to_default);
                $stmt_unset->execute();
                $stmt_unset->close();

                // Set the specified address to default
                $stmt_set = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
                $stmt_set->bind_param("ss", $address_id_to_default, $user_id);
                if ($stmt_set->execute()) {
                    if ($stmt_set->affected_rows > 0) {
                        $response = ['success' => true, 'message' => 'Default address updated.'];
                    } else {
                        $response['message'] = 'Address not found or no change needed.';
                        // It's not an error if it was already default and others were not, or if address not found.
                        // Consider if 404 is appropriate if address_id is invalid.
                        // For now, if affected_rows is 0, it might mean it was already default or ID was wrong.
                        // Frontend will refresh, so it should be fine.
                    }
                } else {
                    $response['message'] = 'Failed to set default address. Error: ' . $stmt_set->error;
                    http_response_code(500);
                }
                $stmt_set->close();
                break;

            default:
                $response['message'] = 'Invalid action specified.';
                http_response_code(400); // Bad Request
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Address API Error (POST): " . $e->getMessage() . " for user " . $user_id . " with action " . ($action ?? 'unknown'));
        $response['message'] = 'An internal server error occurred. Please try again. Code: ' . $e->getCode();
        http_response_code(500); // Internal Server Error
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['action'])) {
        $response['message'] = 'No GET action specified.';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    $action = $_GET['action'];

    try {
        switch ($action) {
            case 'get_addresses':
                $stmt = $conn->prepare("SELECT id, street, city, province, postal_code, country, is_default FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $addresses = [];
                while ($row = $result->fetch_assoc()) {
                    // Ensure is_default is boolean/integer 0 or 1 for JS
                    $row['is_default'] = (bool)$row['is_default'];
                    $addresses[] = $row;
                }
                $stmt->close();
                $response = ['success' => true, 'addresses' => $addresses];
                if ($is_seller && empty($addresses)) {
                    // For seller, if no address, it's fine, frontend form will be empty for add.
                }
                break;

            case 'get_address_details':
                if (!isset($_GET['id']) || empty($_GET['id'])) {
                    $response['message'] = 'Address ID is required for details.';
                    http_response_code(400);
                    break;
                }
                $address_id_to_fetch = sanitize_input($_GET['id']);
                $stmt = $conn->prepare("SELECT id, street, city, province, postal_code, country, is_default FROM addresses WHERE id = ? AND user_id = ?");
                $stmt->bind_param("ss", $address_id_to_fetch, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($address_detail = $result->fetch_assoc()) {
                    $address_detail['is_default'] = (bool)$address_detail['is_default'];
                    $response = ['success' => true, 'address' => $address_detail];
                } else {
                    $response['message'] = 'Address not found or permission denied.';
                    http_response_code(404);
                }
                $stmt->close();
                break;

            default:
                $response['message'] = 'Invalid GET action specified.';
                http_response_code(400);
        }
    } catch (Exception $e) {
        error_log("Address API Error (GET): " . $e->getMessage() . " for user " . $user_id . " with action " . ($action ?? 'unknown'));
        $response['message'] = 'An internal server error occurred while fetching data. Code: ' . $e->getCode();
        http_response_code(500);
    }
} else {
    $response['message'] = 'Invalid request method.';
    http_response_code(405); // Method Not Allowed
}

$conn->close();
echo json_encode($response);
exit();
