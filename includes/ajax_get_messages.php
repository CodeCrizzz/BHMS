<?php
include 'db.php';

if(isset($_POST['my_id']) && isset($_POST['other_id'])){
    $sender_id = $_POST['my_id'];
    $receiver_id = $_POST['other_id'];

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
            
            // 1. Determine Alignment (Right for Me, Left for Them)
            $alignment = $is_me ? 'justify-content-end' : 'justify-content-start';
            
            // 2. Determine CSS Class (Using our new styles)
            $bubble_class = $is_me ? 'msg-sent' : 'msg-received';
            
            echo '<div class="d-flex ' . $alignment . ' mb-3">';
            echo '  <div class="p-3 ' . $bubble_class . '" style="max-width: 75%;">';
            
            // Show Message Text
            echo '      <div>' . htmlspecialchars($row['message']) . '</div>';

            // Show Timestamp
            echo '      <small class="msg-timestamp">' . date('h:i A', strtotime($row['timestamp'])) . '</small>';
            echo '  </div>';
            echo '</div>';
        }
    } else {
        echo '<div class="text-center text-muted mt-5 opacity-50">';
        echo '  <i class="fa fa-comments fa-2x mb-2"></i><br>';
        echo '  <small>No messages yet. Start the conversation!</small>';
        echo '</div>';
    }
}
?>