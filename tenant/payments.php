<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('tenant');

$my_id = $_SESSION['user_id'];

$stmt_pending = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE tenant_id = ? AND status = 'pending'");
$stmt_pending->bind_param("i", $my_id);
$stmt_pending->execute();
$pending_total = $stmt_pending->get_result()->fetch_assoc()['total'];
$pending_total = $pending_total ? $pending_total : 0.00;

$stmt_paid = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE tenant_id = ? AND status = 'paid'");
$stmt_paid->bind_param("i", $my_id);
$stmt_paid->execute();
$paid_total = $stmt_paid->get_result()->fetch_assoc()['total'];
$paid_total = $paid_total ? $paid_total : 0.00;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>My Payments | Tenant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light d-flex flex-column h-100" style="overflow: hidden;">
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

    <div class="d-flex flex-grow-1" style="overflow: hidden;">
        
        <div class="sidebar p-3" style="width: 250px; overflow-y: auto;">
            <h4 class="text-center mb-4 mt-2">My Portal</h4>
            <a href="dashboard.php"><i class="fa fa-home me-2"></i> Dashboard</a>
            <a href="profile.php"><i class="fa fa-user me-2"></i> My Profile</a>
            <a href="payments.php" class="active"><i class="fa fa-credit-card me-2"></i> Billing</a>
            <a href="talk.php"><i class="fa fa-comments me-2"></i> Chat Admin</a>
        </div>

        <div class="flex-grow-1 p-4 bg-light" style="overflow-y: auto;">
            
            <h2 class="mb-4 text-primary-custom">Billing & Payment History</h2>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card card-custom p-4 text-white border-0" style="background-color: #ee6c4d;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5>Total Due (Unpaid)</h5>
                                <h2 class="fw-bold">Php. <?php echo number_format($pending_total, 2); ?></h2>
                            </div>
                            <i class="fa fa-exclamation-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card card-custom p-4 bg-success text-white border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h5>Total Paid Lifetime</h5>
                                <h2 class="fw-bold">Php. <?php echo number_format($paid_total, 2); ?></h2>
                            </div>
                            <i class="fa fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-custom p-3 border-0 shadow-sm">
                <h5 class="mb-3">Transaction History</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Invoice ID</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM payments WHERE tenant_id = ? ORDER BY date_created DESC";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $my_id);
                            $stmt->execute();
                            $result = $stmt->get_result();

                            if($result->num_rows > 0){
                                while($row = $result->fetch_assoc()){
                                    $is_paid = ($row['status'] == 'paid');
                                    $badge = $is_paid ? 'bg-success' : 'bg-warning text-dark';
                                    $icon  = $is_paid ? 'fa-check' : 'fa-clock';
                            ?>
                            <tr>
                                <td class="text-secondary">#INV-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo $row['date_created']; ?></td>
                                <td class="fw-bold"><?php echo $row['description']; ?></td>
                                <td>Php. <?php echo number_format($row['amount'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $badge; ?>">
                                        <i class="fa <?php echo $icon; ?> me-1"></i>
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center text-muted py-4'>No payment records found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div> 
    </div> 
<script src="../assets/js/sidebar.js"></script>
<script src="../assets/js/darkmode.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>