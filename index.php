<?php
include 'includes/db.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? "admin/dashboard.php" : "tenant/dashboard.php"));
    exit();
}

$msg = "";
$msg_type = "";
// Capture role to maintain state after post
$role = isset($_POST['role']) ? $_POST['role'] : 'tenant';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // 1. Check if the email exists
    $sql_check = "SELECT * FROM users WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows === 0) {
        $msg = "Incorrect email address."; // Specific email error
        $msg_type = "danger";
    } else {
        $user = $result->fetch_assoc();

        // Check if the role matches the selection
        if ($user['role'] !== $role) {
            $msg = ($role === 'admin') ? "Not an admin account." : "This is an admin account. Please switch tabs.";
            $msg_type = "danger";
        } else {
            // Check password
            $hashed_password = hash('sha256', $password);

            if ($user['password'] === $hashed_password) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];

                header("Location: " . ($user['role'] === 'admin' ? "admin/dashboard.php" : "tenant/dashboard.php"));
                exit();
            } else {
                $msg = "Incorrect password.";
                $msg_type = "danger";
            }
        }
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

<body class="min-vh-100 d-flex align-items-center justify-content-center p-3 p-md-5">
    <div class="login-bg-container">
        <img src="assets/img/bg.png" alt="Background">
        <div class="login-bg-overlay"></div>
    </div>

    <div class="card login-card shadow-lg border-0 overflow-hidden <?php echo $msg ? 'shake-error' : ''; ?>" style="max-width: 900px; width: 100%;">
        <div class="row g-0 align-items-stretch">
            <div class="col-lg-6 d-none d-lg-block border-end" style="background: #ffffff url('assets/img/logo.jpg') center center / contain no-repeat; min-height: 500px;"></div>
            <div class="col-12 col-lg-6 p-4 p-md-5 d-flex align-items-center bg-white">
                <div class="w-100">
                    <div class="text-start mb-4">
                        <h1 class="h2 fw-bold text-dark mb-2">Welcome!!</h1>
                        <p class="text-secondary mb-4">Please login to your account.</p>
                    </div>

                    <?php if ($msg): ?>
                        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-circle me-2"></i><?php echo $msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" autocomplete="off">
                        <input type="hidden" name="role" id="role_input" value="<?php echo htmlspecialchars($role); ?>">

                        <ul class="nav nav-pills nav-fill mb-4 role-toggle" id="roleTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($role === 'tenant') ? 'active' : ''; ?>" id="tenant-tab" data-bs-toggle="pill" type="button" role="tab" onclick="switchRole('tenant')">Tenant</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo ($role === 'admin') ? 'active' : ''; ?>" id="admin-tab" data-bs-toggle="pill" type="button" role="tab" onclick="switchRole('admin')">Admin</button>
                            </li>
                        </ul>

                        <div class="mb-3">
                            <label class="form-label text-secondary fw-500">Email address</label>
                            <div class="input-group input-group-custom">
                                <span class="input-group-text"><i id="emailIcon" class="fa fa-user text-primary-custom"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="Email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary fw-500">Password</label>
                            <div class="input-group input-group-custom">
                                <span class="input-group-text"><i class="fa fa-lock text-primary-custom"></i></span>
                                <input type="password" name="password" id="loginPass" class="form-control" placeholder="Password" required>
                                <button class="btn btn-outline-secondary border-start-0" type="button" onclick="togglePassword('loginPass', this)" style="border-color: var(--border-color); background: white;">
                                    <i class="fa fa-eye text-secondary"></i>
                                </button>
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
                                <a href="signup.php" class="text-primary-custom fw-bold text-decoration-none ms-1 transition-link">Sign up here</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector("i");
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

        // Role switching logic
        function switchRole(role) {
            const roleInput = document.getElementById('role_input');
            const emailIcon = document.getElementById('emailIcon');
            const signupSection = document.getElementById('signupSection');

            if (role === 'admin') {
                roleInput.value = 'admin';
                emailIcon.className = 'fa fa-user-shield text-primary-custom';
                signupSection.style.visibility = 'hidden';
            } else {
                roleInput.value = 'tenant';
                emailIcon.className = 'fa fa-user text-primary-custom';
                signupSection.style.visibility = 'visible';
            }
        }

        // Apply saved role logic on page load
        window.onload = function() {
            const currentRole = "<?php echo $role; ?>";
            switchRole(currentRole);
        };
    </script>
</body>

</html>