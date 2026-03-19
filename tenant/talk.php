<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('tenant');

$my_id = $_SESSION['user_id'];

$admin_query = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$admin_data = $admin_query->fetch_assoc();
$admin_id = $admin_data ? $admin_data['id'] : 0;

if ($admin_id > 0) {
    $conn->query("UPDATE messages SET is_read = 1 WHERE sender_id = $admin_id AND receiver_id = $my_id AND is_read = 0");
}

if (isset($_POST['send_msg']) && $admin_id > 0) {
    $msg = $_POST['message'];
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
    <title>Chat Admin | Tenant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-light d-flex flex-column" style="height: 100vh; overflow: hidden; margin: 0;">
    <nav class="navbar navbar-expand-lg navbar-custom px-3 py-3 shadow-sm d-flex justify-content-between flex-nowrap flex-shrink-0" style="z-index: 1000;">
        <div class="d-flex align-items-center gap-2" style="min-width: 0;">
            <button class="btn btn-outline-secondary d-lg-none flex-shrink-0" id="sidebarToggle"><i class="fa fa-bars"></i></button>
            <div class="navbar-brand-custom fw-bold text-truncate"><i class="fa fa-building me-2"></i> StudyStay Boarding House</div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
            <button id="darkModeToggle" class="btn btn-outline-secondary rounded-circle" style="width: 38px; height: 38px; padding: 0; display: flex; align-items: center; justify-content: center;"><i class="fa fa-moon"></i></button>
            <a href="../logout.php" class="btn btn-danger btn-sm d-flex align-items-center" style="height: 36px; white-space: nowrap;"><i class="fa fa-sign-out-alt me-1"></i> <span class="d-none d-sm-inline">Logout</span></a>
        </div>
    </nav>

    <div class="d-flex flex-grow-1" style="overflow: hidden;">
        <div class="sidebar p-3 flex-shrink-0" style="width: 250px; overflow-y: auto;">
            <h4 class="text-center mb-4 mt-2">My Portal</h4>
            <a href="dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>"><i class="fa fa-home me-2"></i> Dashboard</a>
            <a href="profile.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : ''; ?>"><i class="fa fa-user me-2"></i> My Profile</a>
            <a href="payments.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'payments.php') ? 'active' : ''; ?>"><span><i class="fa fa-credit-card me-2"></i> Billing</span><span id="sidebar-bell-container"></span></a>
            <a href="requests.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'requests.php') ? 'active' : ''; ?>">
                <span><i class="fa fa-wrench me-2"></i> My Requests</span>
                <?php
                $safe_tenant_id = $_SESSION['user_id'] ?? 0;
                $sidebar_req_query = $conn->query("SELECT COUNT(id) AS total FROM requests WHERE tenant_id = $safe_tenant_id AND status IN ('In Progress', 'Resolved')");
                $sidebar_req_count = $sidebar_req_query ? $sidebar_req_query->fetch_assoc()['total'] : 0;
                if ($sidebar_req_count > 0):
                ?>
                    <span class="badge bg-warning text-dark rounded-pill shadow-sm" style="font-size: 0.7rem; padding: 4px 8px;"><?php echo $sidebar_req_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="talk.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'talk.php') ? 'active' : ''; ?>"><span><i class="fa fa-comments me-2"></i> Chat Admin</span><span id="sidebar-chat-container"></span></a>
        </div>

        <div class="flex-grow-1 d-flex flex-column p-4" style="overflow: hidden;">
            <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column" style="border-radius: 25px; overflow: hidden;">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center gap-3">
                    <div class="bg-primary rounded-circle p-2 text-white shadow-sm d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="fa fa-user-tie"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold m-0 text-dark">Admin Support</h6>
                        <small class="text-success fw-bold"><i class="fa fa-circle me-1" style="font-size: 0.5rem;"></i>Online</small>
                    </div>
                </div>

                <div id="chat-box" class="flex-grow-1 p-4 bg-light" style="overflow-y: auto; background-image: url('https://www.transparenttextures.com/patterns/cubes.png');">
                </div>

                <div class="p-3 bg-white border-top">
                    <form method="POST" autocomplete="off" class="m-0">
                        <div class="input-group bg-light p-1 rounded-pill border">
                            <input type="text" name="message" class="form-control border-0 bg-transparent px-4" placeholder="Type your message here..." required style="box-shadow: none;">
                            <button type="submit" name="send_msg" class="btn btn-primary rounded-circle shadow-sm" style="width: 45px; height: 45px;">
                                <i class="fa fa-paper-plane"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
    <script src="../assets/js/get_notification.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/darkmode.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>