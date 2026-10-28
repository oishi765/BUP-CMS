<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* Delete Workshop */
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    mysqli_query($conn, "DELETE FROM workshops WHERE workshop_id='$id'");
    header("Location: workshops.php");
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM workshops ORDER BY workshop_date DESC");

$totalWorkshop = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM workshops"))['total'] ?? 0;
$activeWorkshop = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM workshops WHERE status='ACTIVE'"))['total'] ?? 0;
$completedWorkshop = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM workshops WHERE status='COMPLETED'"))['total'] ?? 0;
$cancelledWorkshop = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM workshops WHERE status='CANCELLED'"))['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workshops - BUP Counselling & Wellness</title>
    <!-- FontAwesome Icons, Google Font & Chart.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            background-color: #06110d;
            color: #d1d5db;
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR - DARK EMERALD THEME */
        .sidebar {
            width: 270px;
            background: #071e17;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: fixed;
            height: 100vh;
            z-index: 100;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 8px 10px 28px 10px;
        }

        .brand-logo-icon {
            width: 44px;
            height: 44px;
            background: rgba(0, 206, 136, 0.15);
            border: 1px solid rgba(0, 206, 136, 0.3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #00ce88;
            font-size: 20px;
        }

        .brand-text h2 {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }

        .brand-text span {
            font-size: 11px;
            color: #00ce88;
            font-weight: 500;
            margin-top: 2px;
            display: block;
        }

        .nav-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 18px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .nav-link i {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .nav-link:hover {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-item.active .nav-link {
            background: #0c3326;
            color: #00ce88;
            border: 1px solid rgba(0, 206, 136, 0.2);
            font-weight: 600;
        }

        .sidebar-footer-text {
            font-size: 12px;
            color: #475569;
            text-align: center;
            font-style: italic;
            padding: 10px;
        }

        /* MAIN CONTENT AREA */
        .main-content {
            margin-left: 270px;
            flex: 1;
            padding: 35px;
            background: #f8fafc;
            color: #1e293b;
        }

        /* TOPBAR */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
        }

        .search-box {
            position: relative;
            width: 320px;
        }

        .search-box input {
            width: 100%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 9px 16px 9px 38px;
            color: #1e293b;
            font-size: 13px;
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 13px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .date-badge {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 8px 16px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }

        .date-badge i { color: #00ce88; }

        .user-profile-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 6px 14px 6px 6px;
            border-radius: 30px;
        }

        .avatar {
            width: 34px;
            height: 34px;
            background: linear-gradient(135deg, #071e17, #0c3326);
            color: #00ce88;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 12px;
        }

        .profile-info h4 { font-size: 13px; color: #0f172a; line-height: 1.1; }
        .profile-info p { font-size: 10px; color: #64748b; margin-top: 2px; }

        /* STAT CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 22px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: #ffffff;
            padding: 22px;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .stat-card h3 {
            font-size: 26px;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-card p {
            margin-top: 4px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
        }

        .color-dark { color: #071e17; }
        .color-green { color: #10b981; }
        .color-blue { color: #3b82f6; }
        .color-red { color: #ef4444; }

        /* CHARTS SECTION */
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
            margin-bottom: 28px;
        }

        .chart-box {
            background: #ffffff;
            border-radius: 14px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        }

        .chart-box h5 {
            font-size: 15px;
            color: #0f172a;
            margin-bottom: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .chart-wrapper {
            position: relative;
            height: 220px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* CARD & TABLE */
        .card {
            background: #ffffff;
            border-radius: 14px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header h2 {
            font-size: 20px;
            color: #0f172a;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            background: #071e17;
            color: #ffffff;
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .top-btn:hover {
            background: #0c3326;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f8fafc;
            color: #475569;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 14px 16px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }

        table td {
            padding: 14px 16px;
            font-size: 13px;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
            text-align: center;
        }

        table tr:hover {
            background: #f8fafc;
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        .badge-active { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .badge-completed { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
        .badge-cancelled { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        .edit-btn {
            background: #fef3c7;
            color: #d97706;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 4px;
            display: inline-block;
        }

        .edit-btn:hover { background: #fde68a; }

        .delete-btn {
            background: #fef2f2;
            color: #dc2626;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .delete-btn:hover { background: #fee2e2; }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #94a3b8;
            font-size: 14px;
        }

        @media (max-width: 992px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .charts-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div>
            <div class="brand">
                <div class="brand-logo-icon"><i class="fas fa-leaf"></i></div>
                <div class="brand-text">
                    <h2>BUP</h2>
                    <span>Counselling & Wellness</span>
                </div>
            </div>

            <ul class="nav-list">
                <li class="nav-item">
                    <a href="admin.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="manage-users.php" class="nav-link"><i class="fas fa-users-gear"></i> User Management</a>
                </li>
                <li class="nav-item">
                    <a href="counsellors.php" class="nav-link"><i class="fas fa-user-md"></i> Counsellors</a>
                </li>
                <li class="nav-item active">
                    <a href="workshops.php" class="nav-link"><i class="fas fa-chalkboard-user"></i> Workshops</a>
                </li>
                <li class="nav-item">
                    <a href="appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a>
                </li>
                <li class="nav-item">
                    <a href="feedback.php" class="nav-link"><i class="fas fa-comments"></i> Feedback</a>
                </li>
                <li class="nav-item">
                    <a href="reports.php" class="nav-link"><i class="fas fa-chart-line"></i> Reports</a>
                </li>
                
                <li class="nav-item" style="margin-top: 15px;">
                    <a href="logout.php" class="nav-link" style="color: #ef4444;"><i class="fas fa-right-from-bracket"></i> Logout</a>
                </li>
            </ul>
        </div>

        <div class="sidebar-footer-text">
            "A Safer, Healthier, Stronger BUP"
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOP BAR -->
        <header class="topbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search workshop...">
            </div>

            <div class="topbar-right">
                <div class="date-badge">
                    <i class="fas fa-calendar-day"></i>
                    <span><?php echo date('l, d F Y'); ?></span>
                </div>

                <div class="user-profile-badge">
                    <div class="avatar">AD</div>
                    <div class="profile-info">
                        <h4><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></h4>
                        <p><?php echo htmlspecialchars($_SESSION['role'] ?? 'Administrator'); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- STATISTICS GRID -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3 class="color-dark"><?= $totalWorkshop ?></h3>
                <p>Total Workshops</p>
            </div>
            <div class="stat-card">
                <h3 class="color-green"><?= $activeWorkshop ?></h3>
                <p>Active</p>
            </div>
            <div class="stat-card">
                <h3 class="color-blue"><?= $completedWorkshop ?></h3>
                <p>Completed</p>
            </div>
            <div class="stat-card">
                <h3 class="color-red"><?= $cancelledWorkshop ?></h3>
                <p>Cancelled</p>
            </div>
        </div>

        <!-- CHARTS SECTION -->
        <div class="charts-grid">
            <div class="chart-box">
                <h5><i class="fa-solid fa-chart-column" style="color:#10b981;"></i> Workshop Status</h5>
                <div class="chart-wrapper">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
            <div class="chart-box">
                <h5><i class="fa-solid fa-chart-pie" style="color:#3b82f6;"></i> Distribution</h5>
                <div class="chart-wrapper">
                    <canvas id="pieChart"></canvas>
                </div>
            </div>
        </div>

        <!-- MAIN CARD & TABLE -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-chalkboard-user" style="color:#071e17;"></i> Workshop Management</h2>
                <a href="add-workshop.php" class="top-btn">
                    <i class="fas fa-plus"></i> Add Workshop
                </a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Venue</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td style="font-weight:600; color:#64748b;">#<?php echo htmlspecialchars($row['workshop_id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['venue']); ?></td>
                                    <td><?php echo htmlspecialchars($row['workshop_date']); ?></td>
                                    <td><?php echo htmlspecialchars($row['start_time']) . ' - ' . htmlspecialchars($row['end_time']); ?></td>
                                    <td><?php echo htmlspecialchars($row['capacity']); ?></td>
                                    <td>
                                        <?php if ($row['status'] == "ACTIVE") { ?>
                                            <span class="badge-status badge-active"><i class="fa-solid fa-circle" style="font-size:6px;"></i> ACTIVE</span>
                                        <?php } elseif ($row['status'] == "COMPLETED") { ?>
                                            <span class="badge-status badge-completed"><i class="fa-solid fa-circle" style="font-size:6px;"></i> COMPLETED</span>
                                        <?php } else { ?>
                                            <span class="badge-status badge-cancelled"><i class="fa-solid fa-circle" style="font-size:6px;"></i> CANCELLED</span>
                                        <?php } ?>
                                    </td>
                                    <td>
                                        <a href="edit-workshop.php?id=<?php echo $row['workshop_id']; ?>" class="edit-btn">
                                            <i class="fas fa-pen"></i> Edit
                                        </a>
                                        <a href="workshops.php?delete=<?php echo $row['workshop_id']; ?>" class="delete-btn" onclick="return confirm('Delete Workshop?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                <td colspan="8" class="no-data">No workshop found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</div>

<script>
// Chart Styling Constants
const colors = {
    active: '#10b981',
    completed: '#3b82f6',
    cancelled: '#ef4444'
};

// Modern Bar Chart
const barCtx = document.getElementById('barChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: ['Active', 'Completed', 'Cancelled'],
        datasets: [{
            label: 'Workshops',
            data: [<?= $activeWorkshop ?>, <?= $completedWorkshop ?>, <?= $cancelledWorkshop ?>],
            backgroundColor: [colors.active, colors.completed, colors.cancelled],
            borderRadius: 8,
            borderSkipped: false,
            barThickness: 32
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, font: { family: 'Plus Jakarta Sans', size: 11 } },
                grid: { color: '#f1f5f9' }
            },
            x: {
                ticks: { font: { family: 'Plus Jakarta Sans', size: 12, weight: '600' } },
                grid: { display: false }
            }
        }
    }
});

// Modern Doughnut (Pie) Chart
const pieCtx = document.getElementById('pieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'doughnut',
    data: {
        labels: ['Active', 'Completed', 'Cancelled'],
        datasets: [{
            data: [<?= $activeWorkshop ?>, <?= $completedWorkshop ?>, <?= $cancelledWorkshop ?>],
            backgroundColor: [colors.active, colors.completed, colors.cancelled],
            borderWidth: 3,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 12,
                    padding: 16,
                    font: { family: 'Plus Jakarta Sans', size: 12, weight: '500' }
                }
            }
        }
    }
});
</script>

</body>
</html>