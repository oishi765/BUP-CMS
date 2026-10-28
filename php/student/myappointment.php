<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Find the correct student_id from students table using users.user_id or email/username
$student_query = mysqli_query($conn, "SELECT student_id FROM students WHERE user_id = '$current_user_id' OR email = (SELECT email FROM users WHERE user_id = '$current_user_id') LIMIT 1");
if ($student_row = mysqli_fetch_assoc($student_query)) {
    $student_id = $student_row['student_id'];
} else {
    // Fallback if session user_id directly matches student_id
    $student_id = $current_user_id;
}

// Handle Cancel Request
if (isset($_POST['cancel_appointment'])) {
    $app_id = intval($_POST['appointment_id']);
    $reason = mysqli_real_escape_string($conn, $_POST['cancel_reason'] ?? 'User requested cancellation');
    
    $update_cancel = mysqli_query($conn, "UPDATE appointments SET appointment_status='Cancelled', status='Cancelled', cancel_reason='$reason' WHERE appointment_id='$app_id' AND student_id='$student_id'");
    
    if (!$update_cancel) {
        die("Error cancelling appointment: " . mysqli_error($conn));
    }
    
    header("Location: myappointment.php?tab=Cancelled");
    exit();
}

// Handle Reschedule Request
if (isset($_POST['reschedule_appointment'])) {
    $app_id = intval($_POST['appointment_id']);
    $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
    $new_time = mysqli_real_escape_string($conn, $_POST['new_time']);
    
    $update_reschedule = mysqli_query($conn, "UPDATE appointments SET appointment_date='$new_date', appointment_time='$new_time', appointment_status='Pending', status='Pending' WHERE appointment_id='$app_id' AND student_id='$student_id'");
    
    if (!$update_reschedule) {
        die("Error rescheduling appointment: " . mysqli_error($conn));
    }
    
    header("Location: myappointment.php?tab=Approved");
    exit();
}

// Filter Tab
$status_filter = $_GET['tab'] ?? 'Approved';

if ($status_filter === 'Completed') {
    $query_str = "SELECT a.*, c.full_name as counsellor_name, c.specialization FROM appointments a JOIN counsellors c ON a.counsellor_id = c.counsellor_id WHERE a.student_id='$student_id' AND (a.appointment_status='Completed' OR a.status='Completed') ORDER BY a.appointment_date DESC";
} elseif ($status_filter === 'Cancelled') {
    $query_str = "SELECT a.*, c.full_name as counsellor_name, c.specialization FROM appointments a JOIN counsellors c ON a.counsellor_id = c.counsellor_id WHERE a.student_id='$student_id' AND (a.appointment_status='Cancelled' OR a.status='Cancelled' OR a.appointment_status='Rejected' OR a.status='Rejected') ORDER BY a.appointment_date DESC";
} else {
    // Approved tab: Show everything that is NOT Completed and NOT Cancelled/Rejected
    $query_str = "SELECT a.*, c.full_name as counsellor_name, c.specialization FROM appointments a JOIN counsellors c ON a.counsellor_id = c.counsellor_id WHERE a.student_id='$student_id' AND (a.appointment_status NOT IN ('Completed', 'Cancelled', 'Rejected') AND a.status NOT IN ('Completed', 'Cancelled', 'Rejected')) ORDER BY a.appointment_date ASC";
}

$appointments = mysqli_query($conn, $query_str);

// Fetch User Profile Name
$user_query = mysqli_query($conn, "SELECT full_name, username FROM users WHERE user_id = '$current_user_id'");
$user_data = mysqli_fetch_assoc($user_query);
$display_name = !empty($user_data['full_name']) ? $user_data['full_name'] : ($user_data['username'] ?? 'Student');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - BUP Counselling</title>
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
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-profile .avatar { width: 38px; height: 38px; background: #2d6a4f; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 13px; }

        /* Tabs */
        .tab-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .tab-btn { padding: 8px 20px; border-radius: 20px; font-size: 12px; font-weight: bold; text-decoration: none; color: #4b5563; background: #e2e8f0; transition: 0.2s; }
        .tab-btn.active { background: #2d6a4f; color: #fff; }

        /* Cards List */
        .app-list { display: flex; flex-direction: column; gap: 15px; }
        .app-card { background: #ffffff; border-radius: 14px; padding: 20px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
        
        .app-info { display: flex; align-items: center; gap: 16px; }
        .app-avatar { width: 55px; height: 55px; background: #1b4332; color: #52b788; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; }
        .app-details h4 { font-size: 15px; color: #111827; }
        .app-details .spec { font-size: 11px; color: #2d6a4f; font-weight: 600; margin-bottom: 6px; display: inline-block; }
        .app-details p { font-size: 12px; color: #6b7280; margin-bottom: 3px; }

        .status-tag { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: bold; margin-top: 4px; }
        .status-PENDING, .status-Pending { background: #fef3c7; color: #d97706; }
        .status-APPROVED, .status-Approved, .status-Confirmed, .status-CONFIRMED { background: #dcfce7; color: #15803d; }
        .status-Completed { background: #e0f2fe; color: #0369a1; }
        .status-Cancelled { background: #fee2e2; color: #b91c1c; }

        .app-actions { display: flex; flex-direction: column; gap: 8px; width: 130px; }
        .btn-action { padding: 6px 12px; border-radius: 6px; font-size: 11px; font-weight: bold; text-align: center; cursor: pointer; border: none; text-decoration: none; transition: 0.2s; }
        .btn-reschedule { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .btn-reschedule:hover { background: #2563eb; color: #fff; }
        .btn-cancel { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .btn-cancel:hover { background: #dc2626; color: #fff; }

        /* Modal Background */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 999; }
        .modal-content { background: #fff; border-radius: 16px; padding: 25px; width: 400px; text-align: center; position: relative; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .close-modal { position: absolute; right: 15px; top: 15px; cursor: pointer; font-size: 16px; color: #888; }
        .modal-btn { padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; border: none; margin-top: 15px; font-size: 12px; }
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
            <h2 style="font-size: 20px; color: #111827;"><i class="fa-solid fa-calendar-check" style="color: #2d6a4f;"></i> My Appointments</h2>
            <p style="font-size: 12px; color: #666;">View, manage or reschedule your counselling appointments.</p>
        </div>
        <div class="user-profile">
            <div class="avatar"><?php echo strtoupper(substr($display_name, 0, 2)); ?></div>
            <div>
                <strong style="font-size: 13px;"><?php echo htmlspecialchars($display_name); ?></strong><br>
                <small style="color: #52b788;">Student</small>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="tab-bar">
        <a href="myappointment.php?tab=Approved" class="tab-btn <?php echo $status_filter === 'Approved' ? 'active' : ''; ?>">Approved</a>
        <a href="myappointment.php?tab=Completed" class="tab-btn <?php echo $status_filter === 'Completed' ? 'active' : ''; ?>">Completed</a>
        <a href="myappointment.php?tab=Cancelled" class="tab-btn <?php echo $status_filter === 'Cancelled' ? 'active' : ''; ?>">Cancelled</a>
    </div>

    <!-- Appointments List -->
    <div class="app-list">
        <?php if ($appointments && mysqli_num_rows($appointments) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($appointments)): ?>
                <div class="app-card">
                    <div class="app-info">
                        <div class="app-avatar"><i class="fa-solid fa-user-doctor"></i></div>
                        <div class="app-details">
                            <h4><?php echo htmlspecialchars($row['counsellor_name'] ?? 'Counsellor'); ?></h4>
                            <span class="spec"><?php echo htmlspecialchars($row['specialization'] ?? 'Specialization'); ?></span>
                            <p><i class="fa-solid fa-calendar-day" style="color: #2d6a4f;"></i> <?php echo !empty($row['appointment_date']) ? date('l, d F Y', strtotime($row['appointment_date'])) : 'N/A'; ?></p>
                            <p><i class="fa-solid fa-clock" style="color: #2d6a4f;"></i> <?php echo htmlspecialchars($row['appointment_time'] ?? $row['time_slot'] ?? ''); ?> | <?php echo htmlspecialchars($row['counselling_type'] ?? 'Session'); ?></p>
                            <?php 
                                $display_status = !empty($row['appointment_status']) ? $row['appointment_status'] : ($row['status'] ?? 'Pending');
                            ?>
                            <span class="status-tag status-<?php echo $display_status; ?>"><?php echo $display_status; ?></span>
                        </div>
                    </div>

                    <?php 
                        $status_upper = strtoupper($display_status);
                        if ($status_upper === 'PENDING' || $status_upper === 'APPROVED' || $status_upper === 'CONFIRMED'): 
                    ?>
                        <div class="app-actions">
                            <button type="button" class="btn-action btn-reschedule" onclick="openRescheduleModal(<?php echo $row['appointment_id']; ?>)"><i class="fa-solid fa-arrows-rotate"></i> Reschedule</button>
                            <button type="button" class="btn-action btn-cancel" onclick="openCancelModal(<?php echo $row['appointment_id']; ?>)"><i class="fa-solid fa-trash"></i> Cancel</button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="background: #fff; padding: 40px; text-align: center; border-radius: 12px; color: #777; font-size: 13px;">No <?php echo strtolower($status_filter); ?> appointments found.</div>
        <?php endif; ?>
    </div>
</div>

<!-- Cancel Appointment Modal -->
<div class="modal" id="cancelModal">
    <div class="modal-content">
        <i class="fa-solid fa-xmark close-modal" onclick="closeModal('cancelModal')"></i>
        <i class="fa-solid fa-face-frown" style="font-size: 40px; color: #dc2626; margin-bottom: 10px;"></i>
        <h3 style="font-size: 16px; margin-bottom: 8px;">Cancel Appointment</h3>
        <p style="font-size: 12px; color: #666; margin-bottom: 15px;">Are you sure you want to cancel this appointment?</p>
        
        <form action="myappointment.php" method="POST">
            <input type="hidden" name="appointment_id" id="cancel_app_id">
            <div style="margin-bottom: 12px; text-align: left;">
                <label style="font-size: 11px; font-weight: bold; display: block; margin-bottom: 4px;">Reason (Optional)</label>
                <input type="text" name="cancel_reason" placeholder="Why are you cancelling?" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; font-size: 12px;">
            </div>
            <button type="button" class="modal-btn" style="background: #e5e7eb; color: #333;" onclick="closeModal('cancelModal')">No, Keep It</button>
            <button type="submit" name="cancel_appointment" class="modal-btn" style="background: #dc2626; color: #fff;">Yes, Cancel</button>
        </form>
    </div>
</div>

<!-- Reschedule Modal -->
<div class="modal" id="rescheduleModal">
    <div class="modal-content" style="text-align: left;">
        <i class="fa-solid fa-xmark close-modal" onclick="closeModal('rescheduleModal')"></i>
        <h3 style="font-size: 16px; margin-bottom: 15px;"><i class="fa-solid fa-calendar-days" style="color: #2d6a4f;"></i> Reschedule Appointment</h3>
        
        <form action="myappointment.php" method="POST">
            <input type="hidden" name="appointment_id" id="reschedule_app_id">
            
            <div style="margin-bottom: 12px;">
                <label style="font-size: 11px; font-weight: bold; display: block; margin-bottom: 4px;">New Date</label>
                <input type="date" name="new_date" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; font-size: 12px;" required>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="font-size: 11px; font-weight: bold; display: block; margin-bottom: 4px;">New Time Slot</label>
                <select name="new_time" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid #ccc; font-size: 12px;" required>
                    <option value="09:00 AM">09:00 AM</option>
                    <option value="10:00 AM">10:00 AM</option>
                    <option value="11:00 AM">11:00 AM</option>
                    <option value="02:00 PM">02:00 PM</option>
                    <option value="04:00 PM">04:00 PM</option>
                </select>
            </div>

            <button type="submit" name="reschedule_appointment" class="modal-btn" style="background: #2d6a4f; color: #fff; width: 100%;">Update Appointment</button>
        </form>
    </div>
</div>

<script>
function openCancelModal(id) {
    document.getElementById('cancel_app_id').value = id;
    document.getElementById('cancelModal').style.display = 'flex';
}

function openRescheduleModal(id) {
    document.getElementById('reschedule_app_id').value = id;
    document.getElementById('rescheduleModal').style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}
</script>

</body>
</html>