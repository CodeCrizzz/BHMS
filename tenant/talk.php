<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('tenant');

$my_id = $_SESSION['user_id'];

// Find the actual Admin ID dynamically 
$admin_query = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$admin_data = $admin_query->fetch_assoc();
$admin_id = $admin_data ? $admin_data['id'] : 0;

// --- MARK ALL MESSAGES FROM ADMIN AS READ ---
if ($admin_id > 0) {
    $conn->query("UPDATE messages SET is_read = 1 WHERE sender_id = $admin_id AND receiver_id = $my_id AND is_read = 0");
}

// GET UNREAD MESSAGE COUNT ---
$unread_query = $conn->query("SELECT COUNT(id) AS unread FROM messages WHERE receiver_id = " . $_SESSION['user_id'] . " AND is_read = 0");
$unread_count = 0;
if ($unread_query) {
    $unread_data = $unread_query->fetch_assoc();
    $unread_count = $unread_data['unread'];
}

// --- GET UNPAID BILLS COUNT ---
$stmt_pending = $conn->prepare("SELECT SUM(amount) as total FROM payments WHERE tenant_id = ? AND status = 'pending'");
$stmt_pending->bind_param("i", $my_id);
$stmt_pending->execute();
$pending_total = $stmt_pending->get_result()->fetch_assoc()['total'];
$pending_total = $pending_total ? $pending_total : 0.00;

if (isset($_POST['send_msg']) && $admin_id > 0) {
    $msg = $_POST['message'];

    //Only send if message is not empty
    if (!empty(trim($msg))) {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $my_id, $admin_id, $msg);
        $stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Chat Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-light d-flex flex-column h-100" style="overflow: hidden;">
    <nav class="navbar navbar-expand-lg navbar-custom px-3 py-3 shadow-sm d-flex justify-content-between flex-nowrap" style="z-index: 1000;">
        <div class="d-flex align-items-center gap-2" style="min-width: 0;"> <button class="btn btn-outline-secondary d-lg-none flex-shrink-0" id="sidebarToggle">
                <i class="fa fa-bars"></i>
            </button>

            <div class="navbar-brand-custom fw-bold text-truncate">
                <i class="fa fa-building me-2"></i> StudyStay Boarding House
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <button id="darkModeToggle" class="btn btn-outline-secondary rounded-circle" style="width: 38px; height: 38px; padding: 0; display: flex; align-items: center; justify-content: center;">
                <i class="fa fa-moon"></i>
            </button>

            <a href="../logout.php" class="btn btn-danger btn-sm d-flex align-items-center" style="height: 36px; white-space: nowrap;">
                <i class="fa fa-sign-out-alt me-1"></i> <span class="d-none d-sm-inline">Logout</span>
            </a>
        </div>
    </nav>

    <div class="d-flex flex-grow-1" style="overflow: hidden;">
        <div class="sidebar p-3" style="width: 250px; overflow-y: auto;">
            <h4 class="text-center mb-4 mt-2">My Portal</h4>
            <a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fa fa-home me-2"></i> Dashboard
            </a>
            <a href="profile.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>">
                <i class="fa fa-user me-2"></i> My Profile
            </a>

            <a href="payments.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'payments.php') ? 'active' : ''; ?>">
                <span><i class="fa fa-credit-card me-2"></i> Billing</span>
                <span id="sidebar-bell-container"></span>
            </a>

            <a href="talk.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'talk.php') ? 'active' : ''; ?>">
                <span><i class="fa fa-comments me-2"></i> Chat Admin</span>
                <span id="sidebar-chat-container"></span>
            </a>
        </div>

        <div class="d-flex flex-column flex-grow-1" style="overflow: hidden;">
            <div class="p-3 bg-white border-bottom shadow-sm flex-shrink-0">
                <h5 class="m-0 text-primary-custom">Chat with Admin</h5>
            </div>

            <div id="chat-box" class="flex-grow-1 p-4" style="overflow-y: auto;"></div>

            <div class="p-3 bg-white border-top flex-shrink-0">
                <form method="POST" autocomplete="off">
                    <div class="input-group">
                        <input type="text" name="message" class="form-control" placeholder="Type a message..." required>
                        <button type="submit" name="send_msg" class="btn bg-primary-custom text-white"><i class="fa fa-paper-plane"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/darkmode.js"></script>
    <script>
        $(document).ready(function() {
            var myId = <?php echo $my_id; ?>;
            var adminId = <?php echo $admin_id; ?>;
            var chatBox = $("#chat-box");
            var autoScroll = true;

            function loadMessages() {
                $.post('../includes/ajax_get_messages.php', {
                    my_id: myId,
                    other_id: adminId
                }, function(data) {
                    chatBox.html(data);
                    if (autoScroll) {
                        chatBox.scrollTop(chatBox[0].scrollHeight);
                        autoScroll = false;
                    }
                });
            }
            loadMessages();
            setInterval(loadMessages, 2000);
        });
    </script>

    <script src="../assets/js/darkmode.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/get_notification.js"></script>
</body>

</html>