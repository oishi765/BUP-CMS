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

// Auto-create session_notes table if not exists
@mysqli_query($conn, "CREATE TABLE IF NOT EXISTS session_notes (
    note_id INT AUTO_INCREMENT PRIMARY KEY,
    counsellor_id INT,
    student_id INT,
    session_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle Add Session Note
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_note'])) {
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $session_date = mysqli_real_escape_string($conn, $_POST['session_date']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);

    if (!empty($student_id) && !empty($session_date) && !empty($notes)) {
        $insert_query = "INSERT INTO session_notes (counsellor_id, student_id, session_date, notes) VALUES ('$counsellor_id', '$student_id', '$session_date', '$notes')";
        if (mysqli_query($conn, $insert_query)) {
            $success_msg = "Session note added successfully!";
        } else {
            $error_msg = "Error adding session note: " . mysqli_error($conn);
        }
    } else {
        $error_msg = "Please fill in all fields!";
    }
}

// Fetch Counsellor Name
$query = "SELECT full_name FROM counsellors WHERE counsellor_id = '$counsellor_id'";
$result = mysqli_query($conn, $query);
$counsellor = $result ? mysqli_fetch_assoc($result) : null;
$counsellor_name = $counsellor ? $counsellor['full_name'] : "Counsellor";

// Fetch Students for dropdown
$students_query = "SELECT DISTINCT s.student_id, s.full_name FROM students s 
                   JOIN appointments a ON s.student_id = a.student_id 
                   WHERE a.counsellor_id = '$counsellor_id'";
$students_result = mysqli_query($conn, $students_query);

// Fetch Session Notes
$notes_query = "SELECT sn.*, s.full_name as student_name FROM session_notes sn 
                JOIN students s ON sn.student_id = s.student_id 
                WHERE sn.counsellor_id = '$counsellor_id' ORDER BY sn.session_date DESC";
$notes_result = mysqli_query($conn, $notes_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Notes - BUP-CMS</title>
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
        .form-group textarea { resize: vertical; height: 100px; }
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
                <li><a href="counsellor_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="availability.php"><i class="fa-solid fa-calendar-days"></i> Availability & Schedule</a></li>
                <li><a href="appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
                <li><a href="active_appointments.php"><i class="fa-solid fa-user-clock"></i> Active Appointments</a></li>
                <li><a href="student_details.php"><i class="fa-solid fa-users"></i> Student Details</a></li>
                <li class="active"><a href="session_notes.php"><i class="fa-solid fa-note-sticky"></i> Session Notes</a></li>
                <li><a href="counselling_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Counselling History</a></li>
                <li><a href="../authorization/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer"><p>Bangladesh University of Professionals</p></div>
    </div>

    <div class="main-content">
        <div class="header">
            <div style="font-size: 18px; font-weight: 600; color: #1b4d3e;"><i class="fa-solid fa-note-sticky"></i> Session Notes</div>
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
                    <div class="card-header"><h3>Add Session Note</h3></div>
                    <form action="session_notes.php" method="POST">
                        <div class="form-group">
                            <label>Select Student</label>
                            <select name="student_id" required>
                                <option value="">-- Choose Student --</option>
                                <?php if ($students_result && mysqli_num_rows($students_result) > 0): ?>
                                    <?php while($s = mysqli_fetch_assoc($students_result)): ?>
                                        <option value="<?php echo $s['student_id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Session Date</label>
                            <input type="date" name="session_date" required>
                        </div>
                        <div class="form-group">
                            <label>Notes & Observations</label>
                            <textarea name="notes" placeholder="Write session notes here..." required></textarea>
                        </div>
                        <button type="submit" name="add_note" class="btn-submit"><i class="fa-solid fa-plus"></i> Save Note</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Saved Session Notes</h3></div>
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Date</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($notes_result && mysqli_num_rows($notes_result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($notes_result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['session_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['notes']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align: center; color: #777;">No session notes found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>