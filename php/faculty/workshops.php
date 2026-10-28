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
$msg = "";

// Handle Workshop Registration
if (isset($_POST['register_workshop'])) {
    $workshop_id = mysqli_real_escape_string($conn, $_POST['workshop_id']);
    
    // Check if already registered
    $check_reg = mysqli_query($conn, "SELECT * FROM workshop_registrations WHERE workshop_id='$workshop_id' AND user_id='{$_SESSION['user_id']}'");
    if (mysqli_num_rows($check_reg) > 0) {
        $msg = "You are already registered for this workshop!";
    } else {
        $reg_query = mysqli_query($conn, "INSERT INTO workshop_registrations (workshop_id, user_id, registration_date) VALUES ('$workshop_id', '{$_SESSION['user_id']}', NOW())");
        if ($reg_query) {
            $msg = "Successfully registered for the workshop!";
        } else {
            $msg = "Registration failed. Please try again.";
        }
    }
}

// Handle Search
$search = "";
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $workshops_query = mysqli_query($conn, "SELECT * FROM workshops WHERE title LIKE '%$search%' OR description LIKE '%$search%' ORDER BY workshop_date DESC");
} else {
    $workshops_query = mysqli_query($conn, "SELECT * FROM workshops ORDER BY workshop_date DESC");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Workshops - BUP Counselling & Wellness</title>
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

        /* Search Bar & Alert */
        .search-bar-container {
            margin-bottom: 25px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            max-width: 500px;
        }

        .search-form input {
            flex-grow: 1;
            padding: 10px 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
        }

        .search-form button {
            padding: 10px 20px;
            background: #0b3c29;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .alert-msg {
            background: #d1e7dd;
            color: #0f5132;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        /* Workshops Grid */
        .workshops-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .workshop-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-top: 4px solid #198754;
        }

        .workshop-card h3 {
            font-size: 17px;
            color: #0b3c29;
            margin-bottom: 10px;
        }

        .workshop-card p {
            font-size: 13px;
            color: #555;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .workshop-meta {
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
            padding-top: 12px;
            margin-bottom: 15px;
        }

        .workshop-meta div {
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .workshop-meta i {
            color: #2d6a4f;
            width: 14px;
        }

        .register-btn {
            background: #198754;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            text-align: center;
            width: 100%;
        }

        .register-btn:hover {
            background: #157347;
        }

        .no-data {
            grid-column: span 3;
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
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
                <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                <li><a href="workshops.php" class="active"><i class="fas fa-chalkboard-teacher"></i> Workshops</a></li>
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
                <h2>Browse & Search Workshops</h2>
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

        <?php if (!empty($msg)): ?>
            <div class="alert-msg"><i class="fas fa-info-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>

        <!-- Search Bar -->
        <div class="search-bar-container">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search workshops by title or description..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>

        <!-- Workshops Grid -->
        <div class="workshops-grid">
            <?php if (mysqli_num_rows($workshops_query) > 0): ?>
                <?php while ($w = mysqli_fetch_assoc($workshops_query)): ?>
                    <div class="workshop-card">
                        <div>
                            <h3><?php echo htmlspecialchars($w['title']); ?></h3>
                            <p><?php echo htmlspecialchars($w['description']); ?></p>
                            
                            <div class="workshop-meta">
                                <div><i class="fas fa-calendar-alt"></i> Date: <?php echo htmlspecialchars($w['workshop_date']); ?></div>
                                <div><i class="fas fa-clock"></i> Time: <?php echo htmlspecialchars($w['start_time'] . ' - ' . $w['end_time']); ?></div>
                                <?php if (!empty($w['location'])): ?>
                                    <div><i class="fas fa-map-marker-alt"></i> Location: <?php echo htmlspecialchars($w['location']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="workshop_id" value="<?php echo $w['workshop_id']; ?>">
                            <button type="submit" name="register_workshop" class="register-btn"><i class="fas fa-check-circle"></i> Register for Workshop</button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-info-circle" style="font-size: 24px; color: #2d6a4f; margin-bottom: 10px;"></i>
                    <p>No workshops found matching your search.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>