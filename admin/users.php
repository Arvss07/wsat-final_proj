<?php
// admin/users.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php?page=login&error=Unauthorized+access");
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/uuid.php';
require_once __DIR__ . '/../utils/hash.php';

$page_title = "Manage Accounts";

$action = $_GET['action'] ?? 'list'; // list, add, edit, (delete will be a post to this page)
$user_id_to_edit = $_GET['id'] ?? null;

$feedback = [];
$errors = [];

// Handle POST requests for add, edit, delete, block/unblock
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    try {
        if (isset($_POST['addUser'])) {
            // Add user logic
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role_id = $_POST['role_id'] ?? '';
            $is_blocked = isset($_POST['is_blocked']) ? 1 : 0;

            // Fetch Admin role ID to prevent assigning it
            $admin_role_id_value = '';
            $stmt_admin_role = $conn->prepare("SELECT id FROM roles WHERE name = 'Admin' LIMIT 1");
            if ($stmt_admin_role) {
                $stmt_admin_role->execute();
                $result_admin_role = $stmt_admin_role->get_result();
                if ($result_admin_role->num_rows > 0) {
                    $admin_role_id_value = $result_admin_role->fetch_assoc()['id'];
                }
                $stmt_admin_role->close();
            }

            if (!empty($admin_role_id_value) && $role_id === $admin_role_id_value) {
                $errors[] = "Cannot assign Admin role through this form.";
            }
            if (empty($name) || empty($email) || empty($password) || empty($role_id)) {
                $errors[] = "All fields (Name, Email, Password, Role) are required.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format.";
            }
            // Check if email exists
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $errors[] = "Email already exists.";
            }
            $stmt_check->close();

            if (empty($errors)) {
                $new_user_id = generateUuidV4();
                $hashed_password = hashPassword($password);
                $stmt_add = $conn->prepare("INSERT INTO users (id, name, email, password, role_id, is_blocked) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_add->bind_param("sssssi", $new_user_id, $name, $email, $hashed_password, $role_id, $is_blocked);
                if ($stmt_add->execute()) {
                    $feedback[] = "User added successfully.";
                } else {
                    $errors[] = "Failed to add user: " . $stmt_add->error;
                }
                $stmt_add->close();
            }
        } elseif (isset($_POST['editUser'])) {
            // Edit user logic
            $user_id = $_POST['user_id'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? ''; // Optional: only if changing
            $role_id = $_POST['role_id'] ?? ''; // Admins cannot change roles as per requirements
            $is_blocked = isset($_POST['is_blocked']) ? 1 : 0;

            if (empty($user_id) || empty($name) || empty($email)) {
                $errors[] = "User ID, Name, and Email are required.";
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format.";
            }
            // Check if email exists for another user
            $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt_check->bind_param("ss", $email, $user_id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $errors[] = "Email already exists for another user.";
            }
            $stmt_check->close();

            if (empty($errors)) {
                if (!empty($password)) {
                    $hashed_password = hashPassword($password);
                    $stmt_edit = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ?, is_blocked = ? WHERE id = ?");
                    $stmt_edit->bind_param("sssisi", $name, $email, $hashed_password, $is_blocked, $user_id);
                } else {
                    $stmt_edit = $conn->prepare("UPDATE users SET name = ?, email = ?, is_blocked = ? WHERE id = ?");
                    $stmt_edit->bind_param("ssisi", $name, $email, $is_blocked, $user_id);
                }
                if ($stmt_edit->execute()) {
                    $feedback[] = "User updated successfully.";
                } else {
                    $errors[] = "Failed to update user: " . $stmt_edit->error;
                }
                $stmt_edit->close();
            }
        } elseif (isset($_POST['deleteUser'])) {
            $user_id_to_delete = $_POST['user_id_to_delete'] ?? '';
            if (!empty($user_id_to_delete)) {
                // Prevent admin from deleting themselves
                if ($user_id_to_delete === $_SESSION['user_id']) {
                    $errors[] = "You cannot delete your own account.";
                } else {
                    $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
                    $stmt_delete->bind_param("s", $user_id_to_delete);
                    if ($stmt_delete->execute()) {
                        $feedback[] = "User deleted successfully.";
                    } else {
                        $errors[] = "Failed to delete user: " . $stmt_delete->error;
                    }
                    $stmt_delete->close();
                }
            } else {
                $errors[] = "User ID not provided for deletion.";
            }
        } elseif (isset($_POST['toggleBlockUser'])) {
            $user_id_to_toggle = $_POST['user_id_to_toggle'] ?? '';
            $current_status = (int)($_POST['current_status'] ?? 0);
            $new_status = $current_status ? 0 : 1;
            if (!empty($user_id_to_toggle)) {
                if ($user_id_to_toggle === $_SESSION['user_id'] && $new_status === 1) {
                    $errors[] = "You cannot block your own account.";
                } else {
                    $stmt_toggle = $conn->prepare("UPDATE users SET is_blocked = ? WHERE id = ?");
                    $stmt_toggle->bind_param("is", $new_status, $user_id_to_toggle);
                    if ($stmt_toggle->execute()) {
                        $feedback[] = "User status updated successfully.";
                    } else {
                        $errors[] = "Failed to update user status: " . $stmt_toggle->error;
                    }
                    $stmt_toggle->close();
                }
            } else {
                $errors[] = "User ID not provided for status toggle.";
            }
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = "An error occurred: " . $e->getMessage();
    }
}

// Fetch roles for dropdowns
$roles = [];
$result_roles = $conn->query("SELECT id, name FROM roles");
if ($result_roles) {
    while ($row = $result_roles->fetch_assoc()) {
        $roles[] = $row;
    }
}

$user_to_edit_data = null;
if ($action === 'edit' && $user_id_to_edit) {
    $stmt_user = $conn->prepare("SELECT id, name, email, role_id, is_blocked FROM users WHERE id = ?");
    $stmt_user->bind_param("s", $user_id_to_edit);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows === 1) {
        $user_to_edit_data = $result_user->fetch_assoc();
    } else {
        $errors[] = "User not found for editing.";
        $action = 'list'; // Revert to list view
    }
    $stmt_user->close();
}

?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo htmlspecialchars($page_title); ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php?page=admin/dashboard">Dashboard</a></li>
        <li class="breadcrumb-item active">Manage Accounts</li>
    </ol>

    <?php if (!empty($feedback)): ?>
        <div class="alert alert-success">
            <?php foreach ($feedback as $msg): echo htmlspecialchars($msg) . "<br>";
            endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $err): echo htmlspecialchars($err) . "<br>";
            endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($action === 'add' || ($action === 'edit' && $user_to_edit_data)): ?>
        <h2><?php echo $action === 'add' ? 'Add New User' : 'Edit User'; ?></h2>
        <form method="POST" action="index.php?page=admin/users">
            <?php if ($action === 'edit'): ?>
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_to_edit_data['id']); ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user_to_edit_data['name'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_to_edit_data['email'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password <?php if ($action === 'edit') echo "(leave blank to keep current)"; ?></label>
                <input type="password" class="form-control" id="password" name="password" <?php echo $action === 'add' ? 'required' : ''; ?>>
            </div>
            <div class="mb-3">
                <label for="role_id" class="form-label">Role</label>
                <select class="form-select" id="role_id" name="role_id" required <?php if ($action === 'edit') echo 'disabled'; /* Admins cannot change user roles */ ?>>
                    <option value="">Select Role</option>
                    <?php foreach ($roles as $role): ?>
                        <?php
                        // For 'add' action, skip the 'Admin' role.
                        if ($action === 'add' && $role['name'] === 'Admin') {
                            continue;
                        }
                        ?>
                        <option value="<?php echo htmlspecialchars($role['id']); ?>"
                            <?php
                            if ($action === 'edit' && $user_to_edit_data && $user_to_edit_data['role_id'] === $role['id']) {
                                echo 'selected';
                            } elseif ($action === 'add' && $role['name'] === 'Shopper') { // Default to Shopper for new users
                                echo 'selected';
                            }
                            ?>>
                            <?php echo htmlspecialchars($role['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($action === 'edit'): ?> <small class="form-text text-muted">Role cannot be changed after creation.</small><?php endif; ?>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="is_blocked" name="is_blocked" value="1" <?php echo ($user_to_edit_data['is_blocked'] ?? 0) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="is_blocked">Is Blocked</label>
            </div>

            <button type="submit" name="<?php echo $action === 'add' ? 'addUser' : 'editUser'; ?>" class="btn btn-primary">
                <?php echo $action === 'add' ? 'Add User' : 'Save Changes'; ?>
            </button>
            <a href="index.php?page=admin/users" class="btn btn-secondary">Cancel</a>
        </form>
    <?php else: ?>
        <div class="d-flex justify-content-end mb-3">
            <a href="index.php?page=admin/users&action=add" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add New User</a>
        </div>
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-people me-1"></i>
                User List
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="usersDataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_all_users = "SELECT u.id, u.name, u.email, r.name as role_name, u.is_blocked 
                                            FROM users u 
                                            JOIN roles r ON u.role_id = r.id 
                                            ORDER BY u.name ASC";
                            $result_all_users = $conn->query($sql_all_users);
                            if ($result_all_users && $result_all_users->num_rows > 0) {
                                while ($user = $result_all_users->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                                    echo "<td>" . htmlspecialchars($user['role_name']) . "</td>";
                                    echo "<td>";
                                    echo $user['is_blocked'] ? '<span class="badge bg-danger">Blocked</span>' : '<span class="badge bg-success">Active</span>';
                                    echo "</td>";
                                    echo "<td>";
                                    echo "<a href='index.php?page=admin/users&action=edit&id=" . htmlspecialchars($user['id']) . "' class='btn btn-sm btn-warning me-1' title='Edit'><i class='bi bi-pencil-square'></i></a>";

                                    // Toggle Block/Unblock Form
                                    echo "<form method='POST' action='index.php?page=admin/users' style='display: inline-block;' class='me-1'>";
                                    echo "<input type='hidden' name='user_id_to_toggle' value='" . htmlspecialchars($user['id']) . "'>";
                                    echo "<input type='hidden' name='current_status' value='" . (int)$user['is_blocked'] . "'>";
                                    if ($user['is_blocked']) {
                                        echo "<button type='submit' name='toggleBlockUser' class='btn btn-sm btn-success' title='Unblock'><i class='bi bi-unlock-fill'></i></button>";
                                    } else {
                                        echo "<button type='submit' name='toggleBlockUser' class='btn btn-sm btn-secondary' title='Block'><i class='bi bi-lock-fill'></i></button>";
                                    }
                                    echo "</form>";

                                    // Delete Form (conditionally show if not current admin)
                                    if ($user['id'] !== $_SESSION['user_id']) {
                                        echo "<form method='POST' action='index.php?page=admin/users' style='display: inline-block;' onsubmit=\"return confirm('Are you sure you want to delete this user? This action cannot be undone.');\">";
                                        echo "<input type='hidden' name='user_id_to_delete' value='" . htmlspecialchars($user['id']) . "'>";
                                        echo "<button type='submit' name='deleteUser' class='btn btn-sm btn-danger' title='Delete'><i class='bi bi-trash-fill'></i></button>";
                                        echo "</form>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5'>No users found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <p class="text-muted"><small>Note: For simplicity, DataTables JS library is not yet integrated. Pagination and advanced searching/sorting would typically be handled by such a library or server-side logic.</small></p>
    <?php endif; ?>
</div>

<!-- Include DataTables CSS/JS in footer if you decide to use it -->
<!-- <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet"> -->
<!-- <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script> -->
<!-- <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script> -->
<!-- <script>
    // $(document).ready(function() {
    //     $('#usersDataTable').DataTable();
    // });
</script> -->