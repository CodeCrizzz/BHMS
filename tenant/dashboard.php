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
        :root {
            --glass: rgba(255, 255, 255, 0.95);
            --grad: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Modern Card Styling */
        .glass-card {
            background: var(--glass);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.25rem;
        }

        .activity-row {
            border-left: 3px solid transparent;
            transition: all 0.2s;
            cursor: default;
        }

        .activity-row:hover {
            background-color: #f8fafc;
            border-left-color: var(--primary);
        }

        /* Modern Scrollbar for activity */
        .custom-scroll::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
    </style>
</head>

<body class="bg-light d-flex flex-column" style="height: 100vh; overflow: hidden; margin: 0;">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom px-4 py-3 shadow-sm flex-shrink-0" style="background: var(--white); z-index: 1000;">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary d-lg-none" id="sidebarToggle"><i class="fa fa-bars"></i></button>
            <span class="fw-bold h5 mb-0 text-primary-custom"><i class="fa fa-building-user me-2"></i>StudyStay</span>
        </div>
        <div class="ms-auto d-flex align-items-center gap-3">
            <button id="darkModeToggle" class="btn btn-light rounded-circle shadow-sm"><i class="fa fa-moon"></i></button>
            <div class="vr mx-2 text-muted opacity-25"></div>
            <a href="../logout.php" class="btn btn-danger btn-sm px-3 rounded-pill shadow-sm">Logout</a>
        </div>
    </nav>

    <div class="d-flex flex-grow-1" style="overflow: hidden;">

        <!-- Sidebar -->
        <div class="sidebar p-3 flex-shrink-0" style="width: 260px; background: var(--sidebar-bg); overflow-y: auto;">
            <div class="text-center mb-4 pt-3">
                <img src="<?php echo $img_path; ?>" class="rounded-circle border border-3 border-primary shadow-sm" style="width: 80px; height: 80px; object-fit: cover;">
                <h6 class="text-white mt-3 fw-bold mb-0"><?php echo htmlspecialchars($user['fullname']); ?></h6>
                <span class="badge bg-primary-custom mt-1" style="font-size: 0.65rem;">TENANT</span>
            </div>

            <a href="dashboard.php" class="active"><i class="fa fa-home me-2"></i> Dashboard</a>
            <a href="profile.php"><i class="fa fa-user me-2"></i> My Profile</a>
            <a href="payments.php" class="d-flex justify-content-between align-items-center">
                <span><i class="fa fa-credit-card me-2"></i> Billing</span>
                <span id="sidebar-bell-container"></span>
            </a>
            <a href="requests.php" class="d-flex justify-content-between align-items-center">
                <span><i class="fa fa-wrench me-2"></i> My Requests</span>
                <?php
                $safe_tenant_id = $_SESSION['user_id'] ?? 0;
                $sidebar_req_query = $conn->query("SELECT COUNT(id) AS total FROM requests WHERE tenant_id = $safe_tenant_id AND status IN ('In Progress', 'Resolved')");
                $sidebar_req_count = $sidebar_req_query ? $sidebar_req_query->fetch_assoc()['total'] : 0;
                if ($sidebar_req_count > 0):
                ?>
                    <span class="badge bg-warning text-dark rounded-pill"><?php echo $sidebar_req_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="talk.php" class="d-flex justify-content-between align-items-center">
                <span><i class="fa fa-comments me-2"></i> Chat Admin</span>
                <span id="sidebar-chat-container"></span>
            </a>
        </div>

        <!-- Dashboard Content -->
        <div class="flex-grow-1 p-4 d-flex flex-column" style="overflow: hidden;">

            <!-- Header Row -->
            <div class="d-flex justify-content-between align-items-end mb-4 flex-shrink-0">
                <div>
                    <h2 class="fw-bold text-dark m-0">Dashboard</h2>
                    <p class="text-muted small mb-0">Welcome back, Christian. Here's your summary.</p>
                </div>
                <div class="text-end">
                    <span class="text-muted small d-block">Monthly Rent Date</span>
                    <span class="fw-bold text-primary-custom">Due in 12 days</span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="row g-3 mb-4 flex-shrink-0">
                <div class="col-md-4">
                    <div class="glass-card shadow-sm p-4 h-100">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="text-muted small fw-bold mb-1">UNIT ASSIGNED</p>
                                <h3 class="fw-bold m-0"><?php echo $user['room_assigned'] ?? 'N/A'; ?></h3>
                            </div>
                            <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa fa-door-open"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card shadow-sm p-4 h-100">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="text-muted small fw-bold mb-1">TOTAL BALANCE</p>
                                <h3 class="fw-bold m-0 text-danger">₱<?php echo number_format($pending_bill, 2); ?></h3>
                            </div>
                            <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                                <i class="fa fa-wallet"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card shadow-sm p-4 h-100">
                        <div class="d-flex justify-content-between">
                            <div>
                                <p class="text-muted small fw-bold mb-1">MONTHLY RATE</p>
                                <h3 class="fw-bold m-0">₱<?php echo number_format($user['rent_amount'] ?? 0, 2); ?></h3>
                            </div>
                            <div class="stat-icon bg-success bg-opacity-10 text-success">
                                <i class="fa fa-receipt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Split Panel -->
            <div class="row g-4 flex-grow-1" style="overflow: hidden;">

                <!-- Recent Activity Panel -->
                <div class="col-lg-8 d-flex flex-column h-100">
                    <div class="glass-card shadow-sm flex-grow-1 d-flex flex-column" style="overflow: hidden;">
                        <div class="card-header bg-transparent py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold m-0 text-dark">Recent Transactions & Requests</h6>
                            <a href="requests.php" class="btn btn-sm btn-primary-custom rounded-pill px-3">New Request</a>
                        </div>
                        <div class="card-body p-0 flex-grow-1 custom-scroll" style="overflow-y: auto;">
                            <?php if ($activities->num_rows > 0): ?>
                                <?php while ($row = $activities->fetch_assoc()): ?>
                                    <div class="activity-row p-3 border-bottom d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 40px; height: 40px;">
                                            <i class="fa <?php echo ($row['type'] == 'Payment') ? 'fa-arrow-up text-info' : 'fa-wrench text-warning'; ?>" style="font-size: 0.85rem;"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <span class="fw-bold text-dark small"><?php echo htmlspecialchars($row['description']); ?></span>
                                                <span class="text-muted" style="font-size: 0.7rem;"><?php echo date('M d, Y', strtotime($row['date_created'])); ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <span class="text-muted small"><?php echo $row['type']; ?></span>
                                                <span class="badge rounded-pill <?php echo ($row['status'] == 'paid' || $row['status'] == 'Resolved') ? 'bg-success' : 'bg-warning text-dark'; ?>" style="font-size: 0.6rem;">
                                                    <?php echo strtoupper($row['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fa fa-history fa-3x mb-3 opacity-25"></i>
                                    <p>No activity recorded yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Announcement Panel -->
                <div class="col-lg-4 d-flex flex-column h-100">
                    <div class="glass-card shadow-sm flex-grow-1 p-4 bg-primary text-white" style="background: var(--grad) !important;">
                        <h5 class="fw-bold mb-3"><i class="fa fa-bullhorn me-2"></i>Notices</h5>

                        <div class="bg-white bg-opacity-10 p-3 rounded-3 mb-3 border border-white border-opacity-10">
                            <p class="small mb-1 fw-bold">General Policy</p>
                            <p class="small mb-0 opacity-75">Rent collection is strictly on the 1st of every month. Please settle balances to avoid penalties.</p>
                        </div>

                        <div class="bg-white bg-opacity-10 p-3 rounded-3 border border-white border-opacity-10">
                            <p class="small mb-1 fw-bold">Maintenance Notice</p>
                            <p class="small mb-0 opacity-75">Water supply will be temporarily shut down this Sunday from 9AM to 11AM for tank cleaning.</p>
                        </div>

                        <div class="mt-auto pt-4 text-center">
                            <i class="fa fa-shield-halved fa-4x opacity-25"></i>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/get_notification.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/darkmode.js"></script>
</body>

</html>