<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('admin');

// // --- SECURITY CHECK ---
// if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') { 
//     header("Location: ../index.php"); 
//     exit(); 
// }

// --- BACKEND LOGIC ---

// ADD ROOM
if (isset($_POST['add_room'])) {
    $room_no = $_POST['room_no'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    $sql = "INSERT INTO rooms (room_no, price, status) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sds", $room_no, $price, $status);
    
    if ($stmt->execute()) {
        $msg = "Room added successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error adding room.";
        $msg_type = "danger";
    }
}

// DELETE ROOM
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM rooms WHERE id=$id");
    header("Location: manage_rooms.php"); 
    exit();
}

// UPDATE ROOM
if (isset($_POST['update_room'])) {
    $id = $_POST['room_id'];
    $room_no = $_POST['room_no'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    $sql = "UPDATE rooms SET room_no=?, price=?, status=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsi", $room_no, $price, $status, $id);
    
    if ($stmt->execute()) {
        $msg = "Room updated successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error updating room.";
        $msg_type = "danger";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manage Rooms | Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
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

<div class="d-flex">
    <div class="sidebar p-3" style="width: 250px; min-height:100vh;">
        <h4 class="text-center mb-4">System Admin</h4>
        <a href="dashboard.php"><i class="fa fa-home me-2"></i> Dashboard</a>
        <a href="manage_tenants.php"><i class="fa fa-users me-2"></i> Manage Tenants</a>
        <a href="manage_rooms.php" class="active"><i class="fa fa-bed me-2"></i> Manage Rooms</a>
        <a href="billing.php"><i class="fa fa-file-invoice-dollar me-2"></i> Billing</a>
        <a href="manage_requests.php"><i class="fa fa-wrench me-2"></i> Manage Requests</a>
        <a href="talk.php"><i class="fa fa-comments me-2"></i> Chat Support</a>
        <a href="manage_admins.php"><i class="fa fa-user-shield me-2"></i> Manage Admins</a>
    </div>

    <div class="flex-grow-1 p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-primary-custom">Manage Rooms</h2>
            <button class="btn bg-primary-custom" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                <i class="fa fa-plus"></i> Add New Room
            </button>
        </div>

        <?php if(isset($msg)): ?>
            <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card card-custom p-3 bg-white">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th>Room No</th>
                        <th>Price (Php.)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM rooms ORDER BY id DESC");
                    if($result->num_rows > 0){
                        while($row = $result->fetch_assoc()){
                            $badge = ($row['status'] == 'available') ? 'bg-success' : 'bg-danger';
                    ?>
                    <tr>
                        <td class="fw-bold"><?php echo $row['room_no']; ?></td>

                        <td>Php <?php echo number_format($row['price'], 2); ?></td>

                        <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary me-2 edit-btn" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editRoomModal"
                                data-id="<?php echo $row['id']; ?>"
                                data-room="<?php echo $row['room_no']; ?>"
                                data-price="<?php echo $row['price']; ?>"
                                data-status="<?php echo $row['status']; ?>">
                                <i class="fa fa-edit"></i>
                            </button>

                            <a href="manage_rooms.php?delete=<?php echo $row['id']; ?>" 
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Are you sure you want to delete this room?');">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php 
                        }
                    } else {
                        echo "<tr><td colspan='4' class='text-center'>No rooms found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom">
                <h5 class="modal-title">Add New Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Room Number</label>
                        <input type="text" name="room_no" class="form-control" required placeholder="e.g. 101">
                    </div>
                    <div class="mb-3">
                        <label>Price (Monthly)</label>
                        <input type="number" name="price" class="form-control" required placeholder="e.g. 500">
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_room" class="btn bg-primary-custom">Save Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-dark-custom">
                <h5 class="modal-title">Edit Room</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="room_id" id="edit_room_id">
                    
                    <div class="mb-3">
                        <label>Room Number</label>
                        <input type="text" name="room_no" id="edit_room_no" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Price (Monthly)</label>
                        <input type="number" name="price" id="edit_price" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="available">Available</option>
                            <option value="occupied">Occupied</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_room" class="btn bg-primary-custom">Update Room</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="../assets/js/darkmode.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // This script grabs the data from the Edit button and fills the modal
    var editModal = document.getElementById('editRoomModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget; // Button that triggered the modal
        
        // Extract info from data-* attributes
        var id = button.getAttribute('data-id');
        var room = button.getAttribute('data-room');
        var price = button.getAttribute('data-price');
        var status = button.getAttribute('data-status');
        
        // Update the modal's content.
        document.getElementById('edit_room_id').value = id;
        document.getElementById('edit_room_no').value = room;
        document.getElementById('edit_price').value = price;
        document.getElementById('edit_status').value = status;
    });
</script>
<script src="../assets/js/sidebar.js"></script>
</body>
</html>