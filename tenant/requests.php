<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('tenant');

$user_id = $_SESSION['user_id'] ?? 0;
$msg = "";
$msg_type = "";

if (isset($_POST['submit_request'])) {
    $type = $_POST['request_type'];
    $desc = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO requests (tenant_id, request_type, description, status) VALUES (?, ?, ?, 'Pending')");
    $stmt->bind_param("iss", $user_id, $type, $desc);

    if ($stmt->execute()) {
        $msg = "Request submitted successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error submitting request.";
        $msg_type = "danger";
    }
}

$requests_sql = "SELECT * FROM requests WHERE tenant_id = ? ORDER BY date_created DESC";
$stmt_req = $conn->prepare($requests_sql);
$stmt_req->bind_param("i", $user_id);
$stmt_req->execute();
$requests_result = $stmt_req->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>My Requests | Tenant</title>
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
                $sidebar_req_query = $conn->query("SELECT COUNT(id) AS total FROM requests WHERE tenant_id = $user_id AND status IN ('In Progress', 'Resolved')");
                $sidebar_req_count = $sidebar_req_query ? $sidebar_req_query->fetch_assoc()['total'] : 0;
                if ($sidebar_req_count > 0):
                ?>
                    <span class="badge bg-warning text-dark rounded-pill shadow-sm" style="font-size: 0.7rem; padding: 4px 8px;"><?php echo $sidebar_req_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="talk.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'talk.php') ? 'active' : ''; ?>"><span><i class="fa fa-comments me-2"></i> Chat Admin</span><span id="sidebar-chat-container"></span></a>
        </div>

        <div class="flex-grow-1 p-4" style="overflow-y: auto;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0">Support & Maintenance</h2>
                <button class="btn btn-dark rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#newRequestModal">+ New Request</button>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> rounded-4 shadow-sm border-0"><?php echo $msg; ?></div>
            <?php endif; ?>

            <div class="row">
                <?php if ($requests_result->num_rows > 0): ?>
                    <?php while ($req = $requests_result->fetch_assoc()):
                        $status = $req['status'];
                        $borderColor = ($status == 'Pending') ? '#f59e0b' : (($status == 'In Progress') ? '#3b82f6' : '#10b981');
                        $iconColor = ($status == 'Pending') ? 'text-warning' : (($status == 'In Progress') ? 'text-primary' : 'text-success');
                    ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card border-0 shadow-sm p-4 h-100" style="border-radius: 20px; border-top: 6px solid <?php echo $borderColor; ?> !important;">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-light text-dark border px-3 rounded-pill"><?php echo $req['request_type']; ?></span>
                                    <small class="text-muted"><i class="fa fa-calendar-day me-1"></i><?php echo date('M d', strtotime($req['date_created'])); ?></small>
                                </div>
                                <h6 class="fw-bold mb-3 flex-grow-1"><?php echo htmlspecialchars($req['description']); ?></h6>
                                <div class="mt-auto pt-3 border-top d-flex align-items-center">
                                    <i class="fa <?php echo ($status == 'Pending') ? 'fa-clock' : (($status == 'In Progress') ? 'fa-spinner fa-spin' : 'fa-check-circle'); ?> <?php echo $iconColor; ?> me-2"></i>
                                    <span class="small fw-bold text-uppercase text-muted"><?php echo $status; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="fa fa-clipboard-check fa-4x mb-3 opacity-25"></i>
                        <p>No maintenance requests submitted.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="modal fade" id="newRequestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0" style="border-radius: 20px;">
                <div class="modal-header border-0 bg-light rounded-top-4">
                    <h5 class="modal-title fw-bold">Create Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Category</label>
                            <select name="request_type" class="form-select bg-light border-0" required>
                                <option value="Maintenance">Maintenance / Repair</option>
                                <option value="Cleaning">Cleaning Services</option>
                                <option value="Complaint">Noise / Complaint</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted text-uppercase">Description</label>
                            <textarea name="description" class="form-control bg-light border-0" rows="5" placeholder="Please describe the issue..." required style="resize: none;"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="submit" name="submit_request" class="btn btn-dark rounded-pill px-4 w-100">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/darkmode.js"></script>
</body>

</html>