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

// Handle Walk-in Appointment Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_walkin'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $counsellor_id = mysqli_real_escape_string($conn, $_POST['counsellor_id']);
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $time_slot = mysqli_real_escape_string($conn, $_POST['time_slot']);

    if (!empty($student_id) && !empty($counsellor_id) && !empty($appointment_date) && !empty($time_slot)) {
        $query = "INSERT INTO appointments (student_id, counsellor_id, appointment_date, time_slot, status) VALUES ('$student_id', '$counsellor_id', '$appointment_date', '$time_slot', 'Confirmed')";
        if (mysqli_query($conn, $query)) {
            $success_msg = "Walk-in appointment successfully registered and confirmed!";
        } else {
            $error_msg = "Error registering walk-in appointment: " . mysqli_error($conn);
        }
    } else {
        $error_msg = "Please fill in all required fields!";
    }
}

// Fetch Students & Counsellors for Dropdown
$students_res = mysqli_query($conn, "SELECT student_id, full_name FROM students");
$counsellors_res = mysqli_query($conn, "SELECT counsellor_id, full_name FROM counsellors");

// Fetch Walk-in / All Appointments
$walkin_res = mysqli_query($conn, "SELECT a.*, s.full_name as student_name, c.full_name as counsellor_name FROM appointments a JOIN students s ON a.student_id = s.student_id JOIN counsellors c ON a.counsellor_id = c.counsellor_id ORDER BY a.appointment_date DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Walk-in Appointments - BUP-CMS</title>
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
        .form-group input, .form-group select { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 13px; outline: none; }
        .btn-submit { background-color: #1b4d3e; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; width: 100%; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { color: #555; font-weight: 600; }
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
                <li><a href="staff_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="verify_availability.php"><i class="fa-solid fa-calendar-check"></i> Verify Availability</a></li>
                <li><a href="coordinate_appointments.php"><i class="fa-solid fa-calendar-days"></i> Coordinate Schedule</a></li>
                <li class="active"><a href="walk_in_appointments.php"><i class="fa-solid fa-person-walking"></i> Walk-in Appointments</a></li>
                <li><a href="emergency_records.php"><i class="fa-solid fa-triangle-exclamation"></i> Emergency Records</a></li>
                <li><a href="utilization_reports.php"><i class="fa-solid fa-chart-line"></i> Utilization Reports</a></li>
                <li><a href="../authorization/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer"><p>Bangladesh University of Professionals</p></div>
    </div>

    <div class="main-content">
        <div class="header">
            <div style="font-size: 18px; font-weight: 600; color: #1b4d3e;"><i class="fa-solid fa-person-walking"></i> Manage Walk-in Appointments</div>
            <div class="user-profile">
                <div style="background:#1b4d3e; color:#fff; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600;"><?php echo strtoupper(substr($staff_name, 0, 2)); ?></div>
                <div class="user-info"><h4><?php echo htmlspecialchars($staff_name); ?></h4><p>Counselling Staff</p></div>
            </div>
        </div>

        <div class="dashboard-body">
            <?php if (!empty($success_msg)): ?><div class="alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?></div><?php endif; ?>
            <?php if (!empty($error_msg)): ?><div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div><?php endif; ?>

            <div class="grid-row">
                <div class="card">
                    <div class="card-header"><h3>Register Walk-in</h3></div>
                    <form action="walk_in_appointments.php" method="POST">
                        <div class="form-group">
                            <label>Student</label>
                            <select name="student_id" required>
                                <option value="">-- Choose Student --</option>
                                <?php if ($students_res && mysqli_num_rows($students_res) > 0): ?>
                                    <?php while($s = mysqli_fetch_assoc($students_res)): ?>
                                        <option value="<?php echo $s['student_id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Counsellor</label>
                            <select name="counsellor_id" required>
                                <option value="">-- Choose Counsellor --</option>
                                <?php if ($counsellors_res && mysqli_num_rows($counsellors_res) > 0): ?>
                                    <?php while($c = mysqli_fetch_assoc($counsellors_res)): ?>
                                        <option value="<?php echo $c['counsellor_id']; ?>"><?php echo htmlspecialchars($c['full_name']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="appointment_date" required>
                        </div>
                        <div class="form-group">
                            <label>Time Slot</label>
                            <input type="text" name="time_slot" placeholder="e.g., 10:00 AM - 11:00 AM" required>
                        </div>
                        <button type="submit" name="add_walkin" class="btn-submit"><i class="fa-solid fa-plus"></i> Add Walk-in</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Walk-in Records</h3></div>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Counsellor</th>
                                <th>Date</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($walkin_res && mysqli_num_rows($walkin_res) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($walkin_res)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['counsellor_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['appointment_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['time_slot'] ?? $row['time'] ?? 'N/A'); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; color: #777;">No records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>