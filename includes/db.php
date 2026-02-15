<?php
date_default_timezone_set('Asia/Manila');

$servername = "localhost"; //"sql103.infinityfree.com"; 
$username   = "root"; //"if0_40612478";
$password   = ""; //"oDunxhXQGpur";
$dbname     = "boarding_house_db"; //"if0_40612478_bhms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->query("SET time_zone = '+08:00'");

session_start();
?>