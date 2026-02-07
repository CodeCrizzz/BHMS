<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('admin');

// Count Total Tenants
$sql_tenants = "SELECT COUNT(*) as total FROM users WHERE role='tenant'";
$res_tenants = $conn->query($sql_tenants);
$total_tenants = $res_tenants->fetch_assoc()['total'];

// Count Occupied Rooms
$sql_rooms = "SELECT COUNT(*) as total FROM rooms WHERE status='occupied'";
$res_rooms = $conn->query($sql_rooms);
$occupied_rooms = $res_rooms->fetch_assoc()['total'];

// Calculate Total Revenue (Only 'paid' status)
$sql_revenue = "SELECT SUM(amount) as total FROM payments WHERE status='paid'";
$res_revenue = $conn->query($sql_revenue);
$row_revenue = $res_revenue->fetch_assoc();
$total_revenue = $row_revenue['total'] ? $row_revenue['total'] : 0.00;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-custom px-3 py-3 shadow-sm d-flex justify-content-between flex-nowrap" style="z-index: 1000;">
    
    <div class="d-flex align-items-center gap-2" style="min-width: 0;"> <button class="btn btn-outline-secondary d-lg-none flex-shrink-0" id="sidebarToggle">
            <i class="fa fa-bars"></i>
        </button>

        <div class="navbar-brand-custom fw-bold text-truncate">
            <i class="fa fa-building me-2"></i> StudyStay Boarding House
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 flex-shrink-0">
        <button id="darkModeToggle" class="btn btn-outline-secondary rounded-circle" style="width: 38px; height: 38px; padding: 0; display: flex; align-items: center; justify-content: center;">
            <i class="fa fa-moon"></i>
        </button>
        
        <a href="../logout.php" class="btn btn-danger btn-sm d-flex align-items-center" style="height: 36px; white-space: nowrap;">
            <i class="fa fa-sign-out-alt me-1"></i> <span class="d-none d-sm-inline">Logout</span>
        </a>
    </div>
</nav>

<div class="d-flex">
    <div class="sidebar p-3" style="width: 250px; min-height: 100vh;">
        <h4 class="text-center mb-4">System Admin</h4>
        <a href="dashboard.php" class="active"><i class="fa fa-home me-2"></i> Dashboard</a>
        <a href="manage_tenants.php"><i class="fa fa-users me-2"></i> Manage Tenants</a>
        <a href="manage_rooms.php"><i class="fa fa-bed me-2"></i> Manage Rooms</a>
        <a href="billing.php"><i class="fa fa-file-invoice-dollar me-2"></i> Billing</a>
        <a href="manage_requests.php"><i class="fa fa-wrench me-2"></i> Manage Requests</a>
        <a href="talk.php"><i class="fa fa-comments me-2"></i> Chat Support</a>
        <a href="manage_admins.php"><i class="fa fa-user-shield me-2"></i> Manage Admins</a>
    </div>

    <div class="flex-grow-1 p-4" id="printableArea">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Dashboard Overview</h2>
            <button onclick="printDashboard()" class="btn btn-outline-dark"><i class="fa fa-file-pdf"></i> Export PDF</button>
        </div>

        <div class="row">
            <div class="col-md-3">
                <div class="card card-custom bg-primary-custom text-white p-3">
                    <h5>Total Tenants</h5>
                    <h2><?php echo $total_tenants; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom bg-info text-white p-3">
                    <h5>Occupied Rooms</h5>
                    <h2><?php echo $occupied_rooms; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-custom bg-success text-white p-3">
                    <h5>Total Revenue</h5>
                    <h2>Php. <?php echo number_format($total_revenue, 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="mt-5">
            <h4>Recent Payments</h4>
            <table class="table table-hover bg-white rounded shadow-sm">
                <thead class="bg-light">
                    <tr>
                        <th>Date</th>
                        <th>Tenant</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql_recent = "SELECT payments.*, users.fullname 
                                   FROM payments 
                                   JOIN users ON payments.tenant_id = users.id 
                                   ORDER BY date_created DESC LIMIT 5";

                    $result = $conn->query($sql_recent);

                    if($result->num_rows > 0){
                        while($row = $result->fetch_assoc()){
                            $badge = ($row['status'] == 'paid') ? 'bg-success' : 'bg-warning text-dark';
                    ?>
                    <tr>
                        <td><?php echo $row['date_created']; ?></td>
                        <td><?php echo $row['fullname']; ?></td>
                        <td><?php echo $row['description']; ?></td>

                        <td>Php <?php echo number_format($row['amount'], 2); ?></td>

                        <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center text-muted'>No recent activities found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="../assets/js/darkmode.js"></script>
<script src="../assets/js/script.js"></script>
<script src="../assets/js/sidebar.js"></script>
</body>
</html>