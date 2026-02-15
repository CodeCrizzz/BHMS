<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('admin');

$admin_id = $_SESSION['user_id'];
$selected_tenant_id = isset($_GET['tenant_id']) ? intval($_GET['tenant_id']) : null;
$selected_tenant_name = "Select a Tenant";

// Fetch Tenant Name
if($selected_tenant_id){
    $res = $conn->query("SELECT fullname FROM users WHERE id = $selected_tenant_id");
    if($res->num_rows > 0) $selected_tenant_name = $res->fetch_assoc()['fullname'];
}

// Handle Send Message 
if(isset($_POST['send_msg']) && $selected_tenant_id){
    $msg = $_POST['message'];
    
    if(!empty(trim($msg))){
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $admin_id, $selected_tenant_id, $msg);
        $stmt->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Admin Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-light d-flex flex-column h-100" style="overflow: hidden;">

    <nav class="navbar navbar-expand-lg navbar-custom px-3 py-3 shadow-sm d-flex justify-content-between flex-nowrap" style="z-index: 1000;">
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
        
        <div class="sidebar p-3 flex-shrink-0 d-flex flex-column gap-2" style="width: 250px; min-height: 100vh; overflow-y: auto;">
            <h4 class="text-center mb-4 mt-2 flex-shrink-0">System Admin</h4>
            
            <a href="dashboard.php" class="nav-dashboard"><i class="fa fa-home me-2"></i> Dashboard</a>
            <a href="manage_tenants.php" class="nav-tenants"><i class="fa fa-users me-2"></i> Manage Tenants</a>
            <a href="manage_rooms.php" class="nav-rooms"><i class="fa fa-bed me-2"></i> Manage Rooms</a>
            <a href="billing.php" class="nav-billing"><i class="fa fa-file-invoice-dollar me-2"></i> Billing</a>
            <a href="manage_requests.php" class="nav-requests"><i class="fa fa-wrench me-2"></i> Manage Requests</a>
            <a href="talk.php" class="nav-talk active"><i class="fa fa-comments me-2"></i> Chat Support</a>
            <a href="manage_admins.php" class="nav-admins"><i class="fa fa-user-shield me-2"></i> Manage Admins</a>
        </div>

        <div class="d-flex flex-grow-1" style="overflow: hidden;">
            
            <div class="bg-white border-end flex-shrink-0" style="width: 300px; overflow-y: auto;">
                <div class="p-3 bg-light border-bottom sticky-top">
                    <h5 class="m-0 text-primary-custom">Tenants</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php
                    $tenants = $conn->query("SELECT * FROM users WHERE role='tenant'");
                    while($t = $tenants->fetch_assoc()){
                        $active = ($selected_tenant_id == $t['id']) ? 'active' : '';
                        
                        // 1. Path to your default profile picture
                        $profile_image = "../assets/uploads/profile.jpg"; 
                        
                        // 2. Fallback API: Generates an avatar with initials if the image above is missing
                        $fallback = "https://ui-avatars.com/api/?name=".urlencode($t['fullname'])."&background=cbd5e1&color=1e293b&bold=true";

                        // 3. Output the bigger, flexbox-styled list item
                        echo '<a href="talk.php?tenant_id='.$t['id'].'" class="list-group-item list-group-item-action py-3 d-flex align-items-center '.$active.'">';
                        
                        // Profile Image (Width & Height set to 45px)
                        echo '<img src="'.$profile_image.'" onerror="this.src=\''.$fallback.'\'" class="rounded-circle me-3 border border-2 shadow-sm" style="width: 45px; height: 45px; object-fit: cover; background: #fff;" alt="DP">';
                        
                        // Tenant Name
                        echo '<div class="fw-bold text-truncate" style="max-width: 170px;">'.htmlspecialchars($t['fullname']).'</div>';
                        
                        echo '</a>';
                    }
                    ?>
                </div>
            </div>

            <div class="flex-grow-1 d-flex flex-column" style="overflow: hidden;">
                <?php if($selected_tenant_id): ?>
                    
                    <div class="p-3 bg-white border-bottom flex-shrink-0">
                        <h5 class="m-0"><?php echo $selected_tenant_name; ?></h5>
                    </div>
                    
                    <div id="chat-box" class="flex-grow-1 p-4" style="overflow-y: auto;">
                         </div>

                    <div class="p-3 bg-white border-top flex-shrink-0">
                        <form method="POST" autocomplete="off">
                            <div class="input-group">
                                <input type="text" name="message" class="form-control" placeholder="Type a message..." required>
                                <button type="submit" name="send_msg" class="btn bg-primary-custom text-white"><i class="fa fa-paper-plane"></i></button>
                            </div>
                        </form>
                    </div>

                <?php else: ?>
                    <div class="d-flex justify-content-center align-items-center h-100 text-muted">
                        <div class="text-center">
                            <i class="fa fa-comments fa-3x mb-3 text-secondary opacity-25"></i>
                            <h4>Select a tenant to start chatting</h4>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div> 
    </div>

<script src="../assets/js/darkmode.js"></script>
<script>
$(document).ready(function(){
    var myId = <?php echo $admin_id; ?>;
    var otherId = <?php echo $selected_tenant_id ? $selected_tenant_id : 'null'; ?>;
    var chatBox = $("#chat-box");
    var autoScroll = true;

    function loadMessages(){
        if(otherId){
            $.post('../includes/ajax_get_messages.php', {my_id: myId, other_id: otherId}, function(data){
                chatBox.html(data);
                if(autoScroll){
                    chatBox.scrollTop(chatBox[0].scrollHeight);
                    autoScroll = false;
                }
            });
        }
    }
    loadMessages();
    setInterval(loadMessages, 2000);
});
</script>
<script src="../assets/js/sidebar.js"></script>
</body>
</html>