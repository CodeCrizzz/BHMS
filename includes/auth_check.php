<?php
// includes/auth_check.php

// Check if the function already exists before creating it
if (!function_exists('checkLogin')) {
    
    function checkLogin($required_role = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            header("Location: ../index.php");
            exit();
        }

        if ($required_role != null && $_SESSION['role'] != $required_role) {
            if($_SESSION['role'] == 'tenant'){
                 header("Location: ../tenant/dashboard.php");
            } else {
                 header("Location: ../admin/dashboard.php");
            }
            exit();
        }
    }
    
}
?>