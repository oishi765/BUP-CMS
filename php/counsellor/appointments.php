<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['counsellor_id'])) {
    header("Location: ../authorization/login.php");
    exit();
}

$counsellor_id = $_SESSION['counsellor_id'];
$success_msg = "";
$error_msg = "";

// Auto-create missing columns in appointments table if they don't exist
@mysqli_query($conn, "ALTER TABLE appointments ADD COLUMN status VARCHAR(50) DEFAULT 'Pending'");
@mysqli_query($conn, "ALTER TABLE appointments ADD COLUMN appointment_status VARCHAR(50) DEFAULT 'Pending'");
@mysqli_query($conn, "ALTER TABLE appointments ADD COLUMN appointment_date DATE");
@mysqli_query($conn, "ALTER TABLE appointments ADD COLUMN time_slot VARCHAR(50)");

// Handle Actions (Accept -> Approved, Complete -> Completed, Reject -> Cancelled)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appt_id = mysqli_real_escape_string($conn, $_GET['id']);
    $action = $_GET['action'];
    
    $new_status = '';
    if ($action == 'accept') {
        $new_status = 'Approved';
    } elseif ($action == 'complete') {
        $new_status = 'Completed';
    } elseif ($action == 'reject') {
        $new_status = 'Cancelled';
    }
    
    if (!empty($new_status)) {
        $update_query = "UPDATE appointments SET status = '$new_status', appointment_status = '$new_status' WHERE appointment_id = '$appt_id' AND counsellor_id = '$counsellor_id'";
        if (mysqli_query($conn, $update_query)) {
            header("Location: appointments.php?msg=updated");
            exit();
        } else {
            $error_msg = "Database Error: " . mysqli_error($conn);
        }
    }
}

if(isset($_GET['msg'])) {
    $success_msg = "Appointment status updated successfully!";
}

// Fetch Counsellor Name
$query = "SELECT full_name FROM counsellors WHERE counsellor_id = '$counsellor_id'";
$result = mysqli_query($conn, $query);
$counsellor = $result ? mysqli_fetch_assoc($result) : null;
$counsellor_name = $counsellor ? $counsellor['full_name'] : "Counsellor";

// Fetch All Appointments safely
$appt_query = "SELECT a.*, s.full_name as student_name, s.department FROM appointments a 
               JOIN students s ON a.student_id = s.student_id 
               WHERE a.counsellor_id = '$counsellor_id' 
               ORDER BY a.appointment_date DESC";
$appt_result = mysqli_query($conn, $appt_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - BUP-CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }
        body { background-color: #f4f7f6; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background-color: #0c3325; color: #fff; display: flex; flex-direction: column; justify-content: space-between; height: 100vh; position: fixed; left: 0; top: 0; }
        .sidebar-top { padding: 20px; }
        .logo-area { display: flex; align-items: center; gap: 12px; margin-bottom: 30px; }
        .logo-area img { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
        .logo-text h2 { font-size: 18px; font-weight: 700; }
        .logo-text p { font-size: 10px; color: #a3b899; }
        .nav-links { list-style: none; display: flex; flex-direction: column; gap: 5px; }
        .nav-links li a { display: flex; align-items: center; gap: 15px; padding: 12px 15px; color: #cfd8dc; text-decoration: none; font-size: 14px; border-radius: 8px; transition: all 0.3s ease; }
        .nav-links li.active a, .nav-links li a:hover { background-color: #1b4d3e; color: #ffffff; }
        .nav-links li a i { font-size: 16px; width: 20px; text-align: center; }
        .sidebar-footer { padding: 20px; font-size: 11px; color: #8fa89b; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        .main-content { margin-left: 260px; flex: 1; height: 100vh; overflow-y: auto; background-color: #f4f6f5; display: flex; flex-direction: column; }
        .header { background: #ffffff; padding: 15px 30px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e0e0e0; position: sticky; top: 0; z-index: 100; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-info h4 { font-size: 14px; color: #333; }
        .user-info p { font-size: 11px; color: #777; }
        .dashboard-body { padding: 25px 30px; display: flex; flex-direction: column; gap: 20px; }
        .card { background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e0e0e0; }
        .card-header { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { font-size: 16px; color: #1b4d3e; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { color: #555; font-weight: 600; }
        .btn-action { padding: 6px 12px; border-radius: 5px; border: none; cursor: pointer; font-size: 11px; font-weight: 500; color: white; text-decoration: none; margin-right: 4px; display: inline-block; }
        .btn-accept { background-color: #2e7d32; }
        .btn-complete { background-color: #0284c7; }
        .btn-reject { background-color: #c62828; }
        .badge-pending { color: #d97706; font-weight: 600; background: #fef3c7; padding: 3px 8px; border-radius: 10px; font-size: 11px; }
        .badge-approved { color: #15803d; font-weight: 600; background: #dcfce7; padding: 3px 8px; border-radius: 10px; font-size: 11px; }
        .badge-completed { color: #0369a1; font-weight: 600; background: #e0f2fe; padding: 3px 8px; border-radius: 10px; font-size: 11px; }
        .badge-cancelled { color: #b91c1c; font-weight: 600; background: #fee2e2; padding: 3px 8px; border-radius: 10px; font-size: 11px; }
        .alert-success { background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 15px; }
        .alert-error { background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-top">
            <div class="logo-area">
                <img src="/BUP-CMS/images/logo.png" alt="BUP Logo">
                <div class="logo-text"><h2>BUP-CMS</h2><p>Counselling & Wellness</p></div>
            </div>
            <ul class="nav-links">
                <li><a href="counsellor_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="availability.php"><i class="fa-solid fa-calendar-days"></i> Availability & Schedule</a></li>
                <li class="active"><a href="appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
                <li><a href="active_appointments.php"><i class="fa-solid fa-user-clock"></i> Active Appointments</a></li>
                <li><a href="student_details.php"><i class="fa-solid fa-users"></i> Student Details</a></li>
                <li><a href="session_notes.php"><i class="fa-solid fa-note-sticky"></i> Session Notes</a></li>
                <li><a href="counselling_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Counselling History</a></li>
                <li><a href="../authorization/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer"><p>Bangladesh University of Professionals</p></div>
    </div>

    <div class="main-content">
        <div class="header">
            <div style="font-size: 18px; font-weight: 600; color: #1b4d3e;"><i class="fa-solid fa-calendar-check"></i> Manage Appointments</div>
            <div class="user-profile">
                <div style="background:#1b4d3e; color:#fff; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600;"><?php echo strtoupper(substr($counsellor_name, 0, 2)); ?></div>
                <div class="user-info"><h4><?php echo htmlspecialchars($counsellor_name); ?></h4><p>Counsellor</p></div>
            </div>
        </div>

        <div class="dashboard-body">
            <?php if (!empty($success_msg)): ?><div class="alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?></div><?php endif; ?>
            <?php if (!empty($error_msg)): ?><div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div><?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>All Appointment Requests & Bookings</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($appt_result && mysqli_num_rows($appt_result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($appt_result)): 
                                $appt_id = $row['appointment_id'] ?? 0;
                                $date_val = $row['appointment_date'] ?? $row['date'] ?? 'N/A';
                                $time_val = $row['time_slot'] ?? $row['slot_time'] ?? $row['time'] ?? $row['start_time'] ?? 'N/A';
                                $status = trim($row['status'] ?? $row['appointment_status'] ?? 'Pending');
                                
                                $badge_class = 'badge-pending';
                                if(strcasecmp($status, 'Approved') == 0 || strcasecmp($status, 'Confirmed') == 0) {
                                    $badge_class = 'badge-approved';
                                    $status = 'Approved';
                                } elseif(strcasecmp($status, 'Completed') == 0) {
                                    $badge_class = 'badge-completed';
                                } elseif(strcasecmp($status, 'Cancelled') == 0 || strcasecmp($status, 'Rejected') == 0) {
                                    $badge_class = 'badge-cancelled';
                                    $status = 'Cancelled';
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['student_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($date_val); ?></td>
                                    <td><?php echo htmlspecialchars($time_val); ?></td>
                                    <td>
                                        <span class="<?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span>
                                    </td>
                                    <td>
                                        <?php if(strcasecmp($status, 'Pending') == 0): ?>
                                            <a href="appointments.php?id=<?php echo $appt_id; ?>&action=accept" class="btn-action btn-accept"><i class="fa-solid fa-check"></i> Accept</a>
                                            <a href="appointments.php?id=<?php echo $appt_id; ?>&action=reject" class="btn-action btn-reject"><i class="fa-solid fa-xmark"></i> Reject</a>
                                        <?php elseif(strcasecmp($status, 'Approved') == 0): ?>
                                            <a href="appointments.php?id=<?php echo $appt_id; ?>&action=complete" class="btn-action btn-complete"><i class="fa-solid fa-check-double"></i> Mark Complete</a>
                                        <?php else: ?>
                                            <span style="font-size: 11px; font-weight: 500; color: #555;"><?php echo htmlspecialchars($status); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: #777;">No appointments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>