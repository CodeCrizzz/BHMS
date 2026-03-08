<?php
include 'includes/db.php';

if (isset($_POST['signup'])) {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: signup.php?error=password_mismatch");
        exit();
    }

    // Check if Email already exists
    $check_email = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();

    if ($result->num_rows > 0) {
        header("Location: signup.php?error=email_exists");
        exit();
    }

    // --- SECURE ENCRYPTION ---
    // Encrypt the password using SHA-256 to match login system
    $hashed_password = hash('sha256', $password);

    $role = 'tenant';
    
    $sql = "INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // Bind the $hashed_password instead of the plain text one
    $stmt->bind_param("ssss", $fullname, $email, $hashed_password, $role);

    if ($stmt->execute()) {
        header("Location: index.php?success=registered");
    } else {
        header("Location: signup.php?error=db_error");
    }
}
?>