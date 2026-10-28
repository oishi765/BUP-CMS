<?php
session_start();
require_once "../config/db.php";

// Check if faculty is logged in
if (!isset($_SESSION['faculty_user']) || $_SESSION['role'] !== 'FACULTY') {
    header("Location: ../authorization/login.php");
    exit();
}

$faculty_id = $_SESSION['faculty_id'] ?? 0;
$user_id = $_SESSION['user_id'] ?? 0;
$success_msg = "";
$error_msg = "";

// Handle Profile Update
if (isset($_POST['update_profile'])) {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $designation = mysqli_real_escape_string($conn, $_POST['designation']);

    // Update faculty table
    $update_faculty = mysqli_query($conn, "UPDATE faculty SET full_name='$full_name', email='$email', phone='$phone', department='$department', designation='$designation' WHERE faculty_id='$faculty_id'");
    
    // Update users table full_name as well
    $update_user = mysqli_query($conn, "UPDATE users SET full_name='$full_name' WHERE user_id='$user_id'");

    if ($update_faculty && $update_user) {
        $_SESSION['faculty_name'] = $full_name;
        $_SESSION['department'] = $department;
        $success_msg = "Profile updated successfully!";
    } else {
        $error_msg = "Failed to update profile. Please try again.";
    }
}

// Fetch latest faculty details from database
$query = mysqli_query($conn, "SELECT f.*, u.username FROM faculty f JOIN users u ON f.user_id = u.user_id WHERE f.faculty_id = '$faculty_id'");
$faculty = mysqli_fetch_assoc($query);

$faculty_name = $faculty['full_name'] ?? ($_SESSION['faculty_name'] ?? 'Faculty Member');
$department = $faculty['department'] ?? ($_SESSION['department'] ?? 'Department');
$designation = $faculty['designation'] ?? 'Faculty';
$faculty_no = $faculty['faculty_no'] ?? 'N/A';
$email = $faculty['email'] ?? 'N/A';
$phone = $faculty['phone'] ?? 'N/A';
$username = $faculty['username'] ?? ($_SESSION['faculty_user'] ?? 'N/A');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Profile - BUP Counselling & Wellness</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f4f7f6;
            color: #333;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 260px;
            background-color: #0b3c29;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 20px 0;
            position: fixed;
            height: 100%;
            left: 0;
            top: 0;
        }

        .sidebar-top .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 20px 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-top .brand img {
            width: 38px;
            height: auto;
            object-fit: contain;
        }

        .sidebar-top .brand h2 {
            font-size: 15px;
            font-weight: 600;
            line-height: 1.2;
            color: #fff;
        }

        .nav-links {
            list-style: none;
            padding: 20px 0;
            overflow-y: auto;
            flex-grow: 1;
        }

        .nav-links li a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 25px;
            color: #b8d8ce;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .nav-links li a:hover, 
        .nav-links li a.active {
            background-color: #124e37;
            color: #fff;
            border-left: 4px solid #52b788;
        }

        .nav-links li a i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .sidebar-footer {
            padding: 15px 25px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 11px;
            color: #8abdb2;
            line-height: 1.4;
        }

        /* Main Content Area */
        .main-content {
            margin-left: 260px;
            flex-grow: 1;
            padding: 25px 35px;
            overflow-y: auto;
            height: 100vh;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-title h2 {
            font-size: 22px;
            color: #0b3c29;
        }

        .user-profile-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            background-color: #0b3c29;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }

        .user-info h4 {
            font-size: 13px;
            color: #222;
        }

        .user-info span {
            font-size: 11px;
            color: #666;
        }

        /* Profile Card Container */
        .profile-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .profile-user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-big-avatar {
            width: 75px;
            height: 75px;
            background-color: #0b3c29;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 600;
        }

        .profile-header-info h3 {
            font-size: 20px;
            color: #0b3c29;
            margin-bottom: 4px;
        }

        .profile-header-info p {
            font-size: 14px;
            color: #555;
        }

        .edit-btn {
            background: #0b3c29;
            color: #fff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
        }

        .edit-btn:hover {
            background: #124e37;
        }

        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .alert-error {
            background: #f8d7da;
            color: #842029;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .profile-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .detail-item {
            background: #f8faf9;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5ece7;
        }

        .detail-item label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .detail-item span {
            font-size: 15px;
            color: #1b4332;
            font-weight: 500;
        }

        .detail-item input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
        }

        .detail-item input:focus {
            border-color: #0b3c29;
            box-shadow: 0 0 0 2px rgba(11, 60, 41, 0.1);
        }

        .save-btn-container {
            margin-top: 25px;
            text-align: right;
        }

        .save-btn {
            background: #198754;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .save-btn:hover {
            background: #157347;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-top">
            <div class="brand">
                <img src="../../images/logo.png" alt="BUP Logo">
                <h2>Counselling & Wellness</h2>
            </div>
            <ul class="nav-links">
                <li><a href="faculty_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="faculty_profile.php" class="active"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="counsellors.php"><i class="fas fa-user-md"></i> Counsellors</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                <li><a href="workshops.php"><i class="fas fa-chalkboard-teacher"></i> Workshops</a></li>
                <li><a href="referrals.php"><i class="fas fa-share-nodes"></i> Student Referrals</a></li>
                <li><a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="../authorization/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            Bangladesh University of Professionals<br>"A Safer, Healthier, Stronger BUP"
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="page-title">
                <h2>Faculty Profile</h2>
            </div>
            <div class="user-profile-header">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($faculty_name, 0, 2)); ?>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($faculty_name); ?></h4>
                    <span>Faculty (<?php echo htmlspecialchars($department); ?>)</span>
                </div>
            </div>
        </div>

        <!-- Profile Card -->
        <div class="profile-card">
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert-success"><i class="fas fa-check-circle"></i> <?php echo $success_msg; ?></div>
            <?php endif; ?>

            <?php if (!empty($error_msg)): ?>
                <div class="alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="profile-header-section">
                    <div class="profile-user-info">
                        <div class="profile-big-avatar">
                            <?php echo strtoupper(substr($faculty_name, 0, 2)); ?>
                        </div>
                        <div class="profile-header-info">
                            <h3><?php echo htmlspecialchars($faculty_name); ?></h3>
                            <p><?php echo htmlspecialchars($designation); ?> • Department of <?php echo htmlspecialchars($department); ?></p>
                        </div>
                    </div>
                    <button type="button" class="edit-btn" id="editToggleBtn" onclick="toggleEditMode()"><i class="fas fa-pen"></i> Edit Profile</button>
                </div>

                <div class="profile-details-grid">
                    <div class="detail-item">
                        <label><i class="fas fa-id-badge"></i> Faculty ID</label>
                        <span><?php echo htmlspecialchars($faculty_no); ?></span>
                    </div>
                    <div class="detail-item">
                        <label><i class="fas fa-user-shield"></i> Username (Read-only)</label>
                        <span><?php echo htmlspecialchars($username); ?></span>
                    </div>
                    <div class="detail-item">
                        <label><i class="fas fa-user"></i> Full Name</label>
                        <span class="display-mode"><?php echo htmlspecialchars($faculty_name); ?></span>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($faculty_name); ?>" class="edit-mode" style="display:none;" required>
                    </div>
                    <div class="detail-item">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <span class="display-mode"><?php echo htmlspecialchars($email); ?></span>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="edit-mode" style="display:none;" required>
                    </div>
                    <div class="detail-item">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <span class="display-mode"><?php echo htmlspecialchars($phone); ?></span>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" class="edit-mode" style="display:none;" required>
                    </div>
                    <div class="detail-item">
                        <label><i class="fas fa-building"></i> Department</label>
                        <span class="display-mode"><?php echo htmlspecialchars($department); ?></span>
                        <input type="text" name="department" value="<?php echo htmlspecialchars($department); ?>" class="edit-mode" style="display:none;" required>
                    </div>
                    <div class="detail-item" style="grid-column: span 2;">
                        <label><i class="fas fa-briefcase"></i> Designation</label>
                        <span class="display-mode"><?php echo htmlspecialchars($designation); ?></span>
                        <input type="text" name="designation" value="<?php echo htmlspecialchars($designation); ?>" class="edit-mode" style="display:none;" required>
                    </div>
                </div>

                <div class="save-btn-container edit-mode" style="display:none;">
                    <button type="submit" name="update_profile" class="save-btn"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </form>
        </div>

    </div>

    <script>
        function toggleEditMode() {
            let displayElements = document.querySelectorAll('.display-mode');
            let editElements = document.querySelectorAll('.edit-mode');
            let editBtn = document.getElementById('editToggleBtn');

            displayElements.forEach(el => {
                el.style.display = el.style.display === 'none' ? 'inline' : 'none';
            });

            editElements.forEach(el => {
                el.style.display = el.style.display === 'none' ? 'block' : 'none';
            });

            if (editBtn.innerHTML.includes('Edit')) {
                editBtn.innerHTML = '<i class="fas fa-times"></i> Cancel';
                editBtn.style.background = '#dc3546';
            } else {
                editBtn.innerHTML = '<i class="fas fa-pen"></i> Edit Profile';
                editBtn.style.background = '#0b3c29';
            }
        }
    </script>
</body>
</html>