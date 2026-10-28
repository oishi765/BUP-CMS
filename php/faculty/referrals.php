<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['faculty_user']) || $_SESSION['role'] !== 'FACULTY') {
    header("Location: ../authorization/login.php");
    exit();
}

$faculty_name = $_SESSION['faculty_name'] ?? 'Faculty Member';
$department = $_SESSION['department'] ?? 'Department';
$faculty_id = $_SESSION['faculty_id'] ?? 0;
$msg = "";

// Handle Student Referral Submission
if (isset($_POST['submit_referral'])) {
    $student_no = mysqli_real_escape_string($conn, $_POST['student_no']);
    $counsellor_id = mysqli_real_escape_string($conn, $_POST['counsellor_id']);
    $reason = mysqli_real_escape_string($conn, $_POST['reason']);
    
    // Check if student exists
    $student_check = mysqli_query($conn, "SELECT student_id FROM students WHERE student_no = '$student_no'");
    if (mysqli_num_rows($student_check) > 0) {
        $student = mysqli_fetch_assoc($student_check);
        $student_id = $student['student_id'];
        
        $insert_ref = mysqli_query($conn, "INSERT INTO referrals (faculty_id, student_id, counsellor_id, reason, referral_date) VALUES ('$faculty_id', '$student_id', '$counsellor_id', '$reason', CURDATE())");
        
        if ($insert_ref) {
            $msg = "Student successfully referred to the counsellor!";
        } else {
            $msg = "Failed to submit referral: " . mysqli_error($conn);
        }
    } else {
        $msg = "Student ID not found in the system!";
    }
}

// Fetch counsellors list for dropdown
$counsellors_list = mysqli_query($conn, "SELECT * FROM counsellors");

// Fetch referrals made by this faculty including counsellor name
$referrals_query = mysqli_query($conn, "SELECT r.*, s.full_name as student_name, s.student_no, c.full_name as counsellor_name FROM referrals r JOIN students s ON r.student_id = s.student_id LEFT JOIN counsellors c ON r.counsellor_id = c.counsellor_id WHERE r.faculty_id = '$faculty_id' ORDER BY r.referral_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Referrals - BUP Counselling & Wellness</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f7f6; color: #333; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background-color: #0b3c29; color: #fff; display: flex; flex-direction: column; justify-content: space-between; padding: 20px 0; position: fixed; height: 100%; left: 0; top: 0; }
        .sidebar-top .brand { display: flex; align-items: center; gap: 12px; padding: 0 20px 20px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-top .brand img { width: 38px; height: auto; object-fit: contain; }
        .sidebar-top .brand h2 { font-size: 15px; font-weight: 600; color: #fff; }
        .nav-links { list-style: none; padding: 20px 0; overflow-y: auto; flex-grow: 1; }
        .nav-links li a { display: flex; align-items: center; gap: 15px; padding: 12px 25px; color: #b8d8ce; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .nav-links li a:hover, .nav-links li a.active { background-color: #124e37; color: #fff; border-left: 4px solid #52b788; }
        .nav-links li a i { font-size: 16px; width: 20px; text-align: center; }
        .sidebar-footer { padding: 15px 25px; border-top: 1px solid rgba(255, 255, 255, 0.1); font-size: 11px; color: #8abdb2; line-height: 1.4; }
        .main-content { margin-left: 260px; flex-grow: 1; padding: 25px 35px; overflow-y: auto; height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title h2 { font-size: 22px; color: #0b3c29; }
        .user-profile-header { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 38px; height: 38px; background-color: #0b3c29; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; }
        .user-info h4 { font-size: 13px; color: #222; }
        .user-info span { font-size: 11px; color: #666; }
        .referral-container { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; }
        .card-box { background: #fff; border-radius: 12px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04); padding: 25px; height: fit-content; }
        .card-box h3 { font-size: 16px; color: #0b3c29; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #444; margin-bottom: 5px; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 13px; outline: none; background: #f8faf9; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #0b3c29; background: #fff; box-shadow: 0 0 0 2px rgba(11, 60, 41, 0.1); }
        .submit-btn { background: #198754; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; transition: 0.3s; }
        .submit-btn:hover { background: #157347; }
        .alert-msg { background: #d1e7dd; color: #0f5132; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        th { background-color: #f8faf9; color: #0b3c29; padding: 12px; border-bottom: 2px solid #e5ece7; font-weight: 600; }
        td { padding: 12px; border-bottom: 1px solid #eee; color: #444; }
        tr:hover { background-color: #fafbfc; }
        .no-data { text-align: center; padding: 30px; color: #666; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-top">
            <div class="brand">
                <img src="../../images/logo.png" alt="BUP Logo">
                <h2>Counselling & Wellness</h2>
            </div>
            <ul class="nav-links">
                <li><a href="faculty_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="faculty_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="counsellors.php"><i class="fas fa-user-md"></i> Counsellors</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                <li><a href="workshops.php"><i class="fas fa-chalkboard-teacher"></i> Workshops</a></li>
                <li><a href="referrals.php" class="active"><i class="fas fa-share-nodes"></i> Student Referrals</a></li>
                <li><a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="../authorization/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">Bangladesh University of Professionals<br>"A Safer, Healthier, Stronger BUP"</div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title"><h2>Student Referrals for Counselling</h2></div>
            <div class="user-profile-header">
                <div class="user-avatar"><?php echo strtoupper(substr($faculty_name, 0, 2)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($faculty_name); ?></h4>
                    <span>Faculty (<?php echo htmlspecialchars($department); ?>)</span>
                </div>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert-msg"><i class="fas fa-info-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="referral-container">
            <div class="card-box">
                <h3><i class="fas fa-user-plus"></i> Refer a Student</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Student ID (Student No)</label>
                        <input type="text" name="student_no" placeholder="e.g. 20211234" required>
                    </div>

                    <div class="form-group">
                        <label>Select Counsellor</label>
                        <select name="counsellor_id" required>
                            <option value="">-- Choose Counsellor --</option>
                            <?php while($c = mysqli_fetch_assoc($counsellors_list)): ?>
                                <option value="<?php echo $c['counsellor_id']; ?>">
                                    <?php echo htmlspecialchars($c['full_name'] . ' (' . $c['specialization'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Reason for Referral</label>
                        <textarea name="reason" rows="4" placeholder="Enter reason or observations..." required></textarea>
                    </div>
                    <button type="submit" name="submit_referral" class="submit-btn"><i class="fas fa-paper-plane"></i> Submit Referral</button>
                </form>
            </div>

            <div class="card-box">
                <h3><i class="fas fa-history"></i> My Referral History</h3>
                <?php if (mysqli_num_rows($referrals_query) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Counsellor</th>
                                <th>Reason</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = mysqli_fetch_assoc($referrals_query)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($r['student_name']); ?></strong></td>
                                    <td><span style="color: #2d6a4f; font-weight: 600;"><?php echo htmlspecialchars($r['counsellor_name'] ?? 'General Cell'); ?></span></td>
                                    <td><?php echo htmlspecialchars($r['reason']); ?></td>
                                    <td><?php echo htmlspecialchars($r['referral_date']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data"><i class="fas fa-info-circle" style="font-size: 24px; color: #2d6a4f; margin-bottom: 10px;"></i><p>You haven't referred any students yet.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>