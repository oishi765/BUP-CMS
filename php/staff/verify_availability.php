<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['staff_id']) && !isset($_SESSION['user_id'])) {
    header("Location: ../authorization/login.php");
    exit();
}

$staff_name = $_SESSION['full_name'] ?? $_SESSION['staff_name'] ?? "Staff Member";

// Fetch all availability slots with counsellor details
$query = "SELECT a.*, c.full_name as counsellor_name FROM availability a 
          JOIN counsellors c ON a.counsellor_id = c.counsellor_id 
          ORDER BY a.available_date DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Availability - BUP-CMS</title>
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
        .card { background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e0e0e0; }
        .card-header { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .card-header h3 { font-size: 16px; color: #1b4d3e; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { color: #555; font-weight: 600; }
        .badge-available { color: #2e7d32; font-weight: 600; }
        .badge-booked { color: #c62828; font-weight: 600; }
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
                <li class="active"><a href="verify_availability.php"><i class="fa-solid fa-calendar-check"></i> Verify Availability</a></li>
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
            <div style="font-size: 18px; font-weight: 600; color: #1b4d3e;"><i class="fa-solid fa-calendar-check"></i> Verify Counsellor Availability</div>
            <div class="user-profile">
                <div style="background:#1b4d3e; color:#fff; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600;"><?php echo strtoupper(substr($staff_name, 0, 2)); ?></div>
                <div class="user-info"><h4><?php echo htmlspecialchars($staff_name); ?></h4><p>Counselling Staff</p></div>
            </div>
        </div>

        <div class="dashboard-body">
            <div class="card">
                <div class="card-header">
                    <h3>All Counsellor Availability Slots</h3>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Counsellor Name</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): 
                                $status = $row['status'] ?? 'AVAILABLE';
                                $badge_class = ($status == 'AVAILABLE') ? 'badge-available' : 'badge-booked';
                                $time_slot = (isset($row['start_time']) && isset($row['end_time'])) ? $row['start_time'] . " - " . $row['end_time'] : ($row['time_slot'] ?? 'N/A');
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['counsellor_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['available_date'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($time_slot); ?></td>
                                    <td><span class="<?php echo $badge_class; ?>"><?php echo htmlspecialchars($status); ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align: center; color: #777;">No availability slots found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>