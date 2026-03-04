<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('admin');

$msg = "";
$msg_type = "";

// --- HANDLE STATUS UPDATE ---
if(isset($_POST['update_status'])){
    $request_id = $_POST['request_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $request_id);
    
    if($stmt->execute()){
        $msg = "Request status updated successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error updating status.";
        $msg_type = "danger";
    }
}

// --- HANDLE DELETE ---
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $conn->query("DELETE FROM requests WHERE id=$id");
    header("Location: manage_requests.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manage Requests</title>
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
        
        <div class="sidebar p-3 flex-shrink-0 d-flex flex-column gap-2" style="width: 250px; min-height: 100vh; overflow-y: auto;">
            <h4 class="text-center mb-4 mt-2 flex-shrink-0">System Admin</h4>
            <a href="dashboard.php" class="nav-dashboard"><i class="fa fa-home me-2"></i> Dashboard</a>
            <a href="manage_tenants.php" class="nav-tenants"><i class="fa fa-users me-2"></i> Manage Tenants</a>
            <a href="manage_rooms.php" class="nav-rooms"><i class="fa fa-bed me-2"></i> Manage Rooms</a>
            <a href="billing.php" class="nav-billing"><i class="fa fa-file-invoice-dollar me-2"></i> Billing</a>
            <a href="manage_requests.php" class="nav-requests active"><i class="fa fa-wrench me-2"></i> Manage Requests</a>
            <a href="talk.php" class="nav-talk"><i class="fa fa-comments me-2"></i> Chat Support</a>
            <a href="manage_admins.php" class="nav-admins"><i class="fa fa-user-shield me-2"></i> Manage Admins</a>
        </div>

        <div class="flex-grow-1 p-4" style="overflow-y: auto;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary-custom">Tenant Requests</h2>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show">
                    <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom border-0 shadow-sm overflow-hidden mb-4" style="border-radius: 15px;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4">Date</th>
                                    <th>Tenant</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT requests.*, users.fullname, users.room_assigned 
                                        FROM requests 
                                        JOIN users ON requests.tenant_id = users.id 
                                        ORDER BY requests.date_created DESC";
                                $result = $conn->query($sql);

                                if (!$result) {
                                    die('<div class="alert alert-danger m-4"><strong>Database Crash!</strong> ' . $conn->error . '</div>');
                                }

                                if($result->num_rows > 0){
                                    while($row = $result->fetch_assoc()){
                                        $badge = 'bg-warning text-dark';
                                        if($row['status'] == 'Resolved') $badge = 'bg-success';
                                        if($row['status'] == 'In Progress') $badge = 'bg-info';
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted small"><?php echo date('M d, Y', strtotime($row['date_created'])); ?></td>
                                    <td>
                                        <div class="fw-bold"><?php echo $row['fullname']; ?></div>
                                        <small class="text-muted">Room: <?php echo $row['room_assigned'] ? $row['room_assigned'] : 'None'; ?></small>
                                    </td>
                                    <td><span class="fw-bold text-primary-custom"><?php echo $row['request_type']; ?></span></td>
                                    <td style="max-width: 300px;">
                                        <div class="text-truncate" title="<?php echo $row['description']; ?>">
                                            <?php echo $row['description']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-flex align-items-center">
                                            <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                            
                                            <input type="hidden" name="update_status" value="1"> 

                                            <select name="status" class="form-select form-select-sm" style="width: 130px;" onchange="this.form.submit()">
                                                <option value="Pending" <?php if($row['status']=='Pending') echo 'selected'; ?>>Pending</option>
                                                <option value="In Progress" <?php if($row['status']=='In Progress') echo 'selected'; ?>>In Progress</option>
                                                <option value="Resolved" <?php if($row['status']=='Resolved') echo 'selected'; ?>>Resolved</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="manage_requests.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this request?');">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center py-4 text-muted'>No requests found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
<script src="../assets/js/darkmode.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
</body>
</html>