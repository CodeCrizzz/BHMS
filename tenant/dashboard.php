<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('tenant');

$user_id = $_SESSION['user_id'] ?? 0;

// --- GET USER & ROOM DATA ---
$sql = "SELECT users.*, rooms.price as rent_amount 
        FROM users 
        LEFT JOIN rooms ON users.room_assigned = rooms.room_no 
        WHERE users.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// --- GET UNPAID BILLS ---
$stmt_bill = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE tenant_id = ? AND status = 'pending'");
$stmt_bill->bind_param("i", $user_id);
$stmt_bill->execute();
$pending_bill = $stmt_bill->get_result()->fetch_assoc()['total'] ?? 0.00;

// --- GET RECENT ACTIVITY (Merged) ---
$activity_query = "
    (SELECT 'Payment' as type, description, amount, date_created, status FROM payments WHERE tenant_id = $user_id)
    UNION
    (SELECT 'Request' as type, request_type as description, 0 as amount, date_created, status FROM requests WHERE tenant_id = $user_id)
    ORDER BY date_created DESC LIMIT 15";
$activities = $conn->query($activity_query);

$img_path = "../assets/uploads/" . ($user['profile_image'] ? $user['profile_image'] : 'default.png');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Dashboard | BHMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .custom-scroll::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .hover-bg-light:hover {
            background-color: #f8fafc;
        }

        .transition-all {
            transition: all 0.2s ease;
        }
    </style>
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

        <div class="flex-grow-1 p-4 d-flex flex-column" style="overflow-y: auto;">
            <div class="mb-4">
                <h2 class="fw-bold m-0">Welcome back, <?php echo explode(' ', trim($user['fullname']))[0]; ?>!</h2>
                <p class="text-muted small">Here is what's happening with your stay.</p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-4 text-white" style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); border-radius: 20px;">
                        <small class="opacity-75 fw-bold">ROOM ASSIGNED</small>
                        <h2 class="fw-bold m-0"><?php echo $user['room_assigned'] ?? 'N/A'; ?></h2>
                        <i class="fa fa-door-open position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-4 text-white" style="background: linear-gradient(135deg, #f43f5e 0%, #fb923c 100%); border-radius: 20px;">
                        <small class="opacity-75 fw-bold">PENDING BILL</small>
                        <h2 class="fw-bold m-0">₱<?php echo number_format($pending_bill, 2); ?></h2>
                        <i class="fa fa-wallet position-absolute end-0 bottom-0 m-3 opacity-25 fa-2x"></i>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-4 bg-white" style="border-radius: 20px;">
                        <small class="text-muted fw-bold">MONTHLY RENT</small>
                        <h2 class="fw-bold m-0 text-dark">₱<?php echo number_format($user['rent_amount'] ?? 0, 2); ?></h2>
                        <i class="fa fa-receipt position-absolute end-0 bottom-0 m-3 text-primary opacity-25 fa-2x"></i>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm" style="border-radius: 20px;">
                        <div class="card-header bg-white border-0 py-3 px-4">
                            <h6 class="fw-bold m-0">Recent Activity</h6>
                        </div>
                        <div class="card-body p-0 custom-scroll" style="max-height: 400px; overflow-y: auto;">
                            <?php if ($activities->num_rows > 0): ?>
                                <?php while ($row = $activities->fetch_assoc()): ?>
                                    <div class="d-flex align-items-center p-3 border-bottom hover-bg-light transition-all">
                                        <div class="rounded-circle bg-light p-3 me-3 d-flex justify-content-center align-items-center" style="width: 45px; height: 45px;">
                                            <i class="fa <?php echo ($row['type'] == 'Payment') ? 'fa-credit-card text-success' : 'fa-tools text-warning'; ?>"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-bold small"><?php echo htmlspecialchars($row['description']); ?></span>
                                                <span class="text-muted" style="font-size: 0.7rem;"><?php echo date('M d', strtotime($row['date_created'])); ?></span>
                                            </div>
                                            <span class="text-muted small"><?php echo $row['type']; ?> • <?php echo $row['status']; ?></span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="p-4 text-center text-muted">No recent activity.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm bg-dark text-white p-4" style="border-radius: 20px; background: #1e293b !important;">
                        <h6 class="fw-bold mb-3"><i class="fa fa-bullhorn me-2 text-warning"></i>House Rules</h6>
                        <ul class="list-unstyled small opacity-75">
                            <li class="mb-2">• Curfew: 10:00 PM</li>
                            <li class="mb-2">• Segregate your trash</li>
                            <li class="mb-2">• No overnight guests</li>
                        </ul>
                        <a href="requests.php" class="btn btn-primary w-100 mt-3 rounded-pill">Create Request</a>
                    </div>
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