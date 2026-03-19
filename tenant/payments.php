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
    <nav class="navbar navbar-expand-lg navbar-custom px-3 py-3 shadow-sm d-flex justify-content-between flex-nowrap flex-shrink-0" style="z-index: 1000;">
        <div class="d-flex align-items-center gap-2" style="min-width: 0;">
            <button class="btn btn-outline-secondary d-lg-none flex-shrink-0" id="sidebarToggle"><i class="fa fa-bars"></i></button>
            <div class="navbar-brand-custom fw-bold text-truncate"><i class="fa fa-building me-2"></i> StudyStay Boarding House</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <button id="darkModeToggle" class="btn btn-outline-secondary rounded-circle" style="width: 38px; height: 38px; padding: 0; display: flex; align-items: center; justify-content: center;"><i class="fa fa-moon"></i></button>
            <a href="../logout.php" class="btn btn-danger btn-sm d-flex align-items-center" style="height: 36px; white-space: nowrap;"><i class="fa fa-sign-out-alt me-1"></i> <span class="d-none d-sm-inline">Logout</span></a>
        </div>
    </nav>

    <div class="d-flex flex-grow-1" style="overflow: hidden;">
        <div class="sidebar p-3 flex-shrink-0" style="width: 250px; overflow-y: auto;">
            <h4 class="text-center mb-4 mt-2">My Portal</h4>
            <a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa fa-home me-2"></i> Dashboard</a>
            <a href="profile.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>"><i class="fa fa-user me-2"></i> My Profile</a>
            <a href="payments.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'payments.php') ? 'active' : ''; ?>"><span><i class="fa fa-credit-card me-2"></i> Billing</span><span id="sidebar-bell-container"></span></a>
            <a href="requests.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'requests.php') ? 'active' : ''; ?>">
                <span><i class="fa fa-wrench me-2"></i> My Requests</span>
                <?php
                $sidebar_req_query = $conn->query("SELECT COUNT(id) AS total FROM requests WHERE tenant_id = $my_id AND status IN ('In Progress', 'Resolved')");
                $sidebar_req_count = $sidebar_req_query ? $sidebar_req_query->fetch_assoc()['total'] : 0;
                if ($sidebar_req_count > 0):
                ?>
                    <span class="badge bg-warning text-dark rounded-pill shadow-sm" style="font-size: 0.7rem; padding: 4px 8px;"><?php echo $sidebar_req_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="talk.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'talk.php') ? 'active' : ''; ?>"><span><i class="fa fa-comments me-2"></i> Chat Admin</span><span id="sidebar-chat-container"></span></a>
        </div>

        <div class="flex-grow-1 p-4 d-flex flex-column" style="overflow-y: auto;">
            <h2 class="fw-bold mb-4">Billing Statement</h2>

            <div class="card border-0 shadow-sm mb-4 flex-shrink-0" style="border-radius: 20px; background: linear-gradient(to right, #ffffff, #f8fafc);">
                <div class="card-body p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1 fw-bold text-uppercase">Total Outstanding Amount</p>
                        <h1 class="fw-bold text-danger m-0">₱<?php echo number_format($pending_total, 2); ?></h1>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-success-subtle text-success p-2 px-3 rounded-pill" style="font-size: 0.85rem;">
                            <i class="fa fa-check-circle me-1"></i> Paid to date: ₱<?php echo number_format($paid_total, 2); ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm" style="border-radius: 20px; overflow: hidden;">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 ps-4 py-3 text-muted small text-uppercase">Invoice Details</th>
                                <th class="border-0 py-3 text-muted small text-uppercase">Amount</th>
                                <th class="border-0 py-3 text-muted small text-uppercase">Status</th>
                                <th class="border-0 pe-4 py-3 text-muted small text-uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = $conn->query("SELECT * FROM payments WHERE tenant_id = $my_id ORDER BY date_created DESC");
                            while ($row = $res->fetch_assoc()):
                            ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-bold"><?php echo htmlspecialchars($row['description']); ?></div>
                                        <small class="text-muted">Inv #SS-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></small>
                                    </td>
                                    <td class="fw-bold text-dark">₱<?php echo number_format($row['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo ($row['status'] == 'paid') ? 'bg-success text-white' : 'bg-warning text-dark'; ?> px-3">
                                            <?php echo strtoupper($row['status']); ?>
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
    <script src="../assets/js/get_notification.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/darkmode.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>