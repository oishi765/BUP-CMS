<?php
session_start();
require_once "../config/db.php";

// Check if counsellor is logged in
if (!isset($_SESSION['counsellor_id'])) {
    header("Location: ../authorization/login.php");
    exit();
}

$counsellor_id = $_SESSION['counsellor_id'];
$success_msg = "";
$error_msg = "";

// Handle Profile Update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $experience_years = mysqli_real_escape_string($conn, $_POST['experience_years']);
    $degree = mysqli_real_escape_string($conn, $_POST['degree']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $about = mysqli_real_escape_string($conn, $_POST['about']);
    $tags = mysqli_real_escape_string($conn, $_POST['tags']);

    $update_query = "UPDATE counsellors SET 
                    full_name = '$full_name', 
                    specialization = '$specialization', 
                    email = '$email', 
                    phone = '$phone', 
                    experience_years = '$experience_years', 
                    degree = '$degree', 
                    department = '$department', 
                    about = '$about', 
                    tags = '$tags' 
                    WHERE counsellor_id = '$counsellor_id'";

    if (mysqli_query($conn, $update_query)) {
        $success_msg = "Profile updated successfully!";
    } else {
        $error_msg = "Error updating profile: " . mysqli_error($conn);
    }
}

// Fetch Counsellor Information from database
$query = "SELECT * FROM counsellors WHERE counsellor_id = '$counsellor_id'";
$result = mysqli_query($conn, $query);
$counsellor = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counsellor Profile - BUP-CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: #f4f7f6;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        /* Sidebar Styling (Green Theme) */
        .sidebar {
            width: 260px;
            background-color: #0c3325;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
        }
        .sidebar-top {
            padding: 20px;
        }
        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }
        .logo-area img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .logo-text h2 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .logo-text p {
            font-size: 10px;
            color: #a3b899;
        }
        .nav-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .nav-links li a {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 15px;
            color: #cfd8dc;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .nav-links li.active a, .nav-links li a:hover {
            background-color: #1b4d3e;
            color: #ffffff;
        }
        .nav-links li a i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }
        .sidebar-footer {
            padding: 20px;
            font-size: 11px;
            color: #8fa89b;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Main Content Styling */
        .main-content {
            margin-left: 260px;
            flex: 1;
            height: 100vh;
            overflow-y: auto;
            background-color: #f4f6f5;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: #ffffff;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #e0e0e0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .user-info h4 {
            font-size: 14px;
            color: #333;
        }
        .user-info p {
            font-size: 11px;
            color: #777;
        }

        /* Dashboard Body */
        .dashboard-body {
            padding: 25px 30px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .card {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border: 1px solid #e0e0e0;
        }
        .card-header {
            margin-bottom: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .card-header h3 {
            font-size: 18px;
            color: #1b4d3e;
        }

        /* Form Grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .form-group.full-width {
            grid-column: span 2;
        }
        .form-group label {
            font-size: 13px;
            font-weight: 500;
            color: #333;
        }
        .form-group input, .form-group textarea {
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 13px;
            outline: none;
            background: #fcfcfc;
        }
        .form-group input:focus, .form-group textarea:focus {
            border-color: #1b4d3e;
            background: #fff;
        }
        .form-group textarea {
            resize: vertical;
            height: 90px;
        }
        .btn-save {
            background-color: #1b4d3e;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }
        .btn-save:hover {
            background-color: #123528;
        }
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 15px;
            border: 1px solid #c8e6c9;
        }
        .alert-error {
            background: #ffebee;
            color: #c62828;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 15px;
            border: 1px solid #ffcdd2;
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-top">
            <div class="logo-area">
                <img src="/BUP-CMS/images/logo.png" alt="BUP Logo">
                <div class="logo-text">
                    <h2>BUP-CMS</h2>
                    <p>Counselling & Wellness</p>
                </div>
            </div>
            <ul class="nav-links">
                <li><a href="counsellor_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li class="active"><a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="availability.php"><i class="fa-solid fa-calendar-days"></i> Availability & Schedule</a></li>
                <li><a href="appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
                <li><a href="active_appointments.php"><i class="fa-solid fa-user-clock"></i> Active Appointments</a></li>
                <li><a href="student_details.php"><i class="fa-solid fa-users"></i> Student Details</a></li>
                <li><a href="session_notes.php"><i class="fa-solid fa-note-sticky"></i> Session Notes</a></li>
                <li><a href="counselling_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Counselling History</a></li>
                <li><a href="../authorization/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <p>Bangladesh University of Professionals</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <div style="font-size: 18px; font-weight: 600; color: #1b4d3e;">
                <i class="fa-solid fa-user-gear"></i> My Profile Management
            </div>
            <div class="user-profile">
                <div style="background:#1b4d3e; color:#fff; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600;">
                    <?php echo strtoupper(substr($counsellor['full_name'], 0, 2)); ?>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($counsellor['full_name']); ?></h4>
                    <p>Counsellor</p>
                </div>
            </div>
        </div>

        <div class="dashboard-body">
            <div class="card">
                <div class="card-header">
                    <h3>Counsellor Personal & Professional Information</h3>
                </div>

                <?php if (!empty($success_msg)): ?>
                    <div class="alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?></div>
                <?php endif; ?>

                <?php if (!empty($error_msg)): ?>
                    <div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div>
                <?php endif; ?>

                <form action="profile.php" method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($counsellor['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Specialization</label>
                            <input type="text" name="specialization" value="<?php echo htmlspecialchars($counsellor['specialization']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($counsellor['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($counsellor['phone']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Experience (Years)</label>
                            <input type="number" name="experience_years" value="<?php echo htmlspecialchars($counsellor['experience_years']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Degree</label>
                            <input type="text" name="degree" value="<?php echo htmlspecialchars($counsellor['degree']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Department</label>
                            <input type="text" name="department" value="<?php echo htmlspecialchars($counsellor['department']); ?>">
                        </div>
                        <div class="form-group">
                            <label>Tags (Comma separated)</label>
                            <input type="text" name="tags" value="<?php echo htmlspecialchars($counsellor['tags']); ?>">
                        </div>
                        <div class="form-group full-width">
                            <label>About Me</label>
                            <textarea name="about"><?php echo htmlspecialchars($counsellor['about']); ?></textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>