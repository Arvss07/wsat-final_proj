<?php
// Ensure user is logged in and is a seller
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'Seller') {
    if (!isset($_SESSION['role_name']) || $_SESSION['role_name'] !== 'Seller') {
        echo "Unauthorized access.";
        exit();
    }
}

// Ensure $conn is available (it should be, from index.php -> config/database.php)
if (!isset($conn) || $conn->connect_error) {
    echo "Database connection is not available or failed. Please check the configuration.";
    exit(); 
}

?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col">
            <h2>Manage Shopper Orders</h2>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Order Listings</h4>
        </div>
        <div class="card-body">
            <p class="text-muted text-center"><em>Order management interface will be displayed here. Sellers will be able to view and update order statuses.</em></p>
            <!-- Placeholder for order table or list -->
            <!-- Example structure:
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>ORD-123</td>
                        <td>John Doe</td>
                        <td>2024-05-07</td>
                        <td>$150.00</td>
                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                        <td><a href="#" class="btn btn-sm btn-info">View Details</a></td>
                    </tr>
                </tbody>
            </table>
            -->
        </div>
    </div>
</div>