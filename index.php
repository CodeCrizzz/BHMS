<?php 
include 'includes/db.php'; 

// Check for Errors
$error_msg = "";
$success_msg = "";
if(isset($_GET['error'])){
    if($_GET['error'] == 'user_not_found') { $error_msg = "Account not found!"; 
        $error_msg = "Account not found! Please check your email.";
    } elseif($_GET['error'] == 'wrong_password'){
        $error_msg = "Incorrect Password! Please try again.";
    }
}

if(isset($_GET['success']) && $_GET['success'] == 'registered'){
    $success_msg = "Account created successfully! Please Login.";
}

// Check which Tab should be active (Default to 'tenant')
$active_role = isset($_GET['role']) ? $_GET['role'] : 'tenant';

// Helper variables for CSS classes
$tenant_active = ($active_role == 'tenant') ? 'active' : '';
$tenant_show   = ($active_role == 'tenant') ? 'show active' : '';

$admin_active  = ($active_role == 'admin') ? 'active' : '';
$admin_show    = ($active_role == 'admin') ? 'show active' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>BHMS | Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card card-custom p-4 shadow-lg" style="width: 400px; background: white;">
        
        <h3 class="text-center mb-3 fw-bold text-primary-custom">StudyStay Boarding House Login</h3>
        
        <?php if($error_msg): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fa fa-exclamation-triangle me-2"></i>
                <div><?php echo $error_msg; ?></div>
            </div>
        <?php endif; ?>

        <?php if($success_msg): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fa fa-check-circle me-2"></i>
                <div><?php echo $success_msg; ?></div>
            </div>
        <?php endif; ?>

        <ul class="nav nav-pills nav-fill mb-4" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $tenant_active; ?>" id="pills-tenant-tab" data-bs-toggle="pill" data-bs-target="#pills-tenant" type="button">Tenant</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo $admin_active; ?>" id="pills-admin-tab" data-bs-toggle="pill" data-bs-target="#pills-admin" type="button">Admin</button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            
            <div class="tab-pane fade <?php echo $tenant_show; ?>" id="pills-tenant" role="tabpanel">
                <form action="login_action.php" method="POST">
                    <input type="hidden" name="role" value="tenant">
                    <div class="mb-3">
                        <label class="form-label text-muted">Tenant Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control" required placeholder="name@example.com">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-lock"></i></span>
                            
                            <input type="password" name="password" id="loginPass" class="form-control" required placeholder="******">
                            
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleLoginPass()">
                                <i id="loginIcon" class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" name="login" class="btn bg-primary-custom w-100 py-2">Login as Tenant</button>
                    
                    <div class="text-center mt-3">
                        <small class="text-muted">New here?</small> 
                        <a href="signup.php" class="fw-bold text-decoration-none text-primary-custom">Create an Account</a>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade <?php echo $admin_show; ?>" id="pills-admin" role="tabpanel">
                <form action="login_action.php" method="POST">
                    <input type="hidden" name="role" value="admin">
                    <div class="mb-3">
                        <label class="form-label text-muted">Admin Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-user-shield"></i></span>
                            <input type="email" name="email" class="form-control" required placeholder="admin@bhms.com">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-key"></i></span>
                            
                            <input type="password" name="password" id="adminPass" class="form-control" required placeholder="******">
                            
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleAdminPass()">
                                <i id="adminIcon" class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" name="login" class="btn bg-dark-custom w-100 py-2">Login as Admin</button>
                </form>
            </div>

        </div>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleLoginPass() {
        var input = document.getElementById("loginPass");
        var icon = document.getElementById("loginIcon");
        
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

    function toggleAdminPass() {
        var input = document.getElementById("adminPass");
        var icon = document.getElementById("adminIcon");
        
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
</body>
</html>