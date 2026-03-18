<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('tenant');

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// --- HANDLE SUBMISSION ---
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

// --- FETCH EXISTING REQUESTS ---
$requests_sql = "SELECT * FROM requests WHERE tenant_id = ? ORDER BY date_created DESC";
$stmt_req = $conn->prepare($requests_sql);
$stmt_req->bind_param("i", $user_id);
$stmt_req->execute();
$requests_result = $stmt_req->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Requests | BHMS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-light">
    <div class="d-flex">
        <?php include 'sidebar.php'; ?>
        <div class="content-p main-content w-100 p-4">
            <div class="container-fluid">
                <div class="mb-4">
                    <h3 class="fw-bold"><i class="fa fa-wrench me-2 text-primary"></i>Service Requests</h3>
                    <p class="text-muted">Submit maintenance issues or complaints to the administration.</p>
                </div>

                <?php if ($msg): ?>
                    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show shadow-sm" role="alert">
                        <?php echo $msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="card request-card shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="card-title fw-bold mb-4">New Request</h5>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label text-secondary small fw-bold">REQUEST TYPE</label>
                                        <select name="request_type" class="form-select border-0 bg-light py-2" required>
                                            <option value="Maintenance">Maintenance / Repair</option>
                                            <option value="Cleaning">Cleaning Services</option>
                                            <option value="Complaint">Complaint</option>
                                            <option value="Security">Security Issue</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label text-secondary small fw-bold">DESCRIPTION</label>
                                        <textarea name="description" class="form-control border-0 bg-light" rows="5" placeholder="Please provide details..." required></textarea>
                                    </div>
                                    <button type="submit" name="submit_request" class="btn btn-primary w-100 py-2 shadow-sm">
                                        <i class="fa fa-paper-plane me-2"></i>Submit Request
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card request-card shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="card-title fw-bold mb-4">Request History</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr class="small text-uppercase text-secondary">
                                                <th>Date Submitted</th>
                                                <th>Type</th>
                                                <th>Details</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($requests_result->num_rows > 0): ?>
                                                <?php while ($row = $requests_result->fetch_assoc()): ?>
                                                    <tr>
                                                        <td class="text-nowrap"><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
                                                        <td><span class="fw-bold"><?php echo $row['request_type']; ?></span></td>
                                                        <td class="text-muted small"><?php echo htmlspecialchars($row['description']); ?></td>
                                                        <td>
                                                            <?php
                                                            $status = $row['status'];
                                                            $class = ($status == 'Pending') ? 'bg-pending' : (($status == 'Resolved') ? 'bg-resolved' : 'bg-inprogress');
                                                            ?>
                                                            <span class="status-badge <?php echo $class; ?>">
                                                                <i class="fa fa-circle me-1" style="font-size: 0.5rem;"></i>
                                                                <?php echo $status; ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-5 text-muted">
                                                        <i class="fa fa-clipboard-list fa-3x mb-3 opacity-25"></i>
                                                        <p>You haven't submitted any requests yet.</p>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/get_notification.js"></script>
</body>

</html>