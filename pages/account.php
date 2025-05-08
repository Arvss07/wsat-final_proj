<?php
// File: pages/account.php
// Purpose: Displays user account information and forms for updates.

// Ensure user is logged in
// session_start(); // Already started in index.php or a global include
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login&message=Please login to access your account.');
    exit();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/hash.php'; // For password functions if needed here

$user_id = $_SESSION['user_id'];
$user = null;
$error_message = '';
$success_message = '';

// Fetch user data
try {
    $stmt = $conn->prepare("SELECT name, email, profile_picture_path FROM users WHERE id = ?");
    $stmt->bind_param("s", $user_id); // Bind the user_id parameter
    $stmt->execute();
    $result = $stmt->get_result(); // Get the result set from the prepared statement
    $user = $result->fetch_assoc(); // Fetch the data as an associative array
    $stmt->close(); // Close the statement
} catch (Exception $e) { // Catch generic Exception for MySQLi errors or other issues
    $error_message = "Error fetching account details: " . $e->getMessage();
}

if (!$user) {
    // Should not happen if session is valid, but good to check
    header('Location: index.php?page=login&message=User not found.');
    exit();
}

// Handle messages from handler
if (isset($_GET['success'])) {
    $success_message = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10"> <!-- Increased col-md-8 to col-md-10 for more space -->
            <h2>My Account</h2>
            <hr>

            <?php if ($success_message): ?>
                <div class="alert alert-success" id="globalSuccessMessage"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger" id="globalErrorMessage"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <!-- Tab Navigation -->
            <ul class="nav nav-tabs mb-3" id="accountTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details-pane" type="button" role="tab" aria-controls="details-pane" aria-selected="true">Account Details</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password-pane" type="button" role="tab" aria-controls="password-pane" aria-selected="false">Change Password</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="address-tab" data-bs-toggle="tab" data-bs-target="#address-pane" type="button" role="tab" aria-controls="address-pane" aria-selected="false">Address Management</button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="accountTabsContent">
                <!-- Account Details Pane -->
                <div class="tab-pane fade show active" id="details-pane" role="tabpanel" aria-labelledby="details-tab">
                    <div class="card mb-4">
                        <div class="card-header">
                            Account Details
                        </div>
                        <div class="card-body">
                            <form id="updateDetailsForm" action="api/user/update_account_handler.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="action" value="update_details">
                                <div class="mb-3 row">
                                    <label for="name" class="col-sm-3 col-form-label">Name</label>
                                    <div class="col-sm-9">
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="email" class="col-sm-3 col-form-label">Email</label>
                                    <div class="col-sm-9">
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="profile_picture" class="col-sm-3 col-form-label">Profile Picture</label>
                                    <div class="col-sm-9">
                                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                                        <?php if ($user['profile_picture_path']): ?>
                                            <img src="<?php echo htmlspecialchars($user['profile_picture_path']); ?>" alt="Profile Picture" class="img-thumbnail mt-2" style="max-height: 150px;">
                                        <?php else: ?>
                                            <p class="form-text text-muted">No profile picture uploaded.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <div class="col-sm-9 offset-sm-3">
                                        <button type="submit" class="btn btn-primary">Update Details</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password Pane -->
                <div class="tab-pane fade" id="password-pane" role="tabpanel" aria-labelledby="password-tab">
                    <div class="card">
                        <div class="card-header">
                            Change Password
                        </div>
                        <div class="card-body">
                            <form id="changePasswordForm" action="api/user/update_account_handler.php" method="POST">
                                <input type="hidden" name="action" value="change_password">
                                <div class="mb-3 row">
                                    <label for="current_password" class="col-sm-3 col-form-label">Current Password</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="new_password" class="col-sm-3 col-form-label">New Password</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <label for="confirm_new_password" class="col-sm-3 col-form-label">Confirm New Password</label>
                                    <div class="col-sm-9">
                                        <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required>
                                    </div>
                                </div>
                                <div class="mb-3 row">
                                    <div class="col-sm-9 offset-sm-3">
                                        <button type="submit" class="btn btn-primary">Change Password</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Address Management Pane -->
                <div class="tab-pane fade" id="address-pane" role="tabpanel" aria-labelledby="address-tab">
                    <div class="card">
                        <div class="card-header">
                            Address Management
                        </div>
                        <div class="card-body">
                            <!-- Messages specific to address management -->
                            <div id="addressSuccessMessage" class="alert alert-success" style="display: none;"></div>
                            <div id="addressErrorMessage" class="alert alert-danger" style="display: none;"></div>

                            <?php
                            $user_role = $_SESSION['role'] ?? 'Shopper'; // Default to Shopper if not set

                            if ($user_role === 'Seller'):
                            ?>
                                <h4>My Business Address</h4>
                                <p>This address is used to show shoppers where your products originate from.</p>
                                <form id="sellerAddressForm">
                                    <input type="hidden" name="action" value="update_seller_address">
                                    <input type="hidden" name="address_id" id="seller_address_id" value="">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="seller_street" class="form-label">Street</label>
                                            <input type="text" class="form-control" id="seller_street" name="street" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="seller_barangay" class="form-label">Barangay</label>
                                            <input type="text" class="form-control" id="seller_barangay" name="barangay" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="seller_city" class="form-label">City/Municipality</label>
                                            <input type="text" class="form-control" id="seller_city" name="city" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="seller_province" class="form-label">Province</label>
                                            <input type="text" class="form-control" id="seller_province" name="province" required>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="seller_country" class="form-label">Country</label>
                                            <input type="text" class="form-control" id="seller_country" name="country" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="seller_postal_code" class="form-label">Postal Code</label>
                                            <input type="text" class="form-control" id="seller_postal_code" name="postal_code" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Save Business Address</button>
                                </form>
                            <?php else: ?>
                                <h4>My Addresses</h4>
                                <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addressModal">
                                    Add New Address
                                </button>
                                <div id="shopperAddressesList">
                                    <p>Loading addresses...</p>
                                </div>

                                <!-- Add/Edit Address Modal for Shoppers -->
                                <div class="modal fade" id="addressModal" tabindex="-1" aria-labelledby="addressModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form id="shopperAddressForm">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="addressModalLabel">Add New Address</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="action" id="shopper_address_action" value="add_address">
                                                    <input type="hidden" name="address_id" id="shopper_address_id" value="">

                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label for="shopper_street" class="form-label">Street</label>
                                                            <input type="text" class="form-control" id="shopper_street" name="street" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="shopper_barangay" class="form-label">Barangay</label>
                                                            <input type="text" class="form-control" id="shopper_barangay" name="barangay" required>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label for="shopper_city" class="form-label">City/Municipality</label>
                                                            <input type="text" class="form-control" id="shopper_city" name="city" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="shopper_province" class="form-label">Province</label>
                                                            <input type="text" class="form-control" id="shopper_province" name="province" required>
                                                        </div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label for="shopper_postal_code" class="form-label">Postal Code</label>
                                                            <input type="text" class="form-control" id="shopper_postal_code" name="postal_code" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="shopper_country" class="form-label">Country</label>
                                                            <input type="text" class="form-control" id="shopper_country" name="country" required>
                                                        </div>
                                                    </div>
                                                    <div class="form-check mb-3">
                                                        <input class="form-check-input" type="checkbox" value="1" id="shopper_is_default" name="is_default">
                                                        <label class="form-check-label" for="shopper_is_default">
                                                            Set as default address
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="submit" class="btn btn-primary">Save Address</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var accountTabs = document.querySelectorAll('#accountTabs button[data-bs-toggle="tab"]');
        accountTabs.forEach(function(tab) {
            tab.addEventListener('shown.bs.tab', function(event) {
                localStorage.setItem('activeAccountTab', event.target.id);
            });
        });

        var activeTabId = localStorage.getItem('activeAccountTab');
        if (activeTabId) {
            var activeTab = document.getElementById(activeTabId);
            if (activeTab) {
                new bootstrap.Tab(activeTab).show();
            }
        }

        if (window.location.search.includes('success=') || window.location.search.includes('error=')) {
            const url = new URL(window.location);
            url.searchParams.delete('success');
            url.searchParams.delete('error');
            window.history.replaceState({}, document.title, url.pathname + url.search);
        }

        const userRole = "<?php echo $_SESSION['role'] ?? 'Shopper'; ?>"; // Get role from PHP session

        function displayAddressMessage(message, type) {
            const successMsgDiv = document.getElementById('addressSuccessMessage');
            const errorMsgDiv = document.getElementById('addressErrorMessage');
            successMsgDiv.style.display = 'none';
            errorMsgDiv.style.display = 'none';
            successMsgDiv.textContent = '';
            errorMsgDiv.textContent = '';

            if (type === 'success') {
                successMsgDiv.textContent = message;
                successMsgDiv.style.display = 'block';
            } else {
                errorMsgDiv.textContent = message;
                errorMsgDiv.style.display = 'block';
            }
        }

        if (userRole === 'Seller') {
            loadSellerAddress();
            const sellerAddressForm = document.getElementById('sellerAddressForm');
            if (sellerAddressForm) {
                sellerAddressForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    const action = document.getElementById('seller_address_id').value ? 'update_address' : 'add_address';
                    formData.set('action', action);

                    fetch('api/addresses/address_handler.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                displayAddressMessage(data.message || 'Address saved successfully!', 'success');
                                if (data.address && data.address.id) {
                                    document.getElementById('seller_address_id').value = data.address.id;
                                }
                            } else {
                                displayAddressMessage(data.message || 'Failed to save address.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Seller address form error:', err);
                            displayAddressMessage('An error occurred. Please try again.', 'error');
                        });
                });
            }
        } else {
            loadShopperAddresses();
            const shopperAddressForm = document.getElementById('shopperAddressForm');
            const addressModal = new bootstrap.Modal(document.getElementById('addressModal'));
            const addressModalLabel = document.getElementById('addressModalLabel');

            document.querySelector('[data-bs-target="#addressModal"]').addEventListener('click', function() {
                shopperAddressForm.reset();
                document.getElementById('shopper_address_action').value = 'add_address';
                document.getElementById('shopper_address_id').value = '';
                addressModalLabel.textContent = 'Add New Address';
            });

            if (shopperAddressForm) {
                shopperAddressForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);

                    fetch('api/addresses/address_handler.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                displayAddressMessage(data.message || 'Address saved successfully!', 'success');
                                loadShopperAddresses();
                                addressModal.hide();
                            } else {
                                displayAddressMessage(data.message || 'Failed to save address.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Shopper address form error:', err);
                            displayAddressMessage('An error occurred. Please try again.', 'error');
                        });
                });
            }
        }

        function loadSellerAddress() {
            fetch('api/addresses/address_handler.php?action=get_addresses')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.addresses && data.addresses.length > 0) {
                        const address = data.addresses[0];
                        document.getElementById('seller_address_id').value = address.id;
                        document.getElementById('seller_street').value = address.street;
                        document.getElementById('seller_city').value = address.city;
                        document.getElementById('seller_province').value = address.province;
                        document.getElementById('seller_postal_code').value = address.postal_code;
                        document.getElementById('seller_country').value = address.country;
                    } else if (!data.success) {
                        displayAddressMessage(data.message || 'Could not load address.', 'error');
                    }
                })
                .catch(err => {
                    console.error('Error loading seller address:', err);
                    displayAddressMessage('Error loading address information.', 'error');
                });
        }

        function loadShopperAddresses() {
            const listContainer = document.getElementById('shopperAddressesList');
            listContainer.innerHTML = '<p>Loading addresses...</p>';

            fetch('api/addresses/address_handler.php?action=get_addresses')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.addresses) {
                        if (data.addresses.length === 0) {
                            listContainer.innerHTML = '<p>You have not added any addresses yet.</p>';
                            return;
                        }
                        let html = '<ul class="list-group">';
                        data.addresses.forEach(addr => {
                            html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>${addr.street}, ${addr.city}, ${addr.province}</strong><br>
                                        ${addr.postal_code}, ${addr.country}
                                        ${addr.is_default ? '<span class="badge bg-primary ms-2">Default</span>' : ''}
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary me-2 edit-address-btn" data-id="${addr.id}">Edit</button>
                                        <button class="btn btn-sm btn-outline-danger delete-address-btn" data-id="${addr.id}">Delete</button>
                                        ${!addr.is_default ? '<button class="btn btn-sm btn-outline-secondary ms-2 set-default-btn" data-id="'+addr.id+'">Set Default</button>' : ''}
                                    </div>
                                 </li>`;
                        });
                        html += '</ul>';
                        listContainer.innerHTML = html;
                        attachShopperButtonListeners();
                    } else {
                        listContainer.innerHTML = '<p class="text-danger">Could not load addresses. ' + (data.message || '') + '</p>';
                    }
                })
                .catch(err => {
                    console.error('Error loading shopper addresses:', err);
                    listContainer.innerHTML = '<p class="text-danger">Error loading addresses information.</p>';
                });
        }

        function attachShopperButtonListeners() {
            document.querySelectorAll('.edit-address-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const addressId = this.dataset.id;
                    fetch(`api/addresses/address_handler.php?action=get_address_details&id=${addressId}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.address) {
                                const addr = data.address;
                                document.getElementById('shopper_address_action').value = 'update_address';
                                document.getElementById('shopper_address_id').value = addr.id;
                                document.getElementById('shopper_street').value = addr.street;
                                document.getElementById('shopper_city').value = addr.city;
                                document.getElementById('shopper_province').value = addr.province;
                                document.getElementById('shopper_postal_code').value = addr.postal_code;
                                document.getElementById('shopper_country').value = addr.country;
                                document.getElementById('shopper_is_default').checked = !!parseInt(addr.is_default);
                                document.getElementById('addressModalLabel').textContent = 'Edit Address';
                                new bootstrap.Modal(document.getElementById('addressModal')).show();
                            } else {
                                displayAddressMessage(data.message || 'Could not load address details.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Error fetching address details:', err);
                            displayAddressMessage('Error fetching address details.', 'error');
                        });
                });
            });

            document.querySelectorAll('.delete-address-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const addressId = this.dataset.id;
                    if (confirm('Are you sure you want to delete this address?')) {
                        fetch('api/addresses/address_handler.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: `action=delete_address&address_id=${addressId}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    displayAddressMessage(data.message || 'Address deleted successfully!', 'success');
                                    loadShopperAddresses();
                                } else {
                                    displayAddressMessage(data.message || 'Failed to delete address.', 'error');
                                }
                            })
                            .catch(err => {
                                console.error('Error deleting address:', err);
                                displayAddressMessage('An error occurred while deleting the address.', 'error');
                            });
                    }
                });
            });

            document.querySelectorAll('.set-default-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const addressId = this.dataset.id;
                    fetch('api/addresses/address_handler.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `action=set_default_address&address_id=${addressId}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                displayAddressMessage(data.message || 'Default address updated!', 'success');
                                loadShopperAddresses();
                            } else {
                                displayAddressMessage(data.message || 'Failed to set default address.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error('Error setting default address:', err);
                            displayAddressMessage('An error occurred while setting default address.', 'error');
                        });
                });
            });
        }
    });
</script>