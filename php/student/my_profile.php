<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit();
}

$logged_user_id = $_SESSION['user_id'];
$message = "";

// Handle Form Submission for Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $full_name  = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email      = mysqli_real_escape_string($conn, $_POST['email']);
    $phone      = mysqli_real_escape_string($conn, $_POST['phone']);
    $dept       = mysqli_real_escape_string($conn, $_POST['department']);
    $semester   = mysqli_real_escape_string($conn, $_POST['semester']);
    $batch      = mysqli_real_escape_string($conn, $_POST['batch']);
    $student_no = mysqli_real_escape_string($conn, $_POST['student_no']);

    // 1. Update 'users' table
    $update_users = "UPDATE users 
                    SET full_name = '$full_name', 
                        student_id = '$student_no', 
                        department = '$dept' 
                    WHERE user_id = '$logged_user_id'";
    mysqli_query($conn, $update_users);

    // 2. Check and Update/Insert in 'students' table
    $check_student = mysqli_query($conn, "SELECT * FROM students WHERE user_id = '$logged_user_id'");
    
    if (mysqli_num_rows($check_student) > 0) {
        $update_students = "UPDATE students 
                            SET full_name = '$full_name', 
                                email = '$email', 
                                phone = '$phone', 
                                department = '$dept', 
                                semester = '$semester', 
                                batch = '$batch', 
                                student_no = '$student_no' 
                            WHERE user_id = '$logged_user_id'";
        mysqli_query($conn, $update_students);
    } else {
        $insert_student = "INSERT INTO students (user_id, student_no, full_name, email, phone, department, semester, batch) 
                           VALUES ('$logged_user_id', '$student_no', '$full_name', '$email', '$phone', '$dept', '$semester', '$batch')";
        mysqli_query($conn, $insert_student);
    }

    $_SESSION['full_name'] = $full_name;
    $message = "Profile updated successfully!";
}

// Fetch Latest Profile Data
$query = "SELECT u.user_id, u.username, u.role, u.account_status, u.full_name as user_full_name, u.student_id as user_student_no,
                 s.student_no, s.full_name as student_full_name, s.email, s.phone, s.department, s.semester, s.batch 
          FROM users u 
          LEFT JOIN students s ON u.user_id = s.user_id 
          WHERE u.user_id = '$logged_user_id'";

$result = mysqli_query($conn, $query);
$profile = mysqli_fetch_assoc($result);

$display_name = !empty($profile['student_full_name']) ? $profile['student_full_name'] : (!empty($profile['user_full_name']) ? $profile['user_full_name'] : $profile['username']);
$student_id_val = !empty($profile['student_no']) ? $profile['student_no'] : ($profile['user_student_no'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - BUP Counselling</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f2f7f4; color: #333; }

        .sidebar { width: 260px; height: 100vh; background-color: #081c15; color: #fff; position: fixed; display: flex; flex-direction: column; justify-content: space-between; padding: 20px 0; }
        .sidebar .brand { padding: 0 20px; font-size: 16px; font-weight: bold; margin-bottom: 25px; color: #52b788; display: flex; align-items: center; gap: 10px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li a { display: flex; align-items: center; padding: 12px 25px; color: #d1e0d7; text-decoration: none; font-size: 13px; transition: 0.3s; }
        .sidebar ul li a i { margin-right: 12px; width: 18px; }
        .sidebar ul li.active a, .sidebar ul li a:hover { background-color: #1b4332; color: #fff; border-left: 4px solid #52b788; }
        .sidebar-footer { padding: 20px; font-size: 11px; color: #74a892; border-top: 1px solid #1b4332; }

        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 25px 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .search-bar { background: #fff; padding: 8px 15px; border-radius: 20px; border: 1px solid #e0f0e6; width: 300px; }
        .search-bar input { border: none; outline: none; margin-left: 8px; width: 80%; font-size: 13px; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-profile .avatar { width: 38px; height: 38px; background: #2d6a4f; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }

        .profile-card { background: #fff; border-radius: 15px; border: 1px solid #e0f0e6; box-shadow: 0 4px 10px rgba(0,0,0,0.02); padding: 30px; margin-bottom: 25px; }
        .profile-header { display: flex; align-items: center; gap: 20px; padding-bottom: 25px; border-bottom: 1px solid #e5ece7; margin-bottom: 25px; }
        .profile-avatar-large { width: 80px; height: 80px; background: #1b4332; color: #52b788; font-size: 28px; font-weight: bold; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid #d8f3dc; }
        .profile-title h2 { font-size: 20px; color: #081c15; margin-bottom: 5px; }
        .profile-title p { font-size: 13px; color: #52b788; font-weight: 600; }
        .badge-status { display: inline-block; background: #dcfce7; color: #16a34a; padding: 3px 10px; border-radius: 12px; font-size: 11px; margin-top: 5px; font-weight: bold; }

        .alert-success { background: #d1e7dd; color: #0f5132; padding: 12px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }

        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .info-item { background: #f8faf9; padding: 15px 20px; border-radius: 10px; border: 1px solid #e5ece7; }
        .info-item label { display: block; font-size: 11px; color: #74a892; text-transform: uppercase; font-weight: 700; margin-bottom: 6px; }
        .info-item input { width: 100%; border: 1px solid #c2e0d1; background: #fff; padding: 8px 12px; border-radius: 6px; font-size: 14px; color: #081c15; font-weight: 600; outline: none; }
        .info-item input:focus { border-color: #2d6a4f; box-shadow: 0 0 5px rgba(45,106,79,0.2); }
        .info-item input[readonly] { background: #e9f0ec; color: #666; cursor: not-allowed; border-color: #d1e0d7; }

        .btn-submit { background: #2d6a4f; color: white; border: none; padding: 12px 25px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; margin-top: 20px; transition: 0.3s; }
        .btn-submit:hover { background: #1b4332; }

        .footer-banner { background: linear-gradient(90deg, #1b4332 0%, #2d6a4f 100%); color: white; padding: 15px 25px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; font-size: 12px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="brand"><i class="fa-solid fa-heart-pulse"></i> Counselling & Wellness</div>
            <ul>
                <li><a href="student_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li class="active"><a href="my_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="counsellors.php"><i class="fa-solid fa-user-doctor"></i> Counsellors</a></li>
                <li><a href="appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
                <li><a href="myworkshop.php"><i class="fa-solid fa-graduation-cap"></i> Workshops</a></li>
                <li><a href="my_enrollments.php"><i class="fa-solid fa-book-open"></i> My Enrollments</a></li>
                <li><a href="counselling_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Counselling History</a></li>
                <li><a href="my_feedback.php"><i class="fa-solid fa-comment-dots"></i> Feedback</a></li>
                <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <strong>Bangladesh University of Professionals</strong><br>"A Safer, Healthier, Stronger BUP"
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass" style="color: #2d6a4f;"></i>
                <input type="text" placeholder="Search anything...">
            </div>
            <div class="user-profile">
                <div class="avatar"><?php echo strtoupper(substr($display_name, 0, 2)); ?></div>
                <div>
                    <strong style="font-size: 13px;"><?php echo htmlspecialchars($display_name); ?></strong><br>
                    <small style="color: #52b788;">Student</small>
                </div>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $message; ?></div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar-large"><?php echo strtoupper(substr($display_name, 0, 2)); ?></div>
                <div class="profile-title">
                    <h2><?php echo htmlspecialchars($display_name); ?></h2>
                    <p><i class="fa-solid fa-id-card"></i> Student ID: <?php echo htmlspecialchars($student_id_val ?: 'N/A'); ?></p>
                    <span class="badge-status"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($profile['account_status'] ?? 'ACTIVE'); ?></span>
                </div>
            </div>

            <form method="POST" action="my_profile.php">
                <div class="info-grid">
                    <div class="info-item">
                        <label><i class="fa-solid fa-user-pen"></i> Full Name</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($display_name); ?>" required>
                    </div>

                    <div class="info-item">
                        <label><i class="fa-solid fa-id-badge"></i> Student ID</label>
                        <input type="text" name="student_no" value="<?php echo htmlspecialchars($student_id_val); ?>" placeholder="e.g. 24549010086">
                    </div>

                    <div class="info-item">
                        <label><i class="fa-solid fa-layer-group"></i> Batch</label>
                        <input type="text" name="batch" value="<?php echo htmlspecialchars($profile['batch'] ?? 'BICE-2024'); ?>">
                    </div>

                    <div class="info-item">
                        <label><i class="fa-solid fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" required>
                    </div>

                    <div class="info-item">
                        <label><i class="fa-solid fa-phone"></i> Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                    </div>

                    <div class="info-item">
                        <label><i class="fa-solid fa-building-columns"></i> Department</label>
                        <input type="text" name="department" value="<?php echo htmlspecialchars($profile['department'] ?? ''); ?>">
                    </div>

                    <div class="info-item">
                        <label><i class="fa-solid fa-graduation-cap"></i> Semester</label>
                        <input type="text" name="semester" value="<?php echo htmlspecialchars($profile['semester'] ?? ''); ?>">
                    </div>

                    <div class="info-item">
                        <label><i class="fa-solid fa-lock"></i> Username (Read-Only)</label>
                        <input type="text" value="<?php echo htmlspecialchars($profile['username'] ?? ''); ?>" readonly>
                    </div>
                </div>

                <button type="submit" name="update_profile" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk"></i> Save Changes
                </button>
            </form>
        </div>

        <div class="footer-banner">
            <span><strong>Care · Support · Grow</strong></span>
            <span>Bangladesh University of Professionals</span>
        </div>
    </div>

</body>
</html>