<?php
session_start();
require_once "../config/db.php";

// Check if faculty is logged in
if (!isset($_SESSION['faculty_user']) || $_SESSION['role'] !== 'FACULTY') {
    header("Location: ../authorization/login.php");
    exit();
}

$faculty_name = $_SESSION['faculty_name'] ?? 'Faculty Member';
$department = $_SESSION['department'] ?? 'Department';
$designation = $_SESSION['designation'] ?? 'Faculty';
$faculty_id = $_SESSION['faculty_id'] ?? 0;

// Fetch total referrals made by this faculty
$ref_query = mysqli_query($conn, "SELECT COUNT(*) as total_ref FROM referrals WHERE faculty_id = '$faculty_id'");
$ref_data = mysqli_fetch_assoc($ref_query);
$total_referrals = $ref_data['total_ref'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - BUP Counselling & Wellness</title>
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

        .search-box {
            position: relative;
            width: 350px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 14px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border: 1px solid #ddd;
            border-radius: 20px;
            background: #fff;
            font-size: 13px;
            outline: none;
            transition: 0.3s;
        }

        .search-box input:focus {
            border-color: #0b3c29;
            box-shadow: 0 0 0 3px rgba(11, 60, 41, 0.1);
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

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #d1e7dd 0%, #e8f5ee 100%);
            padding: 30px;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border: 1px solid #c3e6cb;
        }

        .welcome-text h1 {
            font-size: 22px;
            color: #0b3c29;
            margin-bottom: 8px;
        }

        .welcome-text p {
            font-size: 13px;
            color: #2d6a4f;
            margin-bottom: 4px;
        }

        .welcome-text span {
            font-size: 12px;
            color: #555;
            font-style: italic;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid #0b3c29;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            background: #e8f5ee;
            color: #0b3c29;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .stat-info h3 {
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
        }

        .stat-info p {
            font-size: 20px;
            font-weight: 700;
            color: #0b3c29;
        }

        /* Quick Sections / Cards */
        .dashboard-sections {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .card-box {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        }

        .card-box h3 {
            font-size: 15px;
            color: #0b3c29;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tip-box {
            background: #fcf8e3;
            border-left: 4px solid #f0ad4e;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 13px;
            color: #8a6d3b;
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
                <li><a href="faculty_dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="faculty_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
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
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search anything...">
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

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h1>Welcome Back, <?php echo htmlspecialchars($faculty_name); ?>! 👋</h1>
                <p>"A supportive guide creates a stronger, healthier academic community."</p>
                <span>Take care of students, you're doing great!</span>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-share-nodes"></i></div>
                <div class="stat-info">
                    <h3>Total Referrals</h3>
                    <p><?php echo $total_referrals; ?></p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="stat-info">
                    <h3>Workshops Browsed</h3>
                    <p>View All</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <h3>Available Slots</h3>
                    <p>Check Now</p>
                </div>
            </div>
        </div>

        <!-- Dashboard Bottom Sections -->
        <div class="dashboard-sections">
            <div class="card-box">
                <h3><i class="fas fa-bullhorn" style="color: #0b3c29;"></i> Faculty Guidelines & Notice</h3>
                <p style="font-size: 13px; color: #555; line-height: 1.6;">
                    As a faculty member, you can refer any student who requires psychological support or counselling to the wellness cell securely. You can also browse upcoming workshops and submit feedback to help improve the system.
                </p>
            </div>
            <div class="card-box">
                <h3><i class="fas fa-lightbulb" style="color: #f0ad4e;"></i> Wellness Tip of the Day</h3>
                <div class="tip-box">
                    "Take a 10-minute break today. A fresh mind works better and improves student engagement."
                </div>
            </div>
        </div>

    </div>

</body>
</html>