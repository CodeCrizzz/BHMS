<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('admin');

$current_admin_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

if(isset($_POST['add_admin'])){
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];

    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if($check->num_rows > 0){
        $msg = "Email already exists!";
        $msg_type = "danger";
    } elseif ($password !== $confirm_pass) {
        $msg = "Passwords do not match!";
        $msg_type = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, 'admin')");
        $stmt->bind_param("sss", $fullname, $email, $password);
        if($stmt->execute()){
            $msg = "New Admin created successfully!";
            $msg_type = "success";
        }
    }
}

if(isset($_POST['update_admin'])){
    $id = $_POST['admin_id'];
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $new_password = $_POST['password'];

    if(!empty($new_password)){
        $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, password=? WHERE id=?");
        $stmt->bind_param("sssi", $fullname, $email, $new_password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET fullname=?, email=? WHERE id=?");
        $stmt->bind_param("ssi", $fullname, $email, $id);
    }

    if($stmt->execute()){
        $msg = "Admin account updated successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error updating account.";
        $msg_type = "danger";
    }
}

if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    if($id != $current_admin_id){
        $conn->query("DELETE FROM users WHERE id=$id");
        header("Location: manage_admins.php?msg=deleted");
        exit();
    } else {
        $msg = "You cannot delete your own account!";
        $msg_type = "warning";
    }
}

if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'){
    $msg = "Admin account deleted.";
    $msg_type = "success";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manage Admins</title>
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
            <a href="manage_requests.php" class="nav-requests"><i class="fa fa-wrench me-2"></i> Manage Requests</a>
            <a href="talk.php" class="nav-talk"><i class="fa fa-comments me-2"></i> Chat Support</a>
            <a href="manage_admins.php" class="nav-admins active"><i class="fa fa-user-shield me-2"></i> Manage Admins</a>
        </div>
        <div class="flex-grow-1 p-4" style="overflow-y: auto;">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary-custom">Manage Administrators</h2>
                <button class="btn bg-primary-custom text-white" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                    <i class="fa fa-plus-circle me-2"></i> Create New Admin
                </button>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show">
                    <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom border-0 shadow-sm overflow-hidden mb-4" style="border-radius: 15px;">
                <div class="card-body p-0"> <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="text-center py-3" style="width: 80px;">ID</th>
                                    <th class="py-3">Name</th>
                                    <th class="py-3">Email</th>
                                    
                                    <th class="text-center py-3" style="width: 200px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $admins = $conn->query("SELECT * FROM users WHERE role='admin' ORDER BY id ASC");
                                while($row = $admins->fetch_assoc()){
                                    $is_me = ($row['id'] == $current_admin_id);
                                ?>
                                <tr>
                                    <td class="text-center text-secondary">#<?php echo $row['id']; ?></td>
                                    
                                    <td>
                                        <div class="fw-bold"><?php echo $row['fullname']; ?></div>
                                        <?php if($is_me): ?>
                                            <span class="badge bg-success" style="font-size: 0.7rem;">It's You</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-muted"><?php echo $row['email']; ?></td>
                                    
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editAdminModal"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-name="<?php echo $row['fullname']; ?>"
                                            data-email="<?php echo $row['email']; ?>">
                                            <i class="fa fa-pencil-alt"></i>
                                        </button>

                                        <?php if(!$is_me): ?>
                                            <a href="manage_admins.php?delete=<?php echo $row['id']; ?>" 
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to delete this admin?');">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-light text-muted border" disabled>
                                                <i class="fa fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary-custom text-white">
                    <h5 class="modal-title">Create Admin Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="fullname" class="form-control" required placeholder="Admin Name">
                        </div>
                        <div class="mb-3">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" required placeholder="admin@bhms.com">
                        </div>
                        
                        <div class="mb-3">
                            <label>Password</label>
                            <div class="input-group">
                                <input type="password" name="password" id="addPass1" class="form-control" required placeholder="******">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleModalPass('addPass1', this)">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label>Confirm Password</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="addPass2" class="form-control" required placeholder="******">
                                <button class="btn btn-outline-secondary" type="button" onclick="toggleModalPass('addPass2', this)">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_admin" class="btn bg-primary-custom text-white">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal fade" id="editAdminModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary-custom text-white">
                    <h5 class="modal-title">Edit Admin Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="admin_id" id="edit_id">
                        
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="fullname" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email Address</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label>New Password <small class="text-muted">(Leave blank to keep current)</small></label>
                            <input type="password" name="password" class="form-control" placeholder="New Password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_admin" class="btn bg-primary-custom text-white">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/darkmode.js"></script>
    <script>
        // To fill the Edit Modal
        var editModal = document.getElementById('editAdminModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            
            var id = button.getAttribute('data-id');
            var name = button.getAttribute('data-name');
            var email = button.getAttribute('data-email');
            
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_email').value = email;
        });

        // Function to toggle password visibility inside modals
        function toggleModalPass(inputId, btn) {
            var input = document.getElementById(inputId);
            var icon = btn.querySelector("i");
            
            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
    <script src="../assets/js/sidebar.js"></script>
</body>
</html>