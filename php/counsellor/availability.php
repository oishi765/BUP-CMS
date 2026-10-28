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

// Handle Add Availability
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_availability'])) {
    $available_date = mysqli_real_escape_string($conn, $_POST['available_date'] ?? '');
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time'] ?? '');
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time'] ?? '');

    if (!empty($available_date) && !empty($start_time) && !empty($end_time)) {
        $insert_query = "INSERT INTO availability (counsellor_id, available_date, start_time, end_time, status) VALUES ('$counsellor_id', '$available_date', '$start_time', '$end_time', 'AVAILABLE')";
        if (mysqli_query($conn, $insert_query)) {
            $success_msg = "Availability slot added successfully!";
        } else {
            $error_msg = "Error: " . mysqli_error($conn);
        }
    } else {
        $error_msg = "Please fill in all fields!";
    }
}

// Handle Delete Availability using specific row details
if (isset($_GET['date']) && isset($_GET['start']) && isset($_GET['end'])) {
    $del_date = mysqli_real_escape_string($conn, $_GET['date']);
    $del_start = mysqli_real_escape_string($conn, $_GET['start']);
    $del_end = mysqli_real_escape_string($conn, $_GET['end']);

    $delete_query = "DELETE FROM availability WHERE counsellor_id = '$counsellor_id' AND available_date = '$del_date' AND start_time = '$del_start' AND end_time = '$del_end' LIMIT 1";
    mysqli_query($conn, $delete_query);
    
    header("Location: availability.php");
    exit();
}

// Fetch Counsellor Name
$query = "SELECT full_name FROM counsellors WHERE counsellor_id = '$counsellor_id'";
$result = mysqli_query($conn, $query);
$counsellor = $result ? mysqli_fetch_assoc($result) : null;
$counsellor_name = $counsellor ? $counsellor['full_name'] : "Counsellor";

// Fetch Availability Slots
$avail_query = "SELECT * FROM availability WHERE counsellor_id = '$counsellor_id' ORDER BY available_date DESC, start_time DESC";
$avail_result = mysqli_query($conn, $avail_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Availability - BUP-CMS</title>
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
        .grid-row { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        .card { background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e0e0e0; }
        .card-header { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .card-header h3 { font-size: 16px; color: #1b4d3e; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: #333; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 13px; outline: none; }
        .btn-submit { background-color: #1b4d3e; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; width: 100%; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { color: #555; font-weight: 600; }
        .btn-delete { background-color: #c62828; color: white; padding: 5px 10px; border-radius: 5px; text-decoration: none; font-size: 11px; }
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
                <li class="active"><a href="availability.php"><i class="fa-solid fa-calendar-days"></i> Availability & Schedule</a></li>
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
            <div style="font-size: 18px; font-weight: 600; color: #1b4d3e;"><i class="fa-solid fa-calendar-days"></i> Manage Availability & Schedule</div>
            <div class="user-profile">
                <div style="background:#1b4d3e; color:#fff; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600;"><?php echo strtoupper(substr($counsellor_name, 0, 2)); ?></div>
                <div class="user-info"><h4><?php echo htmlspecialchars($counsellor_name); ?></h4><p>Counsellor</p></div>
            </div>
        </div>

        <div class="dashboard-body">
            <?php if (!empty($success_msg)): ?><div class="alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?></div><?php endif; ?>
            <?php if (!empty($error_msg)): ?><div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div><?php endif; ?>

            <div class="grid-row">
                <div class="card">
                    <div class="card-header"><h3>Add New Slot</h3></div>
                    <form action="availability.php" method="POST">
                        <div class="form-group"><label>Date</label><input type="date" name="available_date" required></div>
                        <div class="form-group"><label>Start Time</label><input type="time" name="start_time" required></div>
                        <div class="form-group"><label>End Time</label><input type="time" name="end_time" required></div>
                        <button type="submit" name="add_availability" class="btn-submit"><i class="fa-solid fa-plus"></i> Add Slot</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Your Available Slots</h3></div>
                    <table>
                        <thead>
                            <tr><th>Date</th><th>Time Slot</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($avail_result && mysqli_num_rows($avail_result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($avail_result)): 
                                    $r_date = $row['available_date'];
                                    $r_start = $row['start_time'];
                                    $r_end = $row['end_time'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($r_date); ?></td>
                                        <td><?php echo htmlspecialchars($r_start) . " - " . htmlspecialchars($r_end); ?></td>
                                        <td><span style="color: #2e7d32; font-weight: 500;"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                        <td><a href="availability.php?date=<?php echo $r_date; ?>&start=<?php echo $r_start; ?>&end=<?php echo $r_end; ?>" class="btn-delete" onclick="return confirm('Delete this slot?');">Delete</a></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; color: #777;">No availability slots added yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>