<?php
include 'db.php';
session_start();
$user_id = $_SESSION['user_id'];

// Check for pending bills and calculate the days remaining
$stmt = $conn->prepare("
    SELECT amount, date_created,
    DATEDIFF(DATE_ADD(date_created, INTERVAL 30 DAY), CURDATE()) as days_left 
    FROM payments 
    WHERE tenant_id = ? AND status = 'pending'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$pending_total = 0;
$is_urgent = false;

while ($row = $result->fetch_assoc()) {
    $pending_total += $row['amount'];
    // If any bill is due in 3 days or less (including overdue), set red status
    if ($row['days_left'] <= 3) {
        $is_urgent = true;
    }
}

// Get Unread Messages count for the sidebar badge
$stmt_msg = $conn->prepare("SELECT COUNT(id) as unread FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt_msg->bind_param("i", $user_id);
$stmt_msg->execute();
$unread = $stmt_msg->get_result()->fetch_assoc()['unread'] ?? 0;

echo json_encode([
    'pending' => $pending_total,
    'urgent' => true,
    'unread' => $unread
]);
