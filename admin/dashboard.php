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
    <div class="sidebar p-3 flex-shrink-0 d-flex flex-column gap-2" style="width: 250px; min-height: 100vh; overflow-y: auto;">
        <h4 class="text-center mb-4 mt-2 flex-shrink-0">System Admin</h4>
        <a href="dashboard.php" class="nav-dashboard active"><i class="fa fa-home me-2"></i> Dashboard</a>
        <a href="manage_tenants.php" class="nav-tenants"><i class="fa fa-users me-2"></i> Manage Tenants</a>
        <a href="manage_rooms.php" class="nav-rooms"><i class="fa fa-bed me-2"></i> Manage Rooms</a>
        <a href="billing.php" class="nav-billing"><i class="fa fa-file-invoice-dollar me-2"></i> Billing</a>
        <a href="manage_requests.php" class="nav-requests"><i class="fa fa-wrench me-2"></i> Manage Requests</a>
        <a href="talk.php" class="nav-talk"><i class="fa fa-comments me-2"></i> Chat Support</a>
        <a href="manage_admins.php" class="nav-admins"><i class="fa fa-user-shield me-2"></i> Manage Admins</a>
    </div>
    <div class="flex-grow-1 p-4" id="printableArea">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Dashboard Overview</h2>
            <button onclick="printDashboard()" class="btn btn-outline-dark"><i class="fa fa-file-pdf"></i> Export PDF</button>
        </div>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="card card-custom bg-primary-custom text-white p-3 h-100 shadow-sm border-0">
                    <h5>Total Tenants</h5>
                    <h2><?php echo $total_tenants; ?></h2>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card card-custom bg-info text-white p-3 h-100 shadow-sm border-0">
                    <h5>Occupied Rooms</h5>
                    <h2><?php echo $occupied_rooms; ?></h2>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card card-custom bg-success text-white p-3 h-100 shadow-sm border-0">
                    <h5>Total Revenue</h5>
                    <h2>Php. <?php echo number_format($total_revenue, 2); ?></h2>
                </div>
            </div>
        </div>
        <div class="mt-5">
            <h4 class="mb-3 text-primary-custom fw-bold"><i class="fa fa-clock-rotate-left me-2"></i>Recent Payments</h4>
            <div class="card card-custom border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3 text-uppercase text-muted" style="font-size: 0.8rem; letter-spacing: 0.5px;">Date</th>
                                    <th class="py-3 text-uppercase text-muted" style="font-size: 0.8rem; letter-spacing: 0.5px;">Tenant Name</th>
                                    <th class="py-3 text-uppercase text-muted" style="font-size: 0.8rem; letter-spacing: 0.5px;">Description</th>
                                    <th class="py-3 text-uppercase text-muted" style="font-size: 0.8rem; letter-spacing: 0.5px;">Amount</th>
                                    <th class="py-3 text-uppercase text-muted" style="font-size: 0.8rem; letter-spacing: 0.5px;">Status</th>
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
                                        $icon = ($row['status'] == 'paid') ? '<i class="fa fa-check-circle me-1"></i>' : '<i class="fa fa-clock me-1"></i>';
                                ?>
                                <tr>
                                    <td class="ps-4 text-secondary small">
                                        <i class="fa fa-calendar-alt me-2 opacity-50"></i>
                                        <?php echo date('M d, Y', strtotime($row['date_created'])); ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['fullname']); ?></div>
                                    </td>
                                    <td class="text-secondary"><?php echo htmlspecialchars($row['description']); ?></td>
                                    
                                    <td class="fw-bold text-primary-custom">
                                        Php <?php echo number_format($row['amount'], 2); ?>
                                    </td>
                                    
                                    <td>
                                        <span class="badge <?php echo $badge; ?> px-3 py-2 rounded-pill shadow-sm" style="font-size: 0.75rem;">
                                            <?php echo $icon . ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center py-5 text-muted'><i class='fa fa-folder-open fa-2x mb-2 opacity-25 d-block'></i>No recent activities found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/darkmode.js"></script>
<script src="../assets/js/script.js"></script>
<script src="../assets/js/sidebar.js"></script>
</body>
</html>