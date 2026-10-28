<?php
session_start();
require_once "../config/db.php";

// Check if counsellor is logged in
if (!isset($_SESSION['counsellor_id'])) {
    header("Location: ../authorization/login.php");
    exit();
}

$counsellor_id = $_SESSION['counsellor_id'];

// Handle Accept / Reject Actions directly on Dashboard
if (isset($_GET['action']) && isset($_GET['id'])) {
    $appt_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    $new_status = ($action == 'accept') ? 'Confirmed' : 'Cancelled';
    
    $update_query = "UPDATE appointments SET status = '$new_status', appointment_status = '$new_status' WHERE appointment_id = '$appt_id'";
    $run_update = mysqli_query($conn, $update_query);
    
    if (!$run_update) {
        die("Database Update Error: " . mysqli_error($conn));
    }
    
    header("Location: counsellor_dashboard.php");
    exit();
}

// Fetch Counsellor Name from database
$query = "SELECT counsellor_id, full_name FROM counsellors WHERE counsellor_id = '$counsellor_id' OR counsellor_id = 8 LIMIT 1";
$result = mysqli_query($conn, $query);
$counsellor = $result ? mysqli_fetch_assoc($result) : null;
$counsellor_name = $counsellor ? $counsellor['full_name'] : "Md. Rakibul Islam";
$actual_counsellor_id = $counsellor ? $counsellor['counsellor_id'] : 8;

// Fetch Today's Appointments from database
$today = date('Y-m-d');
$appt_query = "SELECT a.*, s.full_name as student_name, s.department FROM appointments a 
               JOIN students s ON a.student_id = s.student_id 
               WHERE (a.counsellor_id = '$actual_counsellor_id' OR a.counsellor_id = 8) 
               AND (a.status = 'Confirmed' OR a.status = 'Approved' OR a.appointment_status = 'Confirmed' OR a.appointment_status = 'Approved')
               AND a.appointment_date = '$today'
               AND (a.counselling_type NOT LIKE '%Emergency%' AND a.time_slot NOT LIKE '%Emergency%')";
$today_appointments = mysqli_query($conn, $appt_query);

// Fetch Pending Requests from database
$pending_query = "SELECT a.*, s.full_name as student_name, s.department FROM appointments a 
                  JOIN students s ON a.student_id = s.student_id 
                  WHERE (a.counsellor_id = '$actual_counsellor_id' OR a.counsellor_id = 8) 
                  AND (a.status IN ('Pending', 'PENDING') OR a.appointment_status IN ('Pending', 'PENDING') OR a.status IS NULL OR a.status = '')";
$pending_requests = mysqli_query($conn, $pending_query);

// Fetch Emergency / Critical Cases assigned to this counsellor
$emergency_query = "SELECT e.*, s.full_name as student_name, s.department FROM emergency_records e 
                    JOIN students s ON e.student_id = s.student_id 
                    ORDER BY e.emergency_id DESC";
$emergency_result = mysqli_query($conn, $emergency_query);

// Fetch Total Completed Sessions Count
$completed_query = "SELECT COUNT(*) as total FROM appointments WHERE (counsellor_id = '$actual_counsellor_id' OR counsellor_id = 8) AND (status = 'Completed' OR appointment_status = 'Completed')";
$completed_result = mysqli_query($conn, $completed_query);
$completed_row = $completed_result ? mysqli_fetch_assoc($completed_result) : null;
$total_completed = $completed_row ? $completed_row['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counsellor Dashboard - BUP-CMS</title>
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
        .search-bar { background: #f1f3f2; padding: 8px 15px; border-radius: 20px; display: flex; align-items: center; gap: 10px; width: 300px; }
        .search-bar input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-info h4 { font-size: 14px; color: #333; }
        .user-info p { font-size: 11px; color: #777; }
        .dashboard-body { padding: 25px 30px; display: flex; flex-direction: column; gap: 20px; }
        .welcome-banner { background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 20px 25px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #a5d6a7; }
        .welcome-text h1 { font-size: 22px; color: #1b4d3e; margin-bottom: 5px; }
        .welcome-text p { font-size: 13px; color: #2e7d32; }
        .grid-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
        .card { background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e0e0e0; }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .card-header h3 { font-size: 16px; color: #1b4d3e; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { color: #555; font-weight: 600; }
        .btn-action { padding: 5px 10px; border-radius: 5px; border: none; cursor: pointer; font-size: 11px; font-weight: 500; color: white; text-decoration: none; display: inline-block; }
        .btn-accept { background-color: #2e7d32; }
        .btn-reject { background-color: #c62828; }
        textarea { width: 100%; height: 90px; border: 1px solid #ccc; border-radius: 8px; padding: 10px; font-size: 13px; resize: none; outline: none; }
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
                <li class="active"><a href="counsellor_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="availability.php"><i class="fa-solid fa-calendar-days"></i> Availability & Schedule</a></li>
                <li><a href="appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
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
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass" style="color: #777;"></i>
                <input type="text" placeholder="Search appointments, students...">
            </div>
            <div class="user-profile">
                <div style="background:#1b4d3e; color:#fff; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600;"><?php echo strtoupper(substr($counsellor_name, 0, 2)); ?></div>
                <div class="user-info"><h4><?php echo htmlspecialchars($counsellor_name); ?></h4><p>Counsellor</p></div>
            </div>
        </div>

        <div class="dashboard-body">
            <div class="welcome-banner">
                <div class="welcome-text">
                    <h1>Welcome, <?php echo htmlspecialchars($counsellor_name); ?>!</h1>
                    <p>Manage your schedules, appointment requests, and session records efficiently.</p>
                </div>
                <div><i class="fa-solid fa-hand-holding-heart" style="font-size: 40px; color: #1b4d3e;"></i></div>
            </div>

            <!-- Critical / Emergency Cases Alert Widget -->
            <?php if ($emergency_result && mysqli_num_rows($emergency_result) > 0): ?>
                <div class="card" style="border: 1px solid #feb2b2; border-left: 5px solid #e53e3e; background: #fff5f5;">
                    <div class="card-header">
                        <h3 style="color: #c53030;"><i class="fa-solid fa-triangle-exclamation"></i> Critical / Emergency Cases Alert</h3>
                        <span style="font-size: 11px; background: #e53e3e; color: white; padding: 3px 10px; border-radius: 10px; font-weight: bold;">Action Required</span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Department</th>
                                <th>Severity</th>
                                <th>Description</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($em_row = mysqli_fetch_assoc($emergency_result)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($em_row['student_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($em_row['department']); ?></td>
                                    <td><span style="color: #e53e3e; font-weight: bold; background: #fed7d7; padding: 2px 8px; border-radius: 6px; font-size: 11px;"><?php echo htmlspecialchars($em_row['severity'] ?? 'Critical'); ?></span></td>
                                    <td><?php echo htmlspecialchars($em_row['description'] ?? 'Critical condition'); ?></td>
                                    <td><?php echo htmlspecialchars($em_row['reported_at'] ?? $em_row['created_at']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <div class="grid-row">
                <div class="card">
                    <div class="card-header">
                        <h3>Today's Appointments</h3>
                        <span style="font-size: 12px; color: #666;"><?php echo date('d M, Y'); ?></span>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Student Name</th>
                                <th>Department</th>
                                <th>Time Slot</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($today_appointments && mysqli_num_rows($today_appointments) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($today_appointments)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                                        <td><?php echo htmlspecialchars($row['time_slot'] ?? $row['appointment_time']); ?></td>
                                        <td><span style="color: #2e7d32; font-weight: 500;">Confirmed</span></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; color: #777;">No appointments scheduled for today.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Pending Requests</h3></div>
                    <div style="display: flex; flex-direction: column; gap: 10px; font-size: 13px;">
                        <?php if ($pending_requests && mysqli_num_rows($pending_requests) > 0): ?>
                            <?php while($req = mysqli_fetch_assoc($pending_requests)): ?>
                                <div style="padding: 8px; background: #f9f9f9; border-radius: 6px; border-left: 3px solid #f39c12;">
                                    <p><strong><?php echo htmlspecialchars($req['student_name']); ?></strong> (<?php echo htmlspecialchars($req['department']); ?>)</p>
                                    <p style="font-size: 11px; color: #666;">Date: <?php echo htmlspecialchars($req['appointment_date']); ?> | <?php echo htmlspecialchars($req['time_slot'] ?? $req['appointment_time']); ?></p>
                                    <div style="margin-top: 5px; display: flex; gap: 5px;">
                                        <a href="counsellor_dashboard.php?id=<?php echo $req['appointment_id']; ?>&action=accept" class="btn-action btn-accept">Accept</a>
                                        <a href="counsellor_dashboard.php?id=<?php echo $req['appointment_id']; ?>&action=reject" class="btn-action btn-reject">Reject</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: #777; font-size: 12px; margin-top: 20px;">No pending appointment requests.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="grid-row">
                <div class="card">
    <div class="card-header"><h3>Quick Session Notes</h3></div>
    <form action="save_quick_note.php" method="POST">
        <textarea name="quick_note" placeholder="Write quick notes for ongoing or completed sessions..." required></textarea>
        <button type="submit" style="margin-top: 10px; background: #1b4d3e; color: #fff; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-size: 12px;">Save Note</button>
    </form>
</div>
    






                <div class="card" style="display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center;">
                    <h3 style="color: #1b4d3e; margin-bottom: 5px;">Total Completed Sessions</h3>
                    <h1 style="font-size: 36px; color: #2e7d32; font-weight: 700;"><?php echo $total_completed; ?></h1>
                    <p style="font-size: 12px; color: #666;">Keep up the great work supporting students!</p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>