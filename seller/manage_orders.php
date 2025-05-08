<?php
// Ensure strict typing and error reporting
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1'); // Ensure errors are displayed

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the user is logged in and is a seller or admin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Seller', 'Admin'])) {
    // Redirect to login page or an unauthorized page
    header('Location: ../index.php?page=login&message=You must be logged in as a seller to access this page.');
    exit;
}

global $conn; // Use the global connection established by index.php

if (!$conn) {
    die("Database connection failed."); // Or handle more gracefully
}

$seller_id = $_SESSION['user_id'];
$orders = [];

// Fetch orders containing products sold by the logged-in seller
// We need to join orders, order_items, products, and users (for customer name)
$stmt = $conn->prepare("
    SELECT DISTINCT
        o.id AS order_id,
        o.order_date,
        u_customer.name AS customer_name,
        o.total_amount,
        o.status AS order_status,
        o.payment_method,
        o.epayment_type,
        o.created_at
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    LEFT JOIN users u_customer ON o.user_id = u_customer.id
    WHERE p.seller_id = ?
    ORDER BY o.order_date DESC
");

if ($stmt) {
    $stmt->bind_param("s", $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
} else {
    // Handle statement preparation error
    // For now, just log or display a generic error
    error_log("Failed to prepare statement for fetching seller orders: " . $conn->error);
    // You might want to set an error message to display to the user
}
?>

<div class="container mt-4">
    <h2>Manage Orders</h2>

    <?php if (isset($_GET['success_message'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success_message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error_message'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error_message']); ?></div>
    <?php endif; ?>

    <table id="ordersTable" class="table table-striped table-bordered" style="width:100%">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Customer Name</th>
                <th>Total Amount</th>
                <th>Payment Method</th>
                <th>E-Payment Type</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($order['order_date']))); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars(number_format((float)$order['total_amount'], 2)); ?></td>
                        <td><?php echo htmlspecialchars($order['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($order['epayment_type'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="badge bg-<?php echo getStatusBadgeClass($order['order_status']); ?>">
                                <?php echo htmlspecialchars($order['order_status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="index.php?page=seller/view_order_details&order_id=<?php echo htmlspecialchars($order['order_id']); ?>" class="btn btn-info btn-sm" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button type="button" class="btn btn-warning btn-sm" onclick="openUpdateStatusModal('<?php echo htmlspecialchars($order['order_id']); ?>', '<?php echo htmlspecialchars($order['order_status']); ?>')" title="Update Status">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($order['order_status'] !== 'Cancelled' && $order['order_status'] !== 'Delivered'): ?>
                                <button type="button" class="btn btn-danger btn-sm" onclick="confirmCancelOrder('<?php echo htmlspecialchars($order['order_id']); ?>')" title="Cancel Order">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center">No orders found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Modal for Updating Order Status -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="updateStatusForm" method="POST" action="api/orders/update_order_handler.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="modal_order_id">
                    <input type="hidden" name="current_status" id="modal_current_status">

                    <div class="mb-3">
                        <label for="new_status" class="form-label">New Status</label>
                        <select name="new_status" id="new_status" class="form-select" required>
                            <option value="Pending">Pending</option>
                            <option value="Awaiting Payment">Awaiting Payment</option>
                            <option value="Processing">Processing</option>
                            <option value="Shipped">Shipped</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3" id="trackingNumberField" style="display: none;">
                        <label for="tracking_number" class="form-label">Tracking Number (Optional)</label>
                        <input type="text" name="tracking_number" id="tracking_number" class="form-control">
                    </div>
                    <div class="mb-3" id="customerMessageField" style="display: none;">
                        <label for="custom_message" class="form-label">Message to Customer (Optional)</label>
                        <textarea name="custom_message" id="custom_message" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_order_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Helper function to determine badge class based on status
function getStatusBadgeClass($status)
{
    switch ($status) {
        case 'Pending':
            return 'secondary';
        case 'Awaiting Payment':
            return 'warning';
        case 'Processing':
            return 'info';
        case 'Shipped':
            return 'primary';
        case 'Delivered':
            return 'success';
        case 'Cancelled':
            return 'danger';
        default:
            return 'light';
    }
}
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        new DataTable('#ordersTable', {
            responsive: true,
            // Add any other DataTables options here
        });

        const newStatusSelect = document.getElementById('new_status');
        const trackingNumberField = document.getElementById('trackingNumberField');
        const customerMessageField = document.getElementById('customerMessageField');

        if (newStatusSelect) {
            newStatusSelect.addEventListener('change', function() {
                if (this.value === 'Shipped') {
                    trackingNumberField.style.display = 'block';
                    customerMessageField.style.display = 'block';
                } else {
                    trackingNumberField.style.display = 'none';
                    customerMessageField.style.display = 'none';
                }
            });
        }
    });

    function openUpdateStatusModal(orderId, currentStatus) {
        document.getElementById('modal_order_id').value = orderId;
        document.getElementById('modal_current_status').value = currentStatus;
        const newStatusSelect = document.getElementById('new_status');
        newStatusSelect.value = currentStatus; // Set current status as selected initially

        // Trigger change event to show/hide fields if initial status is 'Shipped'
        var event = new Event('change');
        newStatusSelect.dispatchEvent(event);

        var updateModal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
        updateModal.show();
    }

    function confirmCancelOrder(orderId) {
        if (confirm("Are you sure you want to cancel this order? This action cannot be undone and will restock items.")) {
            // Create a form dynamically and submit it
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'api/orders/update_order_handler.php'; // Point to the same handler

            var orderIdInput = document.createElement('input');
            orderIdInput.type = 'hidden';
            orderIdInput.name = 'order_id';
            orderIdInput.value = orderId;
            form.appendChild(orderIdInput);

            var newStatusInput = document.createElement('input');
            newStatusInput.type = 'hidden';
            newStatusInput.name = 'new_status';
            newStatusInput.value = 'Cancelled'; // Set status to Cancelled
            form.appendChild(newStatusInput);

            // Add a specific action input for cancellation if your handler needs to differentiate
            var cancelActionInput = document.createElement('input');
            cancelActionInput.type = 'hidden';
            cancelActionInput.name = 'cancel_order_action';
            cancelActionInput.value = '1';
            form.appendChild(cancelActionInput);

            document.body.appendChild(form);
            form.submit();
        }
    }
</script>