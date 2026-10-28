<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['staff_id']) && !isset($_SESSION['user_id'])) {
    header("Location: ../authorization/login.php");
    exit();
}

$staff_name = $_SESSION['full_name'] ?? $_SESSION['staff_name'] ?? "Staff Member";

// Fetch total metrics for utilization reports
$total_appts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments"))['cnt'] ?? 0;
$completed_appts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE status = 'Completed'"))['cnt'] ?? 0;
$confirmed_appts = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM appointments WHERE status = 'Confirmed' OR status = 'Accepted'"))['cnt'] ?? 0;
$emergency_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM emergency_records"))['cnt'] ?? 0;
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM students"))['cnt'] ?? 0;

// Fetch department-wise utilization stats for charts & table
$dept_query = "SELECT s.department, COUNT(a.appointment_id) as appt_count 
               FROM appointments a 
               JOIN students s ON a.student_id = s.student_id 
               GROUP BY s.department";
$dept_result = mysqli_query($conn, $dept_query);

$dept_labels = [];
$dept_data = [];
$dept_rows = [];
while($row = mysqli_fetch_assoc($dept_result)) {
    $dept_labels[] = $row['department'];
    $dept_data[] = $row['appt_count'];
    $dept_rows[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilization Reports - BUP-CMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; }
        .stat-card { background: #ffffff; padding: 18px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e0e0e0; }
        .stat-card h4 { font-size: 12px; color: #777; margin-bottom: 5px; }
        .stat-card h2 { font-size: 22px; color: #1b4d3e; }
        .charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .tables-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card { background: #ffffff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); border: 1px solid #e0e0e0; }
        .card-header { margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        .card-header h3 { font-size: 15px; color: #1b4d3e; }
        .chart-container { position: relative; height: 240px; width: 100%; display: flex; justify-content: center; align-items: center; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { color: #555; font-weight: 600; }
        @media (max-width: 900px) { .charts-grid, .tables-grid { grid-template-columns: 1fr; } }
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
                <li><a href="emergency_records.php"><i class="fa-solid fa-triangle-exclamation"></i> Emergency Records</a></li>
                <li class="active"><a href="utilization_reports.php"><i class="fa-solid fa-chart-line"></i> Utilization Reports</a></li>
                <li><a href="../authorization/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer"><p>Bangladesh University of Professionals</p></div>
    </div>

    <div class="main-content">
        <div class="header">
            <div style="font-size: 18px; font-weight: 600; color: #1b4d3e;"><i class="fa-solid fa-chart-line"></i> Counselling Service Utilization Reports</div>
            <div class="user-profile">
                <div style="background:#1b4d3e; color:#fff; width:35px; height:35px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:600;"><?php echo strtoupper(substr($staff_name, 0, 2)); ?></div>
                <div class="user-info"><h4><?php echo htmlspecialchars($staff_name); ?></h4><p>Counselling Staff</p></div>
            </div>
        </div>

        <div class="dashboard-body">
            <!-- Top Metric Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Appointments</h4>
                    <h2><?php echo $total_appts; ?></h2>
                </div>
                <div class="stat-card">
                    <h4>Completed Sessions</h4>
                    <h2><?php echo $completed_appts; ?></h2>
                </div>
                <div class="stat-card">
                    <h4>Active / Confirmed</h4>
                    <h2><?php echo $confirmed_appts; ?></h2>
                </div>
                <div class="stat-card">
                    <h4>Emergency Cases</h4>
                    <h2><?php echo $emergency_count; ?></h2>
                </div>
                <div class="stat-card">
                    <h4>Registered Students</h4>
                    <h2><?php echo $total_students; ?></h2>
                </div>
            </div>

            <!-- Visual Charts Section -->
            <div class="charts-grid">
                <div class="card">
                    <div class="card-header"><h3>Department-wise Bookings (Bar Chart)</h3></div>
                    <div class="chart-container">
                        <canvas id="deptBarChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Service Status Distribution (Doughnut Chart)</h3></div>
                    <div class="chart-container">
                        <canvas id="statusPieChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Data Tables Section -->
            <div class="tables-grid">
                <div class="card">
                    <div class="card-header"><h3>Department-wise Utilization Summary (Table)</h3></div>
                    <table>
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Total Bookings</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($dept_rows)): ?>
                                <?php foreach($dept_rows as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                                        <td><?php echo htmlspecialchars($row['appt_count']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" style="text-align: center; color: #777;">No data found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card">
                    <div class="card-header"><h3>Status Breakdown Summary (Table)</h3></div>
                    <table>
                        <thead>
                            <tr>
                                <th>Status Category</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Completed Sessions</td>
                                <td><?php echo $completed_appts; ?></td>
                            </tr>
                            <tr>
                                <td>Active / Confirmed Bookings</td>
                                <td><?php echo $confirmed_appts; ?></td>
                            </tr>
                            <tr>
                                <td>Emergency Cases</td>
                                <td><?php echo $emergency_count; ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Department-wise Bar Chart
        const deptCtx = document.getElementById('deptBarChart').getContext('2d');
        const deptBarChart = new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($dept_labels); ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?php echo json_encode($dept_data); ?>,
                    backgroundColor: '#1b4d3e',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });

        // Status Distribution Doughnut Chart
        const statusCtx = document.getElementById('statusPieChart').getContext('2d');
        const statusPieChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Active/Confirmed', 'Emergency'],
                datasets: [{
                    data: [<?php echo $completed_appts; ?>, <?php echo $confirmed_appts; ?>, <?php echo $emergency_count; ?>],
                    backgroundColor: ['#2e7d32', '#1565c0', '#c62828'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>
</body>
</html>