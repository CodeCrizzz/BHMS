<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('admin');

if (isset($_POST['create_invoice'])) {
    $tenant_id = $_POST['tenant_id'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $date = $_POST['date'];

    $sql = "INSERT INTO payments (tenant_id, amount, description, date_created, status) VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idss", $tenant_id, $amount, $description, $date);

    if ($stmt->execute()) {
        $msg = "Invoice created successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error creating invoice.";
        $msg_type = "danger";
    }
}

if (isset($_GET['pay'])) {
    $id = $_GET['pay'];
    $conn->query("UPDATE payments SET status='paid' WHERE id=$id");
    header("Location: billing.php");
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM payments WHERE id=$id");
    header("Location: billing.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Billing & Payments | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
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

<div class="d-flex">
    <div class="sidebar p-3" style="width: 250px; min-height:100vh;">
        <h4 class="text-center mb-4">System Admin</h4>
        <a href="dashboard.php"><i class="fa fa-home me-2"></i> Dashboard</a>
        <a href="manage_tenants.php"><i class="fa fa-users me-2"></i> Manage Tenants</a>
        <a href="manage_rooms.php"><i class="fa fa-bed me-2"></i> Manage Rooms</a>
        <a href="billing.php" class="active"><i class="fa fa-file-invoice-dollar me-2"></i> Billing</a>
        <a href="manage_requests.php"><i class="fa fa-wrench me-2"></i> Manage Requests</a>
        <a href="talk.php"><i class="fa fa-comments me-2"></i> Chat with Tenant</a>
        <a href="manage_admins.php"><i class="fa fa-user-shield me-2"></i> Manage Admins</a>
    </div>

    <div class="flex-grow-1 p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary-custom">Billing & Payments</h2>
            <button class="btn bg-primary-custom" data-bs-toggle="modal" data-bs-target="#invoiceModal">
                <i class="fa fa-plus"></i> Create New Invoice
            </button>
        </div>

        <?php if(isset($msg)): ?>
            <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show">
                <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-custom p-3 bg-white">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>Invoice ID</th>
                        <th>Tenant Name</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT payments.*, users.fullname 
                            FROM payments 
                            JOIN users ON payments.tenant_id = users.id 
                            ORDER BY payments.date_created DESC";

                    $result = $conn->query($sql);

                    if($result->num_rows > 0){
                        while($row = $result->fetch_assoc()){
                            $status_badge = ($row['status'] == 'paid') ? 'bg-success' : 'bg-warning text-dark';
                    ?>
                    <tr>
                        <td>#INV-<?php echo str_pad($row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                        <td class="fw-bold"><?php echo $row['fullname']; ?></td>
                        <td><?php echo $row['description']; ?></td>
                        <td><?php echo $row['date_created']; ?></td>

                        <td>Php <?php echo number_format($row['amount'], 2); ?></td>

                        <td><span class="badge <?php echo $status_badge; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                        <td>
                            <?php if($row['status'] == 'pending'): ?>
                                <a href="billing.php?pay=<?php echo $row['id']; ?>" class="btn btn-sm btn-success" title="Mark as Paid">
                                    <i class="fa fa-check"></i>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-secondary" disabled><i class="fa fa-check-double"></i></button>
                            <?php endif; ?>

                            <a href="billing.php?delete=<?php echo $row['id']; ?>" 
                               class="btn btn-sm btn-outline-danger ms-1"
                               onclick="return confirm('Delete this invoice?');">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>No invoices found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="invoiceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title">Create New Invoice</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    
                    <div class="mb-3">
                        <label>Select Tenant</label>
                        <select name="tenant_id" class="form-select" required>
                            <option value="">-- Choose Tenant --</option>
                            <?php
                            // Fetch only users with role 'tenant'
                            $tenants = $conn->query("SELECT id, fullname FROM users WHERE role='tenant'");
                            while($t = $tenants->fetch_assoc()){
                                echo "<option value='".$t['id']."'>".$t['fullname']."</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control" placeholder="e.g. Rent for November" required>
                    </div>

                    <div class="mb-3">
                        <label>Amount ($)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>

                    <div class="mb-3">
                        <label>Date</label>
                        <input type="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="create_invoice" class="btn bg-primary-custom">Generate Invoice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../assets/js/darkmode.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/sidebar.js"></script>
</body>
</html>