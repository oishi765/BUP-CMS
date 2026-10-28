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

// Fetch available appointment slots from availability/appointments table joined with counsellors
$slots_query = mysqli_query($conn, "SELECT a.*, c.full_name as counsellor_name, c.specialization FROM availability a JOIN counsellors c ON a.counsellor_id = c.counsellor_id WHERE a.status = 'AVAILABLE' ORDER BY a.available_date ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Appointment Slots - BUP Counselling & Wellness</title>
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

        /* Table Card Container */
        .card-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            padding: 25px;
        }

        .card-container h3 {
            font-size: 18px;
            color: #0b3c29;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #f8faf9;
            color: #0b3c29;
            padding: 12px 15px;
            border-bottom: 2px solid #e5ece7;
            font-weight: 600;
        }

        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            color: #444;
        }

        tr:hover {
            background-color: #fafbfc;
        }

        .badge-available {
            background: #d1e7dd;
            color: #0f5132;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
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
                <li><a href="faculty_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="counsellors.php"><i class="fas fa-user-md"></i> Counsellors</a></li>
                <li><a href="appointments.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a></li>
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
                <h2>Available Appointment Slots</h2>
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

        <!-- Appointments / Slots Card -->
        <div class="card-container">
            <h3><i class="fas fa-calendar-alt"></i> Counsellor Availability Slots</h3>
            
            <?php if ($slots_query && mysqli_num_rows($slots_query) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Counsellor Name</th>
                            <th>Specialization</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($slots_query)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['counsellor_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                <td><i class="fas fa-calendar-day" style="color: #2d6a4f; margin-right: 6px;"></i> <?php echo htmlspecialchars($row['available_date']); ?></td>
                                <td><i class="fas fa-clock" style="color: #2d6a4f; margin-right: 6px;"></i> <?php echo htmlspecialchars($row['start_time'] . ' - ' . $row['end_time']); ?></td>
                                <td><span class="badge-available">Available</span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-info-circle" style="font-size: 24px; color: #2d6a4f; margin-bottom: 10px;"></i>
                    <p>No available appointment slots found at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>