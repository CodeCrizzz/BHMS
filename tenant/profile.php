<?php
include '../includes/db.php';
require_once '../includes/auth_check.php';
checkLogin('tenant');

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

if (isset($_POST['update_profile'])) {
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

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            $unique_name = "profile_" . $user_id . "_" . time() . "." . $ext;
            $destination = "../assets/uploads/" . $unique_name;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $destination)) {
                $new_image = $unique_name;
            } else {
                $msg = "Error uploading image.";
                $msg_type = "warning";
            }
        } else {
            $msg = "Invalid file type.";
            $msg_type = "danger";
        }
    }

    $sql = "UPDATE users SET fullname=?, dob=?, gender=?, contact_number=?, email=?, current_address=?, permanent_address=?, emergency_name=?, emergency_relationship=?, emergency_phone=?, profile_image=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssssi", $fullname, $dob, $gender, $contact, $email, $curr_addr, $perm_addr, $em_name, $em_rel, $em_phone, $new_image, $user_id);

    if ($stmt->execute()) {
        $msg = "Profile updated successfully!";
        $msg_type = "success";
        $_SESSION['name'] = $fullname;
    } else {
        $msg = "Update failed: " . $conn->error;
        $msg_type = "danger";
    }
}

$sql = "SELECT users.*, rooms.price as rent_amount FROM users LEFT JOIN rooms ON users.room_assigned = rooms.room_no WHERE users.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$img_path = "../assets/uploads/" . ($user['profile_image'] ? $user['profile_image'] : 'default.png');
if (!file_exists($img_path)) {
    $img_path = "https://via.placeholder.com/150?text=No+Image";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Tenant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                $sidebar_req_query = $conn->query("SELECT COUNT(id) AS total FROM requests WHERE tenant_id = $user_id AND status IN ('In Progress', 'Resolved')");
                $sidebar_req_count = $sidebar_req_query ? $sidebar_req_query->fetch_assoc()['total'] : 0;
                if ($sidebar_req_count > 0):
                ?>
                    <span class="badge bg-warning text-dark rounded-pill shadow-sm" style="font-size: 0.7rem; padding: 4px 8px;"><?php echo $sidebar_req_count; ?></span>
                <?php endif; ?>
            </a>
            <a href="talk.php" class="d-flex justify-content-between align-items-center <?php echo (basename($_SERVER['PHP_SELF']) == 'talk.php') ? 'active' : ''; ?>"><span><i class="fa fa-comments me-2"></i> Chat Admin</span><span id="sidebar-chat-container"></span></a>
        </div>

        <div class="flex-grow-1 p-4 d-flex flex-column" style="overflow-y: auto;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0">Account Settings</h2>
                <button class="btn btn-primary bg-primary-custom border-0 rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="fa fa-pen me-2"></i>Update Profile
                </button>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msg_type; ?> border-0 rounded-4 shadow-sm"><?php echo $msg; ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm text-center p-4" style="border-radius: 20px;">
                        <img src="<?php echo $img_path; ?>" class="rounded-circle shadow-sm mx-auto border border-4 border-white" style="width: 150px; height: 150px; object-fit: cover;">
                        <h4 class="fw-bold mt-3 mb-1"><?php echo htmlspecialchars($user['fullname']); ?></h4>
                        <p class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div class="bg-light p-3 rounded-4 mt-3 text-start">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Unit Assigned</span>
                                <span class="fw-bold"><?php echo $user['room_assigned'] ?? 'N/A'; ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Account Status</span>
                                <span class="badge bg-success rounded-pill px-3">Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 20px;">
                        <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Personal Information</h6>
                        <div class="row small g-3">
                            <div class="col-md-6"><label class="text-muted d-block mb-1">Birth Date</label>
                                <div class="fw-bold fs-6"><?php echo $user['dob'] ? date('M d, Y', strtotime($user['dob'])) : '-'; ?></div>
                            </div>
                            <div class="col-md-6"><label class="text-muted d-block mb-1">Gender</label>
                                <div class="fw-bold fs-6"><?php echo $user['gender'] ?? '-'; ?></div>
                            </div>
                            <div class="col-md-6"><label class="text-muted d-block mb-1">Phone Number</label>
                                <div class="fw-bold fs-6"><?php echo $user['contact_number'] ?? '-'; ?></div>
                            </div>
                            <div class="col-md-6"><label class="text-muted d-block mb-1">Current Address</label>
                                <div class="fw-bold fs-6"><?php echo $user['current_address'] ?? '-'; ?></div>
                            </div>
                            <div class="col-md-12"><label class="text-muted d-block mb-1">Permanent Address</label>
                                <div class="fw-bold fs-6"><?php echo $user['permanent_address'] ?? '-'; ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm p-4" style="border-radius: 20px;">
                        <h6 class="fw-bold text-danger border-bottom pb-2 mb-3">Emergency Contact</h6>
                        <div class="row small g-3">
                            <div class="col-md-4"><label class="text-muted d-block mb-1">Contact Person</label>
                                <div class="fw-bold fs-6"><?php echo $user['emergency_name'] ?? '-'; ?></div>
                            </div>
                            <div class="col-md-4"><label class="text-muted d-block mb-1">Relationship</label>
                                <div class="fw-bold fs-6"><?php echo $user['emergency_relationship'] ?? '-'; ?></div>
                            </div>
                            <div class="col-md-4"><label class="text-muted d-block mb-1">Phone Number</label>
                                <div class="fw-bold fs-6"><?php echo $user['emergency_phone'] ?? '-'; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editProfileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0" style="border-radius: 20px;">
                <div class="modal-header border-0 bg-light rounded-top-4">
                    <h5 class="modal-title fw-bold text-primary">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <input type="hidden" name="old_image" value="<?php echo $user['profile_image']; ?>">

                        <h6 class="text-muted small fw-bold text-uppercase mb-3">Basic Info</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-12"><label class="form-label small">Profile Picture</label><input type="file" name="profile_pic" class="form-control bg-light" accept="image/*"></div>
                            <div class="col-md-6"><label class="form-label small">Full Name</label><input type="text" name="fullname" class="form-control bg-light" value="<?php echo $user['fullname']; ?>" required></div>
                            <div class="col-md-6"><label class="form-label small">Email Address</label><input type="email" name="email" class="form-control bg-light" value="<?php echo $user['email']; ?>" required></div>
                            <div class="col-md-4"><label class="form-label small">Date of Birth</label><input type="date" name="dob" class="form-control bg-light" value="<?php echo $user['dob']; ?>"></div>
                            <div class="col-md-4"><label class="form-label small">Gender</label>
                                <select name="gender" class="form-select bg-light">
                                    <option value="Male" <?php if ($user['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                                    <option value="Female" <?php if ($user['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label small">Phone</label><input type="text" name="contact_number" class="form-control bg-light" value="<?php echo $user['contact_number']; ?>"></div>
                        </div>

                        <h6 class="text-muted small fw-bold text-uppercase mb-3">Addresses</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6"><label class="form-label small">Current Address</label><textarea name="current_address" class="form-control bg-light" rows="2"><?php echo $user['current_address']; ?></textarea></div>
                            <div class="col-md-6"><label class="form-label small">Permanent Address</label><textarea name="permanent_address" class="form-control bg-light" rows="2"><?php echo $user['permanent_address']; ?></textarea></div>
                        </div>

                        <h6 class="text-muted small fw-bold text-uppercase mb-3">Emergency Contact</h6>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label small">Name</label><input type="text" name="emergency_name" class="form-control bg-light" value="<?php echo $user['emergency_name']; ?>"></div>
                            <div class="col-md-4"><label class="form-label small">Relationship</label><input type="text" name="emergency_relationship" class="form-control bg-light" value="<?php echo $user['emergency_relationship']; ?>"></div>
                            <div class="col-md-4"><label class="form-label small">Phone</label><input type="text" name="emergency_phone" class="form-control bg-light" value="<?php echo $user['emergency_phone']; ?>"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light rounded-bottom-4">
                        <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-primary bg-primary-custom rounded-pill px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="../assets/js/get_notification.js"></script>
    <script src="../assets/js/sidebar.js"></script>
    <script src="../assets/js/darkmode.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>