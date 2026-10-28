<?php
session_start();
require_once "../config/db.php";

// Check if staff is logged in
if (!isset($_SESSION['staff_id']) && !isset($_SESSION['user_id'])) {
    header("Location: ../authorization/login.php");
    exit();
}

$staff_id = $_SESSION['staff_id'] ?? $_SESSION['user_id'];

// Fetch Staff Name from Database dynamically
$query = "SELECT full_name FROM users WHERE user_id = '$staff_id' OR id = '$staff_id'";
$result = mysqli_query($conn, $query);
$staff = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
$staff_name = $staff ? $staff['full_name'] : ($_SESSION['full_name'] ?? "Staff Member");

// Fetch statistics for dashboard metrics
$total_appts = 0;
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments");
if($res) { $row = mysqli_fetch_assoc($res); $total_appts = $row['cnt']; }

$total_avail = 0;
$res2 = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM availability WHERE status = 'AVAILABLE'");
if($res2) { $row2 = mysqli_fetch_assoc($res2); $total_avail = $row2['cnt']; }

$total_students = 0;
$res3 = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM students");
if($res3) { $row3 = mysqli_fetch_assoc($res3); $total_students = $row3['cnt']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - BUP-CMS</title>
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
        .dashboard-body { padding: 25px 30px; display: flex; flex-direction: column; gap: 25px; }
        .welcome-card { background: linear-gradient(135deg, #0c3325, #1b4d3e); color: white; padding: 25px; border-radius: 14px; box-shadow: 0 4px 15px rgba(27, 77, 62, 0.15); }
        .welcome-card h2 { font-size: 22px; margin-bottom: 8px; }
        .welcome-card p { font-size: 13px; color: #d8e2dc; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; }
        .stat-card { background: #ffffff; padding: 20px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e0e0e0; display: flex; align-items: center; gap: 15px; }
        .stat-icon { width: 50px; height: 50px; background: #e8f5e9; color: #2e7d32; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-info h3 { font-size: 22px; color: #333; }
        .stat-info p { font-size: 12px; color: #777; }
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
                <li class="active"><a href="staff_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="verify_availability.php"><i class="fa-solid fa-calendar-check"></i> Verify Availability</a></li>
                <li><a href="coordinate_appointments.php"><i class="fa-solid fa-calendar-days"></i> Coordinate Schedule</a></li>
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
            <div style="font-size: 18px; font-weight: 600; color: #1b4d3e;"><i class="fa-solid fa-house"></i> Staff Dashboard</div>
            <div class="user-profile">
                <div style="background:#1b4d3e; color:#fff; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600;"><?php echo strtoupper(substr($staff_name, 0, 2)); ?></div>
                <div class="user-info"><h4><?php echo htmlspecialchars($staff_name); ?></h4><p>Counselling Staff</p></div>
            </div>
        </div>

        <div class="dashboard-body">
            <div class="welcome-card">
                <h2>Welcome back, <?php echo htmlspecialchars($staff_name); ?>! 👋</h2>
                <p>Manage counselling center operations, verify schedules, coordinate appointments, and track administrative reports efficiently.</p>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_appts; ?></h3>
                        <p>Total Appointments</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#e3f2fd; color:#1565c0;"><i class="fa-solid fa-clock"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_avail; ?></h3>
                        <p>Available Slots</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" style="background:#fff3e0; color:#ef6c00;"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?php echo $total_students; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>