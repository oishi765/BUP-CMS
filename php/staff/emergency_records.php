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

// Auto-create emergency table if not exists
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS emergency_records (
    emergency_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    counsellor_id INT,
    description TEXT,
    severity VARCHAR(50),
    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Add Emergency Case Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_emergency'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $counsellor_id = mysqli_real_escape_string($conn, $_POST['counsellor_id']);
    $severity = mysqli_real_escape_string($conn, $_POST['severity']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $current_date = date('Y-m-d');
    $time_slot = "Emergency / Immediate";

    if (!empty($student_id) && !empty($counsellor_id) && !empty($severity) && !empty($description)) {
        
        // Fetch user_id for the selected student to satisfy appointments foreign key constraint
        $u_check = mysqli_query($conn, "SELECT user_id FROM students WHERE student_id = '$student_id'");
        $u_row = mysqli_fetch_assoc($u_check);
        $appt_student_id = ($u_row && isset($u_row['user_id'])) ? $u_row['user_id'] : $student_id;

        // Insert into emergency_records
        $query1 = "INSERT INTO emergency_records (student_id, counsellor_id, description, severity) VALUES ('$student_id', '$counsellor_id', '$description', '$severity')";
        
        // Insert into appointments using the correct user/student id reference
        $query2 = "INSERT INTO appointments (student_id, counsellor_id, appointment_date, time_slot, status) VALUES ('$appt_student_id', '$counsellor_id', '$current_date', '$time_slot', 'Confirmed')";
        
        if (mysqli_query($conn, $query1) && mysqli_query($conn, $query2)) {
            $success_msg = "Emergency case registered successfully and assigned to the counsellor!";
        } else {
            $error_msg = "Error registering emergency record: " . mysqli_error($conn);
        }
    } else {
        $error_msg = "Please fill in all required fields!";
    }
}

// Fetch Students & Counsellors for Dropdown
$students_res = mysqli_query($conn, "SELECT student_id, full_name FROM students");
$counsellors_res = mysqli_query($conn, "SELECT counsellor_id, full_name FROM counsellors");

// Fetch Emergency Records
$emergency_res = mysqli_query($conn, "SELECT e.*, s.full_name as student_name, s.department, c.full_name as counsellor_name FROM emergency_records e JOIN students s ON e.student_id = s.student_id JOIN counsellors c ON e.counsellor_id = c.counsellor_id ORDER BY e.reported_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Records - BUP-CMS</title>
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
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 13px; outline: none; }
        .form-group textarea { resize: vertical; height: 80px; }
        .btn-submit { background-color: #c62828; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 500; cursor: pointer; width: 100%; }
        .btn-submit:hover { background-color: #b71c1c; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { color: #555; font-weight: 600; }
        .badge-emergency { background: #ffebee; color: #c62828; padding: 4px 8px; border-radius: 4px; font-weight: 600; font-size: 11px; }
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
                <li><a href="walk_in_appointments.php"><i class="fa-solid fa-person-walking"></i> Walk-in Appointments</a></li>
                <li class="active"><a href="emergency_records.php"><i class="fa-solid fa-triangle-exclamation"></i> Emergency Records</a></li>
                <li><a href="utilization_reports.php"><i class="fa-solid fa-chart-line"></i> Utilization Reports</a></li>
                <li><a href="../authorization/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer"><p>Bangladesh University of Professionals</p></div>
    </div>

    <div class="main-content">
        <div class="header">
            <div style="font-size: 18px; font-weight: 600; color: #1b4d3e;"><i class="fa-solid fa-triangle-exclamation"></i> Emergency Appointment Records</div>
            <div class="user-profile">
                <div style="background:#1b4d3e; color:#fff; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600;"><?php echo strtoupper(substr($staff_name, 0, 2)); ?></div>
                <div class="user-info"><h4><?php echo htmlspecialchars($staff_name); ?></h4><p>Counselling Staff</p></div>
            </div>
        </div>

        <div class="dashboard-body">
            <?php if (!empty($success_msg)): ?><div class="alert-success"><i class="fa-solid fa-check-circle"></i> <?php echo $success_msg; ?></div><?php endif; ?>
            <?php if (!empty($error_msg)): ?><div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error_msg; ?></div><?php endif; ?>

            <div class="grid-row">
                <!-- Add Emergency Form -->
                <div class="card">
                    <div class="card-header"><h3>Add Emergency Case</h3></div>
                    <form action="emergency_records.php" method="POST">
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
                            <label>Assign Counsellor</label>
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
                            <label>Severity Level</label>
                            <select name="severity" required>
                                <option value="High">High (Immediate)</option>
                                <option value="Critical">Critical</option>
                                <option value="Moderate">Moderate</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Emergency Description / Notes</label>
                            <textarea name="description" placeholder="Describe the crisis or emergency details..." required></textarea>
                        </div>
                        <button type="submit" name="add_emergency" class="btn-submit"><i class="fa-solid fa-triangle-exclamation"></i> Submit Emergency</button>
                    </form>
                </div>

                <!-- Emergency Records Table -->
                <div class="card">
                    <div class="card-header"><h3>Emergency Cases & Crisis Logs</h3></div>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th style="color: #1b4d3e;">Counsellor</th>
                                <th>Severity</th>
                                <th>Description</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($emergency_res && mysqli_num_rows($emergency_res) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($emergency_res)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td style="font-weight: 500; color: #1b4d3e;"><?php echo htmlspecialchars($row['counsellor_name']); ?></td>
                                        <td><span class="badge-emergency"><?php echo htmlspecialchars($row['severity']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td><?php echo htmlspecialchars($row['reported_at']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align: center; color: #777;">No emergency records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>