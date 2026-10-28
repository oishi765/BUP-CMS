<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['staff_id']) && !isset($_SESSION['user_id'])) {
    header("Location: ../authorization/login.php");
    exit();
}

$staff_name = $_SESSION['full_name'] ?? $_SESSION['staff_name'] ?? "Staff Member";
$success_msg = "";
$error_msg = "";

// Handle appointment status update or coordination
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appt_id = mysqli_real_escape_string($conn, $_GET['id']);
    $action = $_GET['action'];
    
    $new_status = ($action == 'confirm') ? 'Confirmed' : 'Cancelled';
    
    $update_query = "UPDATE appointments SET status = '$new_status' WHERE appointment_id = '$appt_id'";
    if (mysqli_query($conn, $update_query)) {
        $success_msg = "Appointment status updated to $new_status successfully!";
    } else {
        $error_msg = "Error updating appointment status.";
    }
}

// Fetch all appointments with student and counsellor details
$query = "SELECT a.*, s.full_name as student_name, s.department, c.full_name as counsellor_name 
          FROM appointments a 
          JOIN students s ON a.student_id = s.student_id 
          JOIN counsellors c ON a.counsellor_id = c.counsellor_id 
          ORDER BY a.appointment_date DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinate Appointments - BUP-CMS</title>
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
        .card-header { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .card-header h3 { font-size: 16px; color: #1b4d3e; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { color: #555; font-weight: 600; }
        .btn-action { padding: 5px 10px; border-radius: 5px; color: white; text-decoration: none; font-size: 11px; margin-right: 5px; }
        .btn-confirm { background-color: #2e7d32; }
        .btn-cancel { background-color: #c62828; }
        .alert-success { background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 15px; }
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
                <li><a href="staff_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="verify_availability.php"><i class="fa-solid fa-calendar-check"></i> Verify Availability</a></li>
                <li class="active"><a href="coordinate_appointments.php"><i class="fa-solid fa-calendar-days"></i> Coordinate Schedule</a></li>
                <li><a href="walk_in_appointments.php"><i class="fa-solid fa-person-walking"></i> Walk-in Appointments</a></li>
                <li><a href="emergency_records.php"><i class="fa-solid fa-triangle-exclamation"></i> Emergency Records</a></li>
                <li><a href="utilization_reports.php"><i class="fa-solid fa-chart-line"></i> Utilization Reports</a></li>
                <li><a href="../authorization/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer"><p>Bangladesh University of Professionals</p></div>
    </div>

    <div class="main-content">
        <div class="header">
            <div style="font-size: 18px; font-weight: 600; color: #1b4d3e;"><i class="fa-solid fa-calendar-days"></i> Coordinate Appointment Schedule</div>
            <div class="user-profile">
                <div style="background:#1b4d3e; color:#fff; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600;"><?php echo strtoupper(substr($staff_name, 0, 2)); ?></div>
                <div class="user-info"><h4><?php echo htmlspecialchars($staff_name); ?></h4><p>Counselling Staff</p></div>
            </div>
        </div>

        <div class="dashboard-body">
            <?php if (!empty($success_msg)): ?><div class="alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?></div><?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Appointment Coordination & Schedule List</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Department</th>
                            <th>Counsellor</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): 
                                $date_val = $row['appointment_date'] ?? $row['date'] ?? 'N/A';
                                $time_val = $row['time_slot'] ?? $row['slot_time'] ?? $row['time'] ?? $row['start_time'] ?? 'N/A';
                                $status = $row['status'] ?? 'Pending';
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['student_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['counsellor_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($date_val); ?></td>
                                    <td><?php echo htmlspecialchars($time_val); ?></td>
                                    <td><strong><?php echo htmlspecialchars($status); ?></strong></td>
                                    <td>
                                        <a href="coordinate_appointments.php?id=<?php echo $row['appointment_id']; ?>&action=confirm" class="btn-action btn-confirm">Confirm</a>
                                        <a href="coordinate_appointments.php?id=<?php echo $row['appointment_id']; ?>&action=cancel" class="btn-action btn-cancel">Cancel</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" style="text-align: center; color: #777;">No appointments found to coordinate.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>