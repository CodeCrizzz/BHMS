<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('admin');

$msg = "";
$msg_type = "";

// --- 1. ADD TENANT ---
if(isset($_POST['add_tenant'])){
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = $_POST['password']; 
    $room_assigned = empty($_POST['room_assigned']) ? NULL : $_POST['room_assigned'];

    $check = $conn->query("SELECT id FROM users WHERE email='$email'");
    if($check->num_rows > 0){
        $msg = "Email already exists!";
        $msg_type = "danger";
    } else {
        
        // --- SMART LOGIC: GENERATE UNIQUE 5-DIGIT ID ---
        $is_unique = false;
        $new_tenant_id = 0;
        
        while (!$is_unique) {
            $new_tenant_id = rand(10000, 99999); // Generates a random number from 10000 to 99999
            
            // Ask the database if this 5-digit ID already exists
            $check_id = $conn->query("SELECT id FROM users WHERE id='$new_tenant_id'");
            if($check_id->num_rows == 0){
                $is_unique = true; // If 0 results come back, the ID is unique and safe to use!
            }
        }
        // ------------------------------------------------

        // Insert the new tenant using the generated $new_tenant_id
        $stmt = $conn->prepare("INSERT INTO users (id, fullname, email, password, role, room_assigned) VALUES (?, ?, ?, ?, 'tenant', ?)");
        
        // Note: "issss" means Integer, String, String, String, String
        $stmt->bind_param("issss", $new_tenant_id, $fullname, $email, $password, $room_assigned);
        
        if($stmt->execute()){
            // Automatically mark room as occupied if one was assigned
            if(!empty($room_assigned)) {
                $conn->query("UPDATE rooms SET status='occupied' WHERE room_no='$room_assigned'");
            }
            
            // Display the new 5-digit ID in the success message
            $msg = "New tenant added successfully! ID: #" . $new_tenant_id;
            $msg_type = "success";
        } else {
            $msg = "Error adding tenant.";
            $msg_type = "danger";
        }
    }
}

// --- 2. UPDATE TENANT ---
if(isset($_POST['update_tenant'])){
    $id = intval($_POST['tenant_id']);
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $room_assigned = empty($_POST['room_assigned']) ? NULL : $_POST['room_assigned'];
    $new_password = $_POST['password'];

    // SMART LOGIC PRE-CHECK: Get the tenant's OLD room before we change it
    $old_room = null;
    $get_old = $conn->query("SELECT room_assigned FROM users WHERE id=$id");
    if($get_old->num_rows > 0) {
        $old_room = $get_old->fetch_assoc()['room_assigned'];
    }

    if(!empty($new_password)){
        $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, room_assigned=?, password=? WHERE id=?");
        $stmt->bind_param("ssssi", $fullname, $email, $room_assigned, $new_password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET fullname=?, email=?, room_assigned=? WHERE id=?");
        $stmt->bind_param("sssi", $fullname, $email, $room_assigned, $id);
    }

    if($stmt->execute()){
        // SMART LOGIC POST-CHECK: Did the tenant change rooms?
        if($old_room !== $room_assigned) {
            
            // 1. Mark the NEW room as occupied
            if(!empty($room_assigned)) {
                $conn->query("UPDATE rooms SET status='occupied' WHERE room_no='$room_assigned'");
            }
            
            // 2. Check if the OLD room is now completely empty
            if(!empty($old_room)) {
                $check_old = $conn->query("SELECT COUNT(*) as total_occupants FROM users WHERE room_assigned='$old_room' AND role='tenant'");
                $old_data = $check_old->fetch_assoc();
                
                // If nobody is left in the old room, make it available
                if($old_data['total_occupants'] == 0) {
                    $conn->query("UPDATE rooms SET status='available' WHERE room_no='$old_room'");
                }
            }
        }
        $msg = "Tenant updated successfully!";
        $msg_type = "success";
    } else {
        $msg = "Error updating tenant.";
        $msg_type = "danger";
    }
}

// --- 3. DELETE TENANT ---
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']); // intval() adds security
    
    // STEP 1: Find out what room this tenant is in BEFORE deleting them
    $room_assigned = null;
    $get_room = $conn->query("SELECT room_assigned FROM users WHERE id=$id");
    if($get_room->num_rows > 0) {
        $row = $get_room->fetch_assoc();
        $room_assigned = $row['room_assigned'];
    }

    // STEP 2: Delete the tenant from the database
    $conn->query("DELETE FROM users WHERE id=$id");
    
    // STEP 3: If they were assigned to a room, check if the room is now empty
    if(!empty($room_assigned)) {
        // Count how many tenants are STILL in this specific room
        $check_room = $conn->query("SELECT COUNT(*) as total_occupants FROM users WHERE room_assigned='$room_assigned' AND role='tenant'");
        $room_data = $check_room->fetch_assoc();
        
        // STEP 4: If 0 people are left, set the room to 'available'
        if($room_data['total_occupants'] == 0) {
            $conn->query("UPDATE rooms SET status='available' WHERE room_no='$room_assigned'");
        }
    }

    // Refresh the page
    header("Location: manage_tenants.php?msg=deleted");
    exit();
}

if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'){
    $msg = "Tenant removed from the system.";
    $msg_type = "success";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>Manage Tenants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-light d-flex flex-column h-100" style="overflow: hidden;">
    
    <nav class="navbar navbar-expand-lg navbar-custom px-3 py-3 shadow-sm d-flex justify-content-between flex-nowrap" style="z-index: 1000;">
        <div class="d-flex align-items-center gap-2" style="min-width: 0;"> 
            <button class="btn btn-outline-secondary d-lg-none flex-shrink-0" id="sidebarToggle">
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
        <div class="sidebar p-3 flex-shrink-0 d-flex flex-column gap-2" style="width: 250px; min-height: 100vh; overflow-y: auto;">
            <h4 class="text-center mb-4 mt-2 flex-shrink-0">System Admin</h4>
            <a href="dashboard.php" class="nav-dashboard"><i class="fa fa-home me-2"></i> Dashboard</a>
            <a href="manage_tenants.php" class="nav-tenants active"><i class="fa fa-users me-2"></i> Manage Tenants</a>
            <a href="manage_rooms.php" class="nav-rooms"><i class="fa fa-bed me-2"></i> Manage Rooms</a>
            <a href="billing.php" class="nav-billing"><i class="fa fa-file-invoice-dollar me-2"></i> Billing</a>
            <a href="manage_requests.php" class="nav-requests"><i class="fa fa-wrench me-2"></i> Manage Requests</a>
            <a href="talk.php" class="nav-talk"><i class="fa fa-comments me-2"></i> Chat Support</a>
            <a href="manage_admins.php" class="nav-admins"><i class="fa fa-user-shield me-2"></i> Manage Admins</a>
        </div>

        <div class="flex-grow-1 p-4" style="overflow-y: auto;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary-custom">Manage Tenants</h2>
                <button class="btn bg-primary-custom text-white" data-bs-toggle="modal" data-bs-target="#addTenantModal">
                    <i class="fa fa-plus-circle me-2"></i> Add New Tenant
                </button>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show">
                    <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card card-custom border-0 shadow-sm overflow-hidden" style="border-radius: 15px;">
                <div class="card-body p-0"> 
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-4 py-3">ID</th>
                                    <th class="py-3">Tenant Name</th>
                                    <th class="py-3">Email Address</th>
                                    <th class="py-3">Room Assigned</th>
                                    <th class="text-center py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $tenants = $conn->query("SELECT * FROM users WHERE role='tenant' ORDER BY id DESC");
                                if($tenants->num_rows > 0) {
                                    while($row = $tenants->fetch_assoc()){
                                ?>
                                <tr>
                                    <td class="ps-4 text-secondary">#<?php echo $row['id']; ?></td>
                                    <td class="fw-bold"><?php echo $row['fullname']; ?></td>
                                    <td class="text-muted"><?php echo $row['email']; ?></td>
                                    <td>
                                        <?php if($row['room_assigned']): ?>
                                            <span class="badge bg-info text-dark">Room <?php echo $row['room_assigned']; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-outline-primary me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editTenantModal"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-name="<?php echo $row['fullname']; ?>"
                                            data-email="<?php echo $row['email']; ?>"
                                            data-room="<?php echo $row['room_assigned']; ?>">
                                            <i class="fa fa-pencil-alt"></i>
                                        </button>
                                        <a href="manage_tenants.php?delete=<?php echo $row['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this tenant?');">
                                            <i class="fa fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php 
                                    } 
                                } else {
                                    echo "<tr><td colspan='5' class='text-center py-4 text-muted'>No tenants found.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="addTenantModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary-custom text-white">
                    <h5 class="modal-title">Add New Tenant</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="fullname" class="form-control" required placeholder="Tenant Name">
                        </div>
                        <div class="mb-3">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control" required placeholder="tenant@example.com">
                        </div>
                        <div class="mb-3">
                            <label>Assign Room (Optional)</label>
                            <select name="room_assigned" class="form-select">
                                <option value="">-- No Room Assigned --</option>
                                <?php
                                $rooms = $conn->query("SELECT room_no FROM rooms WHERE status='available'");
                                while($r = $rooms->fetch_assoc()){
                                    echo "<option value='".$r['room_no']."'>Room ".$r['room_no']."</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Default Password</label>
                            <input type="text" name="password" class="form-control" value="tenant123" required>
                            <small class="text-muted">They can change this later.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="add_tenant" class="btn bg-primary-custom text-white">Save Tenant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editTenantModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary-custom text-white">
                    <h5 class="modal-title">Edit Tenant Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="tenant_id" id="edit_id">
                        <div class="mb-3">
                            <label>Full Name</label>
                            <input type="text" name="fullname" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Email Address</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Assign Room</label>
                            <select name="room_assigned" id="edit_room" class="form-select">
                            <option value="">-- No Room Assigned --</option>
                            <?php
                            $all_rooms = $conn->query("SELECT room_no, capacity, (SELECT COUNT(*) FROM users WHERE room_assigned=rooms.room_no AND role='tenant') as occupants FROM rooms");
                            while($r = $all_rooms->fetch_assoc()){
                                $status_label = ($r['occupants'] >= $r['capacity']) ? " (FULL)" : " (".$r['occupants']."/".$r['capacity'].")";
                                echo "<option value='".$r['room_no']."'>Room ".$r['room_no'].$status_label."</option>";
                            }
                            ?>
                        </select>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label>Change Password <small class="text-muted">(Leave blank to keep current)</small></label>
                            <input type="password" name="password" class="form-control" placeholder="New Password">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_tenant" class="btn bg-primary-custom text-white">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/darkmode.js"></script>
<script src="../assets/js/sidebar.js"></script>
<script>
    // Fills the Edit Modal with the specific tenant's data
    var editModal = document.getElementById('editTenantModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        
        document.getElementById('edit_id').value = button.getAttribute('data-id');
        document.getElementById('edit_name').value = button.getAttribute('data-name');
        document.getElementById('edit_email').value = button.getAttribute('data-email');
        document.getElementById('edit_room').value = button.getAttribute('data-room');
    });
</script>
</body>
</html>