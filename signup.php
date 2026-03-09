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
<body class="bg-light-gray min-vh-100 d-flex align-items-center justify-content-center p-3 p-md-5">

    <div class="login-bg-container">
        <img src="assets/img/bg.png" alt="Background">
        <div class="login-bg-overlay"></div>
    </div>

    <div class="card login-card shadow-lg border-0 overflow-hidden" style="max-width: 900px; width: 100%;">
        <div class="row g-0 align-items-stretch">
            
            <div class="col-lg-6 d-none d-lg-block border-end bg-white" 
                 style="background: #ffffff url('assets/img/logo.jpg') center center / contain no-repeat; min-height: 500px;">
            </div>
            
            <div class="col-lg-6 col-md-12 p-4 p-md-5 d-flex align-items-center bg-white">
                <div class="w-100">
                    
                    <div class="text-start mb-4">
                        <h1 class="h2 fw-bold text-dark mb-2">Create Account</h1>
                        <p class="text-secondary mb-4">Join our boarding house today.</p>
                    </div>

                    <?php if($msg): ?>
                        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                            <i class="fa fa-exclamation-circle me-2"></i><?php echo $msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="signup_action.php" method="POST" autocomplete="off">
                        <div class="mb-3">
                            <label class="form-label text-secondary fw-500 small">Full Name</label>
                            <div class="input-group input-group-custom">
                                <span class="input-group-text"><i class="fa fa-user text-primary-custom"></i></span>
                                <input type="text" name="fullname" class="form-control" placeholder="Enter full name" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary fw-500 small">Email address</label>
                            <div class="input-group input-group-custom">
                                <span class="input-group-text"><i class="fa fa-envelope text-primary-custom"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="example@gmail.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary fw-500 small">Password</label>
                            <div class="input-group input-group-custom">
                                <span class="input-group-text"><i class="fa fa-lock text-primary-custom"></i></span>
                                <input type="password" name="password" id="regPass1" class="form-control" placeholder="Create password" required>
                                <button class="btn btn-outline-secondary border-start-0" type="button" onclick="toggleSignPass('regPass1', this)" style="border-color: var(--border-color);">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-secondary fw-500 small">Confirm Password</label>
                            <div class="input-group input-group-custom">
                                <span class="input-group-text"><i class="fa fa-lock text-primary-custom"></i></span>
                                <input type="password" name="confirm_password" id="regPass2" class="form-control" placeholder="Repeat password" required>
                                <button class="btn btn-outline-secondary border-start-0" type="button" onclick="toggleSignPass('regPass2', this)" style="border-color: var(--border-color);">
                                    <i class="fa fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4 pt-1">
                            <button type="submit" name="signup" class="btn btn-primary-custom w-100 rounded-pill py-2 fw-bold h5 mb-0">
                                Sign Up
                            </button>
                        </div>

                        <div class="text-center">
                            <p class="text-secondary mb-0">Already have an account? 
                                <a href="index.php" class="text-primary-custom fw-bold text-decoration-none ms-1 transition-link">Login here</a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
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

        document.querySelectorAll('a[href="signup.php"], a[href="index.php"]').forEach(link => {
            link.addEventListener('click', function(e) {
                // Only apply if it's the "Sign up here" or "Login here" links
                if(this.classList.contains('text-primary-custom')) {
                    e.preventDefault();
                    const target = this.getAttribute('href');
                    const card = document.querySelector('.login-card');
                    
                    // Slide the card out
                    card.style.transition = 'all 0.4s ease-in';
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(-30px)';
                    
                    // Navigate after the animation
                    setTimeout(() => {
                        window.location.href = target;
                    }, 400);
                }
            });
        });
    </script>
</body>
</html>