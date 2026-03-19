<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('tenant');

// Safely get user ID
$user_id = $_SESSION['user_id'] ?? 0;
$msg = "";
$msg_type = "";

// --- 1. HANDLE NEW REQUEST SUBMISSION ---
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

// --- 2. FETCH EXISTING REQUESTS & CALCULATE COUNTS ---
$requests_sql = "SELECT * FROM requests WHERE tenant_id = ? ORDER BY date_created DESC";
$stmt_req = $conn->prepare($requests_sql);
$stmt_req->bind_param("i", $user_id);
$stmt_req->execute();
$requests_result = $stmt_req->get_result();

$requests_data = [];
$count_pending = 0;
$count_inprogress = 0;
$count_resolved = 0;
$total_requests = 0;

while ($row = $requests_result->fetch_assoc()) {
    $requests_data[] = $row;
    $total_requests++;

    if ($row['status'] == 'Pending') {
        $count_pending++;
    } elseif ($row['status'] == 'In Progress') {
        $count_inprogress++;
    } elseif ($row['status'] == 'Resolved') {
        $count_resolved++;
    }
}
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
    <style>
        .stat-card {
            border-left: 4px solid;
        }

        .border-warning-custom {
            border-left-color: #ffc107 !important;
        }

        .border-info-custom {
            border-left-color: #0dcaf0 !important;
        }

        .border-success-custom {
            border-left-color: #198754 !important;
        }

        /* Table layout fix for no-scroll */
        .table-fixed {
            table-layout: fixed;
            width: 100%;
        }

        .table-fixed td {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>

<!-- STRICT 100VH NO-SCROLL BODY -->

<body class="bg-light d-flex flex-column" style="height: 100vh; width: 100vw; overflow: hidden; margin: 0;">

    <!-- NAVBAR (Fixed Height) -->
    <nav class="navbar navbar-expand-lg navbar-custom px-3 py-3 shadow-sm d-flex justify-content-between flex-nowrap flex-shrink-0" style="z-index: 1000;">
        <div class="d-flex align-items-center gap-2" style="min-width: 0;">
            <button class="btn btn-outline-secondary d-lg-none flex-shrink-0" id="sidebarToggle">
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

    <!-- MAIN WRAPPER (Takes remaining height exactly, no overflow) -->
    <div class="d-flex flex-grow-1" style="overflow: hidden;">

        <!-- SIDEBAR (No Scroll) -->
        <div class="sidebar p-3 flex-shrink-0" style="width: 250px; overflow: hidden;">
            <h4 class="text-center mb-4 mt-2">My Portal</h4>
            <a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fa fa-home me-2"></i> Dashboard
            </a>
            <a href="profile.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
                <i class="fa fa-user me-2"></i> My Profile
            </a>
            <a href="payments.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'payments.php') ? 'active' : ''; ?>">
                <span><i class="fa fa-credit-card me-2"></i> Billing</span>
                <span id="sidebar-bell-container"></span>
            </a>

            <!-- Updated Sidebar Request Link with Counter -->
            <a href="requests.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'requests.php') ? 'active' : ''; ?>">
                <span><i class="fa fa-wrench me-2"></i> My Requests</span>
                <?php
                $sidebar_req_query = $conn->query("SELECT COUNT(id) AS total FROM requests WHERE tenant_id = $user_id AND status IN ('In Progress', 'Resolved')");
                $sidebar_req_count = $sidebar_req_query ? $sidebar_req_query->fetch_assoc()['total'] : 0;
                if ($sidebar_req_count > 0):
                ?>
                    <span class="badge bg-warning text-dark rounded-pill shadow-sm" style="font-size: 0.7rem; padding: 4px 8px;">
                        <?php echo $sidebar_req_count; ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="talk.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'talk.php') ? 'active' : ''; ?>">
                <span><i class="fa fa-comments me-2"></i> Chat Admin</span>
                <span id="sidebar-chat-container"></span>
            </a>
        </div>

        <!-- CONTENT AREA (Flex Column, No Scroll) -->
        <div class="flex-grow-1 p-4 bg-light d-flex flex-column" style="overflow: hidden;">

            <div class="mb-3 flex-shrink-0">
                <h3 class="fw-bold text-primary-custom m-0"><i class="fa fa-tools me-2"></i>Service Requests</h3>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show shadow-sm border-0 flex-shrink-0 py-2 mb-3">
                    <i class="fa <?php echo ($msg_type == 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
                    <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" style="padding: 0.8rem 1rem;"></button>
                </div>
            <?php endif; ?>

            <!-- ROW WRAPPER (Takes remaining height exactly) -->
            <div class="row g-4 flex-grow-1" style="overflow: hidden;">

                <!-- LEFT COLUMN: FORM -->
                <div class="col-lg-4 h-100">
                    <div class="card card-custom border-0 shadow-sm h-100 d-flex flex-column" style="overflow: hidden;">
                        <div class="card-header bg-white border-bottom-0 pt-3 px-4 flex-shrink-0">
                            <h5 class="fw-bold m-0 text-dark">New Request</h5>
                        </div>
                        <div class="card-body p-4 pt-2 flex-grow-1" style="overflow: hidden;">
                            <form method="POST" class="h-100 d-flex flex-column">
                                <div class="mb-3 flex-shrink-0">
                                    <label class="form-label text-muted small fw-bold mb-1">CATEGORY</label>
                                    <select name="request_type" class="form-select bg-light" required>
                                        <option value="Maintenance">Maintenance / Repair</option>
                                        <option value="Cleaning">Cleaning Services</option>
                                        <option value="Complaint">Noise / Complaint</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="mb-3 flex-grow-1 d-flex flex-column">
                                    <label class="form-label text-muted small fw-bold mb-1">DESCRIPTION</label>
                                    <textarea name="description" class="form-control bg-light flex-grow-1" placeholder="Details..." required style="resize: none;"></textarea>
                                </div>
                                <button type="submit" name="submit_request" class="btn bg-primary-custom text-white w-100 py-2 fw-bold flex-shrink-0">
                                    <i class="fa fa-paper-plane me-2"></i> Submit
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: STATS & TABLE -->
                <div class="col-lg-8 h-100 d-flex flex-column" style="overflow: hidden;">

                    <!-- Counters Row (Fixed Height) -->
                    <div class="row g-3 mb-3 flex-shrink-0">
                        <div class="col-md-4">
                            <div class="card bg-white border-0 shadow-sm p-2 rounded-3 stat-card border-warning-custom">
                                <p class="text-muted small fw-bold mb-0"><i class="fa fa-clock text-warning me-1"></i> PENDING</p>
                                <h4 class="fw-bold text-dark m-0"><?php echo $count_pending; ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-white border-0 shadow-sm p-2 rounded-3 stat-card border-info-custom">
                                <p class="text-muted small fw-bold mb-0"><i class="fa fa-spinner text-info me-1"></i> IN PROGRESS</p>
                                <h4 class="fw-bold text-dark m-0"><?php echo $count_inprogress; ?></h4>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-white border-0 shadow-sm p-2 rounded-3 stat-card border-success-custom">
                                <p class="text-muted small fw-bold mb-0"><i class="fa fa-check text-success me-1"></i> RESOLVED</p>
                                <h4 class="fw-bold text-dark m-0"><?php echo $count_resolved; ?></h4>
                            </div>
                        </div>
                    </div>

                    <!-- History Table (Fills remaining height, hides overflowing rows) -->
                    <div class="card card-custom border-0 shadow-sm flex-grow-1 d-flex flex-column" style="overflow: hidden;">
                        <div class="card-header bg-white pt-3 px-4 pb-2 d-flex justify-content-between align-items-center border-bottom flex-shrink-0">
                            <h6 class="fw-bold m-0 text-dark">My Request History</h6>
                            <span class="badge bg-light text-secondary border">Total: <?php echo $total_requests; ?></span>
                        </div>
                        <div class="card-body p-0 flex-grow-1" style="overflow: hidden;">
                            <table class="table table-hover align-middle mb-0 table-fixed">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 py-2 text-muted small text-uppercase" style="width: 20%;">Date</th>
                                        <th class="py-2 text-muted small text-uppercase" style="width: 55%;">Details</th>
                                        <th class="py-2 text-muted small text-uppercase" style="width: 25%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($total_requests > 0): ?>
                                        <?php foreach ($requests_data as $row): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="fw-bold text-dark small"><?php echo date('M d', strtotime($row['date_created'])); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary mb-1 border" style="font-size: 0.65rem;">
                                                        <?php echo $row['request_type']; ?>
                                                    </span>
                                                    <div class="text-dark small text-truncate"><?php echo htmlspecialchars($row['description']); ?></div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status = $row['status'];
                                                    if ($status == 'Resolved') {
                                                        echo '<span class="badge bg-success rounded-pill px-2 py-1" style="font-size: 0.7rem;"><i class="fa fa-check me-1"></i> Resolved</span>';
                                                    } elseif ($status == 'In Progress') {
                                                        echo '<span class="badge bg-info text-dark rounded-pill px-2 py-1" style="font-size: 0.7rem;"><i class="fa fa-spinner me-1"></i> In Progress</span>';
                                                    } else {
                                                        echo '<span class="badge bg-warning text-dark rounded-pill px-2 py-1" style="font-size: 0.7rem;"><i class="fa fa-clock me-1"></i> Pending</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4">
                                                <div class="text-muted small">No active or past requests.</div>
                                            </td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/get_notification.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/darkmode.js"></script>
</body>

</html>