<?php
include 'db.php';

if(isset($_POST['my_id']) && isset($_POST['other_id'])){
    // Force these to be numbers to prevent empty string crashes
    $sender_id = intval($_POST['my_id']);
    $receiver_id = intval($_POST['other_id']);

    // Stop if the IDs are missing
    if ($sender_id === 0 || $receiver_id === 0) {
        echo '<div class="text-center text-muted mt-5"><small>System waiting for valid user IDs...</small></div>';
        exit();
    }

    // --- 1. FETCH THE TENANT'S PROFILE IMAGE ---
    $res = $conn->query("SELECT fullname, profile_image FROM users WHERE id=$receiver_id");
    if($res && $res->num_rows > 0) {
        $other_user = $res->fetch_assoc();
    } else {
        $other_user = ['fullname' => 'Support', 'profile_image' => ''];
    }
    
    $fallback_avatar = "https://ui-avatars.com/api/?name=".urlencode($other_user['fullname'])."&background=cbd5e1&color=1e293b&bold=true";
    $other_pic = !empty($other_user['profile_image']) ? '../assets/uploads/'.$other_user['profile_image'] : $fallback_avatar;

    // --- UPDATED: Now uses created_at instead of timestamp ---
    $sql = "SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = ?) 
            ORDER BY created_at ASC";

    $stmt = $conn->prepare($sql);

    // --- THE ULTIMATE CRASH CATCHER ---
    if (!$stmt) {
        echo '<div class="m-4 p-3 rounded" style="background:#ffe6e6; color:#cc0000; border: 1px solid #cc0000;">';
        echo '<strong>🚨 Database Error Blocked!</strong><br>';
        echo 'MySQL says: <code>' . $conn->error . '</code><br>';
        echo '</div>';
        exit(); 
    }
    // ----------------------------------

    $stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $is_me = ($row['sender_id'] == $sender_id);
            
            if ($is_me) {
                echo '<div class="d-flex justify-content-end mb-3">';
                echo '  <div class="msg-sent p-3 shadow-sm text-break" style="max-width: 75%; border-radius: 15px; border-top-right-radius: 0;">';
                echo '      <div>' . htmlspecialchars($row['message']) . '</div>';
                // --- UPDATED: Reads created_at ---
                echo '      <small class="msg-timestamp d-block text-end mt-1" style="font-size: 0.65rem; opacity: 0.8;">' . date('h:i A', strtotime($row['created_at'])) . '</small>';
                echo '  </div>';
                echo '</div>';
            } else {
                echo '<div class="d-flex justify-content-start mb-3 align-items-end">';
                echo '  <img src="'.$other_pic.'" onerror="this.src=\''.$fallback_avatar.'\'" class="rounded-circle me-2 border shadow-sm bg-white" style="width: 35px; height: 35px; object-fit: cover; margin-bottom: 2px;">';
                echo '  <div class="msg-received p-3 shadow-sm text-break" style="max-width: 75%; border-radius: 15px; border-top-left-radius: 0;">';
                echo '      <div>' . htmlspecialchars($row['message']) . '</div>';
                // --- UPDATED: Reads created_at ---
                echo '      <small class="msg-timestamp d-block text-end mt-1" style="font-size: 0.65rem; opacity: 0.8;">' . date('h:i A', strtotime($row['created_at'])) . '</small>';
                echo '  </div>';
                echo '</div>';
            }
        }
    } else {
        echo '<div class="text-center text-muted mt-5 opacity-50">';
        echo '  <i class="fa fa-comments fa-2x mb-2"></i><br>';
        echo '  <small>No messages yet. Start the conversation!</small>';
        echo '</div>';
    }
}
?>