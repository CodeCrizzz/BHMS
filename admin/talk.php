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

// Pre-fetch all tenants into an array so we can loop it smoothly for Desktop & Mobile views
$tenants_data = [];
$tenants_query = $conn->query("SELECT * FROM users WHERE role='tenant'");
if ($tenants_query) {
    while($t = $tenants_query->fetch_assoc()){
        $tenants_data[] = $t;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Admin Chat Support</title>
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
            
            <div class="bg-white border-end flex-shrink-0 d-none d-lg-flex flex-column shadow-sm z-2" style="width: 300px;">
                <div class="p-3 bg-light border-bottom sticky-top">
                    <h5 class="m-0 fw-bold text-primary-custom"><i class="fa fa-inbox me-2"></i> Messages</h5>
                </div>
                <div class="list-group list-group-flush flex-grow-1 chat-scroll" style="overflow-y: auto;">
                    <?php
                    foreach($tenants_data as $t){
                        $active = ($selected_tenant_id == $t['id']) ? 'bg-primary-custom text-white shadow-sm' : 'tenant-card border-bottom border-light';
                        $text_color = ($selected_tenant_id == $t['id']) ? 'text-white' : 'text-dark';
                        $fallback = "https://ui-avatars.com/api/?name=".urlencode($t['fullname'])."&background=cbd5e1&color=1e293b&bold=true";

                        echo '<a href="talk.php?tenant_id='.$t['id'].'" class="list-group-item list-group-item-action py-3 d-flex align-items-center border-0 '.$active.'">';
                        echo '<img src="../assets/uploads/profile.jpg" onerror="this.src=\''.$fallback.'\'" class="rounded-circle me-3 border border-2 shadow-sm bg-white" style="width: 45px; height: 45px; object-fit: cover;">';
                        echo '<div class="fw-bold text-truncate '.$text_color.'" style="max-width: 160px; font-size: 0.95rem;">'.htmlspecialchars($t['fullname']).'</div>';     
                        echo '</a>';
                    }
                    ?>
                </div>
            </div>

            <div class="flex-grow-1 d-flex flex-column position-relative" style="overflow: hidden;">

                <div class="mobile-tenant-strip bg-white d-lg-none z-2 chat-scroll p-2 shadow-sm" style="overflow-x: auto; min-height: fit-content;">
                    <div class="d-flex gap-3 px-2 align-items-center" style="min-width: max-content;">
                        <?php
                        foreach($tenants_data as $t){
                            $active_border = ($selected_tenant_id == $t['id']) ? 'border-primary border-3 shadow-sm' : 'border-secondary border-opacity-25';
                            $active_text = ($selected_tenant_id == $t['id']) ? 'fw-bold text-primary-custom' : 'text-muted';
                            $fallback = "https://ui-avatars.com/api/?name=".urlencode($t['fullname'])."&background=cbd5e1&color=1e293b&bold=true";

                            echo '<a href="talk.php?tenant_id='.$t['id'].'" class="text-decoration-none text-center d-flex flex-column align-items-center" style="width: 70px;">';
                            echo '<div class="story-avatar rounded-circle d-flex align-items-center justify-content-center '.$active_border.'">';
                            echo '<img src="../assets/uploads/profile.jpg" onerror="this.src=\''.$fallback.'\'" class="rounded-circle w-100 h-100 bg-white" style="object-fit: cover;">';
                            echo '</div>';
                            echo '<div class="text-truncate mt-1 w-100 '.$active_text.'" style="font-size: 0.7rem;">'.htmlspecialchars($t['fullname']).'</div>';
                            echo '</a>';
                        }
                        ?>
                    </div>
                </div>

                <?php if($selected_tenant_id): ?>
                    
                    <div class="p-3 bg-white border-bottom shadow-sm z-1 d-flex align-items-center gap-3">
                        <?php $header_fallback = "https://ui-avatars.com/api/?name=".urlencode($selected_tenant_name)."&background=3b82f6&color=fff&bold=true"; ?>
                        <img src="../assets/uploads/profile.jpg" onerror="this.src='<?php echo $header_fallback; ?>'" class="rounded-circle border" style="width: 45px; height: 45px; object-fit: cover;">
                        <div>
                            <h5 class="m-0 fw-bold"><?php echo htmlspecialchars($selected_tenant_name); ?></h5>
                            <small class="text-success fw-bold"><i class="fa fa-circle me-1" style="font-size: 8px;"></i>Online</small>
                        </div>
                    </div>
                    
                    <div id="chat-box" class="flex-grow-1 p-4 chat-scroll" style="overflow-y: auto;">
                         </div>

                    <div class="p-3 bg-white border-top shadow-lg z-2">
                        <form method="POST" autocomplete="off" class="m-0">
                            <div class="input-group bg-light rounded-pill p-1 border shadow-sm chat-input-wrapper">
                                <input type="text" name="message" class="form-control border-0 bg-transparent shadow-none px-4" placeholder="Type a message..." required style="height: 45px;">
                                <button type="submit" name="send_msg" class="btn bg-primary-custom text-white rounded-circle me-1 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                    <i class="fa fa-paper-plane ms-[-2px]"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                <?php else: ?>
                    
                    <div class="d-flex flex-column justify-content-center align-items-center h-100 text-center p-4">
                        <div class="bg-white p-5 rounded-4 shadow-sm border" style="max-width: 400px;">
                            <div class="bg-light text-primary-custom rounded-circle d-flex align-items-center justify-content-center mx-auto mb-4 shadow-sm" style="width: 80px; height: 80px;">
                                <i class="fa fa-comments fa-3x"></i>
                            </div>
                            <h4 class="fw-bold text-dark mb-2">Welcome to Support</h4>
                            <p class="text-secondary mb-0">Select a tenant from the list to view their messages and reply.</p>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>