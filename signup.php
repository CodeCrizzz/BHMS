<?php 
// Check for success or error messages from the URL
$msg = "";
$msg_type = "";

if(isset($_GET['error'])){
    $msg = "Email already exists or passwords didn't match!";
    $msg_type = "danger";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Tenant Registration | BHMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card card-custom p-4 shadow-lg" style="width: 450px; background: white;">
        <div class="text-center mb-4">
            <h3 class="fw-bold text-primary-custom">Create Account</h3>
            <p class="text-muted small">Join our boarding house today</p>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> text-center small py-2">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form action="signup_action.php" method="POST">
            <div class="mb-3">
                <label class="form-label text-muted small">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                    <input type="text" name="fullname" class="form-control" required placeholder="Enter your full name">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted small">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" required placeholder="example@gmail.com">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted small">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                    <input type="password" name="password" id="regPass1" class="form-control" required placeholder="Create password">
                    <button class="btn btn-outline-secondary" type="button" onclick="toggleSignPass('regPass1', this)">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-muted small">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                    <input type="password" name="confirm_password" id="regPass2" class="form-control" required placeholder="Repeat password">
                    <button class="btn btn-outline-secondary" type="button" onclick="toggleSignPass('regPass2', this)">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" name="signup" class="btn bg-primary-custom w-100 py-2 mb-3">Sign Up</button>
            <div class="text-center">
                <small>Already have an account? <a href="index.php" class="text-decoration-none fw-bold">Login here</a></small>
            </div>
        </form>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSignPass(inputId, btn) {
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
</body>
</html>