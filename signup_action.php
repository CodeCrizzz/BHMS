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

    // Insert New User
    // Note: not assigning a room yet. That is the Admin's job.
    $role = 'tenant';
    
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