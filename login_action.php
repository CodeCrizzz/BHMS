<?php
include 'includes/db.php';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role']; 

    $sql = "SELECT * FROM users WHERE email = ? AND role = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify Password
        if ($password == $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['fullname'];

            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: tenant/dashboard.php");
            }
            exit();
        } else {
            // Case 1: Wrong Password
            header("Location: index.php?error=wrong_password&role=$role"); 
            exit();
        }
    } else {
        // Case 2: Account does not exist (or wrong role selected)
        header("Location: index.php?error=user_not_found&role=$role");
        exit();
    }
}
?>