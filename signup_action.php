<?php
include 'includes/db.php';

if (isset($_POST['signup'])) {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Check if passwords match
    if ($password !== $confirm_password) {
        header("Location: signup.php?error=password_mismatch");
        exit();
    }

    // 2. Check if Email already exists
    $check_email = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();

    if ($result->num_rows > 0) {
        header("Location: signup.php?error=email_exists");
        exit();
    }

    // 3. Insert New User
    // Note: We are not assigning a room yet. That is the Admin's job.
    $role = 'tenant';
    
    // Using plain text to match your current login system. 
    // In production, use password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $fullname, $email, $password, $role);

    if ($stmt->execute()) {
        // Success! Redirect to login page with success message
        header("Location: index.php?success=registered");
    } else {
        header("Location: signup.php?error=db_error");
    }
}
?>