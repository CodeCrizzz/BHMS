<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('tenant');

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

if (isset($_POST['submit_request'])) {
    $type = $_POST['request_type'];
    $desc = $_POST['description'];

    $stmt = $conn->prepare("INSERT INTO requests (tenant_id, request_type, description) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $type, $desc);

    if ($stmt->execute()) {
        $msg = "Request submitted successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error submitting request.";
        $msg_type = "danger";
    }
}

$sql = "SELECT users.*, rooms.price as rent_amount FROM users LEFT JOIN rooms ON users.room_assigned = rooms.room_no WHERE users.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$img_path = "../assets/uploads/" . ($user['profile_image'] ? $user['profile_image'] : 'default.png');
if (!file_exists($img_path)) $img_path = "https://via.placeholder.com/150?text=No+Image";

$sql_debt = "SELECT SUM(amount) as debt FROM payments WHERE tenant_id = ? AND status = 'pending'";
$stmt_d = $conn->prepare($sql_debt);
$stmt_d->bind_param("i", $user_id);
$stmt_d->execute();
$debt = $stmt_d->get_result()->fetch_assoc()['debt'];
$debt = $debt ? $debt : 0;

$announcements = $conn->query("SELECT * FROM announcements ORDER BY date_posted DESC LIMIT 3");

$sql_req = "SELECT * FROM requests WHERE tenant_id = ? ORDER BY date_created DESC LIMIT 5";
$stmt_r = $conn->prepare($sql_req);
$stmt_r->bind_param("i", $user_id);
$stmt_r->execute();
$my_requests = $stmt_r->get_result();

// --- GET UNPAID BILLS COUNT ---
$stmt_pending = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE tenant_id = ? AND status = 'pending'");
$stmt_pending->bind_param("i", $my_id);
$stmt_pending->execute();
$pending_total = $stmt_pending->get_result()->fetch_assoc()['total'];
$pending_total = $pending_total ? $pending_total : 0.00;

// --- NEW: GET UNREAD MESSAGE COUNT ---
$unread_query = $conn->query("SELECT COUNT(id) AS unread FROM messages WHERE receiver_id = $user_id AND is_read = 0");
$unread_count = 0;
if ($unread_query) {
    $unread_data = $unread_query->fetch_assoc();
    $unread_count = $unread_data['unread'];
}
// -------------------------------------
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Dashboard | Tenant</title>
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
            <a href="dashboard.php" class="active"><i class="fa fa-home me-2"></i> Dashboard</a>
            <a href="profile.php"><i class="fa fa-user me-2"></i> My Profile</a>
            <a href="payments.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'payments.php') ? 'active' : ''; ?>">
                <span><i class="fa fa-credit-card me-2"></i> Billing</span>
                <?php if ($pending_total > 0): ?>
                    <i class="fa fa-bell text-warning shadow-sm" style="animation: pulse-red 2s infinite;" title="You have unpaid bills"></i>
                <?php endif; ?>
            </a>
            <a href="talk.php" class="d-flex justify-content-between align-items-center">
                <span><i class="fa fa-comments me-2"></i> Chat Admin</span>
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger rounded-pill shadow-sm" style="font-size: 0.75rem; padding: 0.35em 0.65em;"><?php echo $unread_count; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <div class="flex-grow-1 p-4 bg-light" style="overflow-y: auto;">

            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show">
                    <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card card-custom h-100 border-0 shadow-sm">
                        <div class="card-body d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <img src="<?php echo $img_path; ?>" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover; border: 2px solid #e2e8f0;">
                            </div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <h6 class="mb-0 fw-bold text-truncate" title="<?php echo $user['fullname']; ?>">
                                    <?php echo $user['fullname']; ?>
                                </h6>
                                <div class="small text-muted text-truncate" title="<?php echo $user['email']; ?>">
                                    <?php echo $user['email']; ?>
                                </div>
                                <a href="profile.php" class="small text-primary-custom text-decoration-none mt-1 d-inline-block">
                                    View Full Profile &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card card-custom h-100 bg-primary-custom text-white border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1">Room Info</h6>
                                    <h3 class="fw-bold"><?php echo $user['room_assigned'] ? $user['room_assigned'] : 'None'; ?></h3>
                                </div>
                                <i class="fa fa-bed fa-2x opacity-50"></i>
                            </div>
                            <small class="opacity-75">Monthly Rent: Php. <?php echo number_format($user['rent_amount'] ?? 0, 2); ?></small>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card card-custom h-100 border-0 shadow-sm <?php echo ($debt > 0) ? 'bg-danger text-white' : 'bg-success text-white'; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="mb-1">Current Balance</h6>
                                    <h3 class="fw-bold">Php. <?php echo number_format($debt, 2); ?></h3>
                                </div>
                                <i class="fa fa-wallet fa-2x opacity-50"></i>
                            </div>
                            <small class="opacity-75"><?php echo ($debt > 0) ? 'Please pay soon.' : 'You are fully paid!'; ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-7 mb-3">
                    <div class="card card-custom border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom-0 pt-3 ps-3">
                            <h5 class="fw-bold text-primary-custom"><i class="fa fa-bullhorn me-2"></i> Announcements</h5>
                        </div>
                        <div class="card-body">

                            <?php
                            $reg_date = strtotime($user['created_at']);
                            $joined_date = date('M d, Y', $reg_date);
                            $due_day = date('jS', $reg_date);
                            ?>

                            <div class="alert alert-info border-0 bg-light-custom mb-3">
                                <div class="d-flex justify-content-between">
                                    <strong class="text-dark">Welcome to StudyStay Boarding House</strong>
                                    <small class="text-muted"><?php echo $joined_date; ?></small>
                                </div>
                                <p class="mb-0 small text-secondary mt-1">
                                    Rent is due on the <strong><?php echo $due_day; ?></strong> of every month. Please check your billing tab.
                                </p>
                            </div>

                            <?php if ($announcements->num_rows > 0): ?>
                                <?php while ($anno = $announcements->fetch_assoc()): ?>

                                    <?php if (stripos($anno['title'], 'Welcome') !== false) continue; ?>

                                    <div class="alert alert-info border-0 bg-light-custom mb-3">
                                        <div class="d-flex justify-content-between">
                                            <strong class="text-dark"><?php echo htmlspecialchars($anno['title']); ?></strong>
                                            <small class="text-muted"><?php echo date('M d', strtotime($anno['date_posted'])); ?></small>
                                        </div>
                                        <p class="mb-0 small text-secondary mt-1"><?php echo htmlspecialchars($anno['message']); ?></p>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <div class="col-md-5 mb-3">
                    <div class="card card-custom border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-bottom-0 pt-3 ps-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold text-primary-custom mb-0"><i class="fa fa-wrench me-2"></i> Requests</h5>
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#requestModal">
                                <i class="fa fa-plus"></i> New
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-3">Type</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($my_requests->num_rows > 0): ?>
                                            <?php while ($req = $my_requests->fetch_assoc()):
                                                $badge = 'bg-warning text-dark';
                                                if ($req['status'] == 'Resolved') $badge = 'bg-success';
                                                if ($req['status'] == 'In Progress') $badge = 'bg-info';
                                            ?>
                                                <tr>
                                                    <td class="ps-3 fw-bold small"><?php echo $req['request_type']; ?></td>
                                                    <td class="small"><?php echo date('M d', strtotime($req['date_created'])); ?></td>
                                                    <td><span class="badge <?php echo $badge; ?>"><?php echo $req['status']; ?></span></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-4 small">No requests found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary-custom text-white">
                    <h5 class="modal-title">Submit Maintenance/Request</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Request Type</label>
                            <select name="request_type" class="form-select">
                                <option value="Maintenance">Maintenance (Repair)</option>
                                <option value="Complaint">Complaint</option>
                                <option value="Cleaning">Cleaning Request</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Description (Details)</label>
                            <textarea name="description" class="form-control" rows="4" required placeholder="e.g. The faucet in the bathroom is leaking..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="submit_request" class="btn bg-primary-custom text-white">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/darkmode.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>