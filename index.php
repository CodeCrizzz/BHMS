<?php
include 'includes/db.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: tenant/dashboard.php");
    }
    exit();
}

$msg = "";
$msg_type = "";

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role']; // Grab the role from the hidden toggle switch

    // SHA-256 for basic encryption
    $hashed_password = hash('sha256', $password);

    // Added the role check to the database query for extra security
    $sql = "SELECT * FROM users WHERE email = ? AND password = ? AND role = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $email, $hashed_password, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: tenant/dashboard.php");
        }
        exit();
    } else {
        $msg = "Incorrect Email, Password, or Role.";
        $msg_type = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Login | StudyStay Boarding House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light-gray min-vh-100 d-flex align-items-center justify-content-center p-3 p-md-5">

    <div class="card login-card shadow-lg border-0 overflow-hidden" style="max-width: 900px; width: 100%;">
        <div class="row g-0 align-items-stretch">
            
            <div class="col-lg-6 d-none d-lg-block border-end" style="background: #ffffff url('assets/img/logo.jpg') center center / contain no-repeat; min-height: 500px;">
            </div>
            
            <div class="col-lg-6 col-md-12 p-4 p-md-5 d-flex align-items-center bg-white">
                <div class="w-100">
                    
                    <div class="text-start mb-4">
                        <h1 class="h2 fw-bold text-dark mb-2">Login</h1>
                        <p class="text-secondary mb-4">Please login to your account.</p>
                    </div>

                    <?php if($msg): ?>
                        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-circle me-2"></i><?php echo $msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_GET['error']) && $_GET['error'] == 'email_exists'): ?>
                        <div class="alert alert-danger">Email already registered! Try another one.</div>
                    <?php endif; ?>

                    <?php if(isset($_GET['success']) && $_GET['success'] == 'registered'): ?>
                        <div class="alert alert-success">Registered Successfully! Please login now.</div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        
                        <input type="hidden" name="role" id="role_input" value="tenant">

                        <ul class="nav nav-pills nav-fill mb-4 role-toggle" id="roleTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="tenant-tab" data-bs-toggle="pill" type="button" role="tab" onclick="switchRole('tenant')">Tenant</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="admin-tab" data-bs-toggle="pill" type="button" role="tab" onclick="switchRole('admin')">Admin</button>
                            </li>
                        </ul>

                        <div class="mb-3">
                            <label class="form-label text-secondary fw-500">Email address</label>
                            <div class="input-group input-group-custom">
                                <span class="input-group-text"><i id="emailIcon" class="fa fa-user text-primary-custom"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="Email" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="form-label text-secondary fw-500">Password</label>
                            <div class="input-group input-group-custom">
                                <span class="input-group-text"><i class="fa fa-lock text-primary-custom"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="Password" required>
                            </div>
                        </div>

                        <div class="text-end mb-4">
                            <a href="#" class="text-primary-custom text-decoration-none small">Forgot password?</a>
                        </div>

                        <div class="mb-4 pt-1">
                            <button type="submit" name="login" class="btn btn-primary-custom w-100 rounded-pill py-2 fw-bold h5 mb-0">
                                Log In
                            </button>
                        </div>

                        <div id="signupSection" class="text-center">
                            <p class="text-secondary mb-0">Don't have an account? 
                                <a href="signup.php" class="text-primary-custom fw-bold text-decoration-none ms-1">Sign up here</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Javascript to handle the dynamic changes when swapping roles
        function switchRole(role) {
            const roleInput = document.getElementById('role_input');
            const emailIcon = document.getElementById('emailIcon');
            const signupSection = document.getElementById('signupSection');

            if (role === 'admin') {
                // Set to Admin
                roleInput.value = 'admin';
                emailIcon.className = 'fa fa-user-shield text-primary-custom'; 
                
                // Hide the text completely but KEEP the empty space so the card stays the same size!
                signupSection.style.visibility = 'hidden'; 
                
            } else {
                // Set to Tenant
                roleInput.value = 'tenant';
                emailIcon.className = 'fa fa-user text-primary-custom'; 
                
                // Bring the text back!
                signupSection.style.visibility = 'visible'; 
            }
        }
    </script>
</body>
</html>