<?php
include 'db.php';

if(isset($_POST['my_id']) && isset($_POST['other_id'])){
    $sender_id = $_POST['my_id'];
    $receiver_id = $_POST['other_id'];

    // --- 1. FETCH THE TENANT'S PROFILE IMAGE ---
    // We get the other person's details so we can show their picture next to their messages
    $res = $conn->query("SELECT fullname, profile_image FROM users WHERE id=$receiver_id");
    $other_user = $res->fetch_assoc();
    
    // Set up the image path or fallback (using profile_image as we discovered earlier!)
    $fallback_avatar = "https://ui-avatars.com/api/?name=".urlencode($other_user['fullname'])."&background=cbd5e1&color=1e293b&bold=true";
    $other_pic = !empty($other_user['profile_image']) ? '../assets/uploads/'.$other_user['profile_image'] : $fallback_avatar;
    // -------------------------------------------

    $sql = "SELECT * FROM messages 
            WHERE (sender_id = ? AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = ?) 
            ORDER BY timestamp ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        while($row = $result->fetch_assoc()){
            $is_me = ($row['sender_id'] == $sender_id);
            
            if ($is_me) {
                // ADMIN SENT MESSAGE (Blue Bubble, on the Right, No Picture)
                echo '<div class="d-flex justify-content-end mb-3">';
                echo '  <div class="msg-sent p-3 shadow-sm text-break" style="max-width: 75%; border-radius: 15px; border-top-right-radius: 0;">';
                echo '      <div>' . htmlspecialchars($row['message']) . '</div>';
                echo '      <small class="msg-timestamp d-block text-end mt-1" style="font-size: 0.65rem; opacity: 0.8;">' . date('h:i A', strtotime($row['timestamp'])) . '</small>';
                echo '  </div>';
                echo '</div>';
            } else {
                // TENANT RECEIVED MESSAGE (White Bubble, on the Left, WITH PROFILE PICTURE)
                echo '<div class="d-flex justify-content-start mb-3 align-items-end">';
                
                // DISPLAY THE PROFILE PICTURE HERE
                echo '  <img src="'.$other_pic.'" onerror="this.src=\''.$fallback_avatar.'\'" class="rounded-circle me-2 border shadow-sm bg-white" style="width: 35px; height: 35px; object-fit: cover; margin-bottom: 2px;">';
                
                echo '  <div class="msg-received p-3 shadow-sm text-break" style="max-width: 75%; border-radius: 15px; border-top-left-radius: 0;">';
                echo '      <div>' . htmlspecialchars($row['message']) . '</div>';
                echo '      <small class="msg-timestamp d-block text-end mt-1" style="font-size: 0.65rem; opacity: 0.8;">' . date('h:i A', strtotime($row['timestamp'])) . '</small>';
                echo '  </div>';
                echo '</div>';
            }
        }
    } else {
        // EMPTY CHAT STATE
        echo '<div class="text-center text-muted mt-5 opacity-50">';
        echo '  <i class="fa fa-comments fa-2x mb-2"></i><br>';
        echo '  <small>No messages yet. Start the conversation!</small>';
        echo '</div>';
    }
}
?>