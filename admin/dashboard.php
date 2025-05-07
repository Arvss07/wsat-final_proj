<?php
// admin/dashboard.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is admin, otherwise redirect or show error
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: index.php?page=login&error=Unauthorized+access");
    exit;
}

require_once __DIR__ . '/../config/database.php'; // $conn

$page_title = "Admin Dashboard";

// Analytics: Count users by role
$user_counts = [
    'Admin' => 0,
    'Seller' => 0,
    'Shopper' => 0,
    'Total' => 0
];
$sql_users = "SELECT r.name as role_name, COUNT(u.id) as count 
              FROM users u 
              JOIN roles r ON u.role_id = r.id 
              GROUP BY r.name";
$result_users = $conn->query($sql_users);
if ($result_users) {
    while ($row = $result_users->fetch_assoc()) {
        if (isset($user_counts[$row['role_name']])) {
            $user_counts[$row['role_name']] = (int)$row['count'];
        }
        $user_counts['Total'] += (int)$row['count'];
    }
}

// Analytics: Total products
$total_products = 0;
$sql_products = "SELECT COUNT(id) as count FROM products";
$result_products = $conn->query($sql_products);
if ($result_products && $result_products->num_rows > 0) {
    $total_products = (int)$result_products->fetch_assoc()['count'];
}

// Analytics: Recent orders (e.g., last 5)
// For simplicity, just counting total orders for now. A more detailed list would be better.
$total_orders = 0;
$sql_orders = "SELECT COUNT(id) as count FROM orders";
$result_orders = $conn->query($sql_orders);
if ($result_orders && $result_orders->num_rows > 0) {
    $total_orders = (int)$result_orders->fetch_assoc()['count'];
}

?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo htmlspecialchars($page_title); ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Overview</li>
    </ol>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3"><?php echo $user_counts['Total']; ?></div>
                            <div>Total Users</div>
                        </div>
                        <i class="bi bi-people-fill" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="index.php?page=admin/users">View Details</a>
                    <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3"><?php echo $total_products; ?></div>
                            <div>Total Products</div>
                        </div>
                        <i class="bi bi-box-seam" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="index.php?page=admin/products">View Details</a>
                    <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3"><?php echo $total_orders; ?></div>
                            <div>Total Orders</div>
                        </div>
                        <i class="bi bi-cart-check" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">View Details (Orders)</a> <!-- Link to future orders page -->
                    <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fs-3">?</div> <!-- Placeholder for another metric -->
                            <div>New Registrations (soon)</div>
                        </div>
                        <i class="bi bi-person-plus-fill" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#">View Details</a>
                    <div class="small text-white"><i class="bi bi-chevron-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-people-fill me-1"></i>
                    Users by Role
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Administrators
                            <span class="badge bg-primary rounded-pill"><?php echo $user_counts['Admin']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Sellers
                            <span class="badge bg-primary rounded-pill"><?php echo $user_counts['Seller']; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Shoppers
                            <span class="badge bg-primary rounded-pill"><?php echo $user_counts['Shopper']; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-bar-chart-line-fill me-1"></i>
                    More Analytics (Placeholder)
                </div>
                <div class="card-body">
                    <p>Further charts or data summaries can be added here.</p>
                    <canvas id="myAreaChart" width="100%" height="40"></canvas> <!-- Example for a chart -->
                </div>
            </div>
        </div>
    </div>

</div>

<!-- If you plan to use Chart.js or similar, include its CDN link in footer.php or here -->
<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script> -->
<!-- <script>
    // Example: Set new default font family and font color to mimic Bootstrap's default styling
    // Chart.defaults.global.defaultFontFamily = '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
    // Chart.defaults.global.defaultFontColor = '#292b2c';
    // Area Chart Example (requires chart.js)
    // var ctx = document.getElementById("myAreaChart");
    // var myLineChart = new Chart(ctx, {
    //   type: 'line',
    //   data: {
    //     labels: ["Mar 1", "Mar 2", "Mar 3"], // Example labels
    //     datasets: [{
    //       label: "Sessions",
    //       lineTension: 0.3,
    //       backgroundColor: "rgba(2,117,216,0.2)",
    //       borderColor: "rgba(2,117,216,1)",
    //       pointRadius: 5,
    //       pointBackgroundColor: "rgba(2,117,216,1)",
    //       pointBorderColor: "rgba(255,255,255,0.8)",
    //       pointHoverRadius: 5,
    //       pointHoverBackgroundColor: "rgba(2,117,216,1)",
    //       pointHitRadius: 50,
    //       pointBorderWidth: 2,
    //       data: [10000, 30162, 26263], // Example data
    //     }],
    //   },
    //   options: {
    //     scales: {
    //       xAxes: [{
    //         time: {
    //           unit: 'date'
    //         },
    //         gridLines: {
    //           display: false
    //         },
    //         ticks: {
    //           maxTicksLimit: 7
    //         }
    //       }],
    //       yAxes: [{
    //         ticks: {
    //           min: 0,
    //           max: 40000, // Adjust based on your data
    //           maxTicksLimit: 5
    //         },
    //         gridLines: {
    //           color: "rgba(0, 0, 0, .125)",
    //         }
    //       }],
    //     },
    //     legend: {
    //       display: false
    //     }
    //   }
    // });
</script> -->