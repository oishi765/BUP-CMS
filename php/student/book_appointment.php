<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Safe query checking with error handling
$user_q = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$current_user_id' LIMIT 1");
$user_row = ($user_q && mysqli_num_rows($user_q) > 0) ? mysqli_fetch_assoc($user_q) : [];
$u_email = $user_row['email'] ?? '';
$u_fullname = $user_row['full_name'] ?? ($user_row['username'] ?? 'Student');

// Explicitly map student_id based on logged-in user to prevent mismatch
$student_id = 1; // Fallback
$student_q = mysqli_query($conn, "SELECT student_id FROM students WHERE user_id = '$current_user_id' OR email = '$u_email' LIMIT 1");
if ($student_q && mysqli_num_rows($student_q) > 0) {
    $s_row = mysqli_fetch_assoc($student_q);
    $student_id = $s_row['student_id'];
} else {
    // Check if session user_id directly matches student_id
    $direct_q = mysqli_query($conn, "SELECT student_id FROM students WHERE student_id = '$current_user_id' LIMIT 1");
    if ($direct_q && mysqli_num_rows($direct_q) > 0) {
        $student_id = $current_user_id;
    }
}

$success_msg = "";
$error_msg = "";

// Handle Booking Form Submission
if (isset($_POST['book_appointment'])) {
    $counsellor_id = intval($_POST['counsellor_id']);
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $time_slot = mysqli_real_escape_string($conn, $_POST['time_slot']);
    $counselling_type = mysqli_real_escape_string($conn, $_POST['counselling_type'] ?? 'Mental Health Counselling');

    if ($counsellor_id > 0 && !empty($appointment_date) && !empty($time_slot)) {
        $insert_query = "INSERT INTO appointments (student_id, counsellor_id, appointment_date, time_slot, appointment_time, counselling_type, status, appointment_status) 
                         VALUES ('$student_id', '$counsellor_id', '$appointment_date', '$time_slot', '$time_slot', '$counselling_type', 'Pending', 'Pending')";
        
        $run_insert = mysqli_query($conn, $insert_query);
        if ($run_insert) {
            $success_msg = "Appointment booking request submitted successfully!";
        } else {
            $error_msg = "Error booking appointment: " . mysqli_error($conn);
        }
    } else {
        $error_msg = "Please fill in all required fields.";
    }
}

// Fetch Counsellors List safely
$counsellors_result = mysqli_query($conn, "SELECT counsellor_id, full_name, specialization FROM counsellors");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - BUP Counselling</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f4f7f5; color: #2c3e50; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 260px; height: 100vh; background-color: #081c15; color: #fff; position: fixed; display: flex; flex-direction: column; justify-content: space-between; padding: 20px 0; }
        .sidebar .brand { padding: 0 20px; font-size: 15px; font-weight: bold; margin-bottom: 25px; color: #52b788; display: flex; align-items: center; gap: 10px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li a { display: flex; align-items: center; padding: 12px 25px; color: #d1e0d7; text-decoration: none; font-size: 13px; transition: 0.3s; }
        .sidebar ul li a i { margin-right: 12px; width: 18px; }
        .sidebar ul li.active a, .sidebar ul li a:hover { background-color: #1b4332; color: #fff; border-left: 4px solid #52b788; }
        .sidebar-footer { padding: 20px; font-size: 11px; color: #74a892; border-top: 1px solid #1b4332; }

        /* Main Content */
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 25px 35px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-profile .avatar { width: 38px; height: 38px; background: #2d6a4f; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 13px; }

        /* Form Container */
        .form-container { background: #ffffff; border-radius: 14px; padding: 30px; border: 1px solid #e2e8f0; max-width: 700px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        .form-group { margin-bottom: 18px; }
        .form-group label { font-size: 12px; font-weight: bold; display: block; margin-bottom: 6px; color: #374151; }
        .form-control { width: 100%; padding: 10px 14px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; }
        .form-control:focus { border-color: #2d6a4f; }
        
        .btn-submit { background: #1b4332; color: #fff; border: none; padding: 12px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; width: 100%; font-size: 14px; transition: 0.2s; }
        .btn-submit:hover { background: #2d6a4f; }

        .alert-success { background: #dcfce7; color: #15803d; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; }
        .alert-error { background: #fee2e2; color: #b91c1c; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="brand"><i class="fa-solid fa-heart-pulse"></i> Counselling & Wellness</div>
        <ul>
            <li><a href="student_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="my_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
            <li><a href="counsellors.php"><i class="fa-solid fa-user-doctor"></i> Counsellors</a></li>
            <li><a href="myappointment.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
            <li><a href="myworkshop.php"><i class="fa-solid fa-graduation-cap"></i> Workshops</a></li>
            <li><a href="my_enrollments.php"><i class="fa-solid fa-book-open"></i> My Enrollments</a></li>
            <li><a href="counselling_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Counselling History</a></li>
            <li><a href="my_feedback.php"><i class="fa-solid fa-comment-dots"></i> Feedback</a></li>
            <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>
    <div class="sidebar-footer">
        <strong>Bangladesh University of Professionals</strong><br>
        <span style="font-size: 10px; opacity: 0.8;">"A Safer, Healthier, Stronger BUP"</span>
    </div>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h2 style="font-size: 20px; color: #111827;"><i class="fa-solid fa-calendar-plus" style="color: #2d6a4f;"></i> Book Counselling Appointment</h2>
            <p style="font-size: 12px; color: #666;">Schedule a session with one of our professional counsellors.</p>
        </div>
        <div class="user-profile">
            <div class="avatar"><?php echo strtoupper(substr($u_fullname, 0, 2)); ?></div>
            <div>
                <strong style="font-size: 13px;"><?php echo htmlspecialchars($u_fullname); ?></strong><br>
                <small style="color: #52b788;">Student</small>
            </div>
        </div>
    </div>

    <div class="form-container">
        <?php if (!empty($success_msg)): ?>
            <div class="alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo $success_msg; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_msg)): ?>
            <div class="alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?php echo $error_msg; ?></div>
        <?php endif; ?>

        <form action="book_appointment.php" method="POST">
            <div class="form-group">
                <label>Select Counsellor</label>
                <select name="counsellor_id" class="form-control" required>
                    <option value="">-- Choose Counsellor --</option>
                    <?php if ($counsellors_result) { while ($c = mysqli_fetch_assoc($counsellors_result)): ?>
                        <option value="<?php echo $c['counsellor_id']; ?>"><?php echo htmlspecialchars($c['full_name']); ?> (<?php echo htmlspecialchars($c['specialization'] ?? 'Counsellor'); ?>)</option>
                    <?php endwhile; } ?>
                </select>
            </div>

            <div class="form-group">
                <label>Appointment Date</label>
                <input type="date" name="appointment_date" class="form-control" required>
            </div>

            <div class="form-group">
                <label>Preferred Time Slot</label>
                <select name="time_slot" class="form-control" required>
                    <option value="">-- Choose Time Slot --</option>
                    <option value="09:00 AM">09:00 AM</option>
                    <option value="10:00 AM">10:00 AM</option>
                    <option value="11:00 AM">11:00 AM</option>
                    <option value="02:00 PM">02:00 PM</option>
                    <option value="04:00 PM">04:00 PM</option>
                </select>
            </div>

            <div class="form-group">
                <label>Counselling Type</label>
                <select name="counselling_type" class="form-control">
                    <option value="Mental Health">Mental Health Counselling</option>
                    <option value="Academic">Academic Counselling</option>
                    <option value="Career">Career Guidance</option>
                </select>
            </div>

            <button type="submit" name="book_appointment" class="btn-submit"><i class="fa-solid fa-paper-plane"></i> Submit Booking Request</button>
        </form>
    </div>
</div>

</body>
</html>