<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('tenant');

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

if(isset($_POST['update_profile'])){
    $fullname = $_POST['fullname'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact = $_POST['contact_number'];
    $email = $_POST['email'];
    $curr_addr = $_POST['current_address'];
    $perm_addr = $_POST['permanent_address'];
    $em_name = $_POST['emergency_name'];
    $em_rel = $_POST['emergency_relationship'];
    $em_phone = $_POST['emergency_phone'];

    $new_image = $_POST['old_image']; 

    if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0){
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if(in_array($ext, $allowed)){
            $unique_name = "profile_" . $user_id . "_" . time() . "." . $ext;
            $destination = "../assets/uploads/" . $unique_name;
            if(move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)){
                $new_image = $unique_name;
            } else {
                $msg = "Error uploading image."; $msg_type = "warning";
            }
        } else {
            $msg = "Invalid file type."; $msg_type = "danger";
        }
    }

    $sql = "UPDATE users SET fullname=?, dob=?, gender=?, contact_number=?, email=?, current_address=?, permanent_address=?, emergency_name=?, emergency_relationship=?, emergency_phone=?, profile_image=? WHERE id=?";     
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssi", $fullname, $dob, $gender, $contact, $email, $curr_addr, $perm_addr, $em_name, $em_rel, $em_phone, $new_image, $user_id);
    
    if($stmt->execute()){
        $msg = "Profile updated successfully!"; $msg_type = "success";
        $_SESSION['name'] = $fullname;
    } else {
        $msg = "Update failed: " . $conn->error; $msg_type = "danger";
    }
}

$sql = "SELECT users.*, rooms.price as rent_amount FROM users LEFT JOIN rooms ON users.room_assigned = rooms.room_no WHERE users.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$img_path = "../assets/uploads/" . ($user['profile_image'] ? $user['profile_image'] : 'default.png');
if(!file_exists($img_path)) { $img_path = "https://via.placeholder.com/150?text=No+Image"; }

$sql_balance = "SELECT SUM(amount) as debt FROM payments WHERE tenant_id = ? AND status = 'pending'";
$stmt_b = $conn->prepare($sql_balance);
$stmt_b->bind_param("i", $user_id);
$stmt_b->execute();
$balance = $stmt_b->get_result()->fetch_assoc()['debt'];
$balance = $balance ? $balance : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-header { color: #3d5a80; font-weight: 700; margin-bottom: 20px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; font-size: 1.1rem; }
        .label-text { font-size: 0.85rem; color: #6c757d; font-weight: 600; margin-bottom: 2px; }
        .info-text { font-size: 1rem; color: #212529; margin-bottom: 20px; font-weight: 500; }
        
        .profile-img-container {
            width: 150px; height: 150px; margin: 0 auto 30px auto;
            border-radius: 50%; overflow: hidden; border: 4px solid #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .profile-img-container img { width: 100%; height: 100%; object-fit: cover; }

        body.dark-mode .profile-header { 
            color: #60a5fa !important; 
            border-bottom-color: #334155;
        }
        body.dark-mode .label-text { 
            color: #94a3b8 !important;
        }
        body.dark-mode .info-text { 
            color: #ffffff !important;
        }
        body.dark-mode .profile-img-container {
            border-color: #1e293b;
        }
    </style>
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
            <a href="dashboard.php"><i class="fa fa-home me-2"></i> Dashboard</a>
            <a href="profile.php" class="active"><i class="fa fa-user me-2"></i> My Profile</a>
            <a href="payments.php"><i class="fa fa-credit-card me-2"></i> Billing</a>
            <a href="talk.php"><i class="fa fa-comments me-2"></i> Chat Admin</a>
        </div>

        <div class="flex-grow-1 p-5 bg-light" style="overflow-y: auto;">
            
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold text-dark">My Profile</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fa fa-pencil-alt me-1"></i> Edit Profile
                </button>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <div class="card card-custom p-5 shadow-sm border-0">
                <div class="profile-img-container">
                    <img src="<?php echo $img_path; ?>" alt="Profile Picture">
                </div>

                <div class="row">
                    <div class="col-md-4 border-end">
                        <h5 class="profile-header">Basic Information</h5>
                        <div class="label-text">Full Name</div>
                        <div class="info-text"><?php echo htmlspecialchars($user['fullname']); ?></div>
                        <div class="label-text">Date of Birth</div>
                        <div class="info-text"><?php echo $user['dob'] ? date('M d, Y', strtotime($user['dob'])) : '-'; ?></div>
                        <div class="label-text">Gender</div>
                        <div class="info-text"><?php echo $user['gender'] ? $user['gender'] : '-'; ?></div>
                        <div class="label-text">Contact Number</div>
                        <div class="info-text"><?php echo $user['contact_number'] ? $user['contact_number'] : '-'; ?></div>
                        <div class="label-text">Email Address</div>
                        <div class="info-text"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>

                    <div class="col-md-4 border-end">
                        <h5 class="profile-header">Address Information</h5>
                        <div class="label-text">Current Address</div>
                        <div class="info-text"><?php echo $user['current_address'] ? $user['current_address'] : '-'; ?></div>
                        <div class="label-text">Permanent Address</div>
                        <div class="info-text"><?php echo $user['permanent_address'] ? $user['permanent_address'] : '-'; ?></div>

                        <div class="mt-4 border-top pt-3">
                            <div class="label-text text-uppercase mb-2 text-primary">Emergency Contact</div>
                            <div class="mb-2"><span class="label-text">Name:</span> <span class="info-text fw-bold"><?php echo $user['emergency_name'] ?? '-'; ?></span></div>
                            <div class="mb-2"><span class="label-text">Relation:</span> <span class="info-text"><?php echo $user['emergency_relationship'] ?? '-'; ?></span></div>
                            <div class="mb-2"><span class="label-text">Phone:</span> <span class="info-text"><?php echo $user['emergency_phone'] ?? '-'; ?></span></div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <h5 class="profile-header">Tenancy Details</h5>
                        <div class="label-text">Room</div>
                        <div class="info-text text-primary fw-bold"><?php echo $user['room_assigned'] ?? 'Unassigned'; ?></div>
                        <div class="label-text">Rent</div>
                        <div class="info-text">Php. <?php echo number_format($user['rent_amount'] ?? 0, 2); ?></div>
                        <div class="label-text">Balance</div>
                        <div class="info-text fw-bold <?php echo ($balance > 0) ? 'text-danger' : 'text-success'; ?>">
                            Php. <?php echo number_format($balance, 2); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary-custom text-white">
                <h5 class="modal-title">Edit Profile Information</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="old_image" value="<?php echo $user['profile_image']; ?>">
                    
                    <h6 class="text-primary-custom border-bottom pb-2 mb-3">1. Profile Picture & Basic Info</h6>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="small text-muted">Upload New Picture</label>
                            <input type="file" name="profile_pic" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Full Name</label>
                            <input type="text" name="fullname" class="form-control" value="<?php echo $user['fullname']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" class="form-control" value="<?php echo $user['dob']; ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Gender</label>
                            <select name="gender" class="form-select">
                                <option value="Male" <?php if($user['gender']=='Male') echo 'selected'; ?>>Male</option>
                                <option value="Female" <?php if($user['gender']=='Female') echo 'selected'; ?>>Female</option>
                                <option value="Other" <?php if($user['gender']=='Other') echo 'selected'; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" class="form-control" value="<?php echo $user['contact_number']; ?>">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Email (Login ID)</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                        </div>
                    </div>

                    <h6 class="text-primary-custom border-bottom pb-2 mb-3 mt-3">2. Address Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Current Address</label>
                            <textarea name="current_address" class="form-control" rows="2"><?php echo $user['current_address']; ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Permanent Address</label>
                            <textarea name="permanent_address" class="form-control" rows="2"><?php echo $user['permanent_address']; ?></textarea>
                        </div>
                    </div>

                    <h6 class="text-primary-custom border-bottom pb-2 mb-3 mt-3">3. Emergency Contact</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Name</label>
                            <input type="text" name="emergency_name" class="form-control" value="<?php echo $user['emergency_name']; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Relationship</label>
                            <input type="text" name="emergency_relationship" class="form-control" value="<?php echo $user['emergency_relationship']; ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Phone</label>
                            <input type="text" name="emergency_phone" class="form-control" value="<?php echo $user['emergency_phone']; ?>">
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_profile" class="btn bg-primary-custom text-white">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="../assets/js/sidebar.js"></script>
<script src="../assets/js/darkmode.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>