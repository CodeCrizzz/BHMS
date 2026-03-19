<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('tenant');

$my_id = $_SESSION['user_id'];

$stmt_pending = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE tenant_id = ? AND status = 'pending'");
$stmt_pending->bind_param("i", $my_id);
$stmt_pending->execute();
$pending_total = $stmt_pending->get_result()->fetch_assoc()['total'] ?? 0.00;

$stmt_paid = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE tenant_id = ? AND status = 'paid'");
$stmt_paid->bind_param("i", $my_id);
$stmt_paid->execute();
$paid_total = $stmt_paid->get_result()->fetch_assoc()['total'] ?? 0.00;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing | StudyStay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-light d-flex flex-column" style="height: 100vh; overflow: hidden; margin: 0;">
    <nav class="navbar navbar-expand-lg navbar-custom px-4 py-3 shadow-sm flex-shrink-0">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary d-lg-none" id="sidebarToggle"><i class="fa fa-bars"></i></button>
            <span class="fw-bold h5 mb-0 text-primary-custom"><i class="fa fa-building-user me-2"></i>StudyStay</span>
        </div>
        <div class="ms-auto d-flex align-items-center gap-3">
            <button id="darkModeToggle" class="btn btn-light rounded-circle shadow-sm"><i class="fa fa-moon"></i></button>
            <a href="../logout.php" class="btn btn-danger btn-sm px-3 rounded-pill shadow-sm">Logout</a>
        </div>
    </nav>

    <div class="d-flex flex-grow-1" style="overflow: hidden;">
        <div class="sidebar p-3 flex-shrink-0" style="width: 260px; overflow-y: auto;">
            <h4 class="text-center mb-4 mt-2 text-white">My Portal</h4>
            <a href="dashboard.php"><i class="fa fa-home me-2"></i> Dashboard</a>
            <a href="profile.php"><i class="fa fa-user me-2"></i> My Profile</a>
            <a href="payments.php" class="active"><i class="fa fa-credit-card me-2"></i> Billing</a>
            <a href="requests.php"><i class="fa fa-wrench me-2"></i> My Requests</a>
            <a href="talk.php"><i class="fa fa-comments me-2"></i> Chat Admin</a>
        </div>

        <div class="flex-grow-1 p-4 d-flex flex-column" style="overflow: hidden;">
            <div class="mb-4 flex-shrink-0">
                <h2 class="fw-bold text-dark m-0">Billing & Payments</h2>
                <p class="text-muted small">View your financial history and outstanding balances.</p>
            </div>

            <div class="row g-4 mb-4 flex-shrink-0">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 text-white" style="background: linear-gradient(135deg, #ee6c4d 0%, #d45d40 100%); border-radius: 20px;">
                        <p class="small fw-bold opacity-75 mb-1 text-uppercase">Current Outstanding</p>
                        <h1 class="fw-bold m-0">₱<?php echo number_format($pending_total, 2); ?></h1>
                        <hr class="opacity-25">
                        <small><i class="fa fa-circle-info me-1"></i> Please settle this to avoid late fees.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 20px;">
                        <p class="text-muted small fw-bold mb-1 text-uppercase">Lifetime Paid</p>
                        <h1 class="fw-bold m-0 text-success">₱<?php echo number_format($paid_total, 2); ?></h1>
                        <hr class="bg-light">
                        <small class="text-muted">Total payments since moving in.</small>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column" style="border-radius: 20px; overflow: hidden;">
                <div class="card-header bg-white py-3 px-4 border-bottom">
                    <h6 class="fw-bold m-0">Transaction History</h6>
                </div>
                <div class="card-body p-0 flex-grow-1" style="overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="ps-4">Invoice</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="pe-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = $conn->query("SELECT * FROM payments WHERE tenant_id = $my_id ORDER BY date_created DESC");
                            while ($row = $res->fetch_assoc()):
                            ?>
                                <tr>
                                    <td class="ps-4 text-muted small">#<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td class="fw-bold"><?php echo htmlspecialchars($row['description']); ?></td>
                                    <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo ($row['status'] == 'paid') ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'; ?> px-3">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-muted small"><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/darkmode.js"></script>
</body>

</html>