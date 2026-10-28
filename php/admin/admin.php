<?php
session_start();
require_once "../config/db.php";


// -------------------------------------------------------------
// 1. TOP STATS CARDS DATA (Dynamic Fetching)
// -------------------------------------------------------------

// Total Counts
$res_u = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
$total_users = ($res_u) ? mysqli_fetch_assoc($res_u)['total'] : 0;

$res_c = mysqli_query($conn, "SELECT COUNT(*) AS total FROM counsellors");
$total_counsellors = ($res_c) ? mysqli_fetch_assoc($res_c)['total'] : 0;

$res_w = mysqli_query($conn, "SELECT COUNT(*) AS total FROM workshops");
$total_workshops = ($res_w) ? mysqli_fetch_assoc($res_w)['total'] : 0;

$res_a = mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments");
$total_appointments = ($res_a) ? mysqli_fetch_assoc($res_a)['total'] : 0;

// Monthly Growth Calculations (Created this month)
// (Note: Assumes tables have 'created_at' or 'date' column)
$res_um = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$users_this_month = ($res_um) ? mysqli_fetch_assoc($res_um)['total'] : 0;

$res_cm = mysqli_query($conn, "SELECT COUNT(*) AS total FROM counsellors WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$counsellors_this_month = ($res_cm) ? mysqli_fetch_assoc($res_cm)['total'] : 0;

$res_wm = mysqli_query($conn, "SELECT COUNT(*) AS total FROM workshops WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$workshops_this_month = ($res_wm) ? mysqli_fetch_assoc($res_wm)['total'] : 0;

$res_am = mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
$appointments_this_month = ($res_am) ? mysqli_fetch_assoc($res_am)['total'] : 0;


// -------------------------------------------------------------
// 2. USER REGISTRATION TREND CHART (Dynamic Monthly Users)
// -------------------------------------------------------------
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthly_users = array_fill(0, 12, 0);

$trend_res = mysqli_query($conn, "SELECT MONTH(created_at) AS month_num, COUNT(*) AS count FROM users WHERE YEAR(created_at) = YEAR(CURRENT_DATE()) GROUP BY MONTH(created_at)");
if ($trend_res) {
    while ($row = mysqli_fetch_assoc($trend_res)) {
        $idx = (int)$row['month_num'] - 1;
        if ($idx >= 0 && $idx < 12) $monthly_users[$idx] = (int)$row['count'];
    }
}

// SVG Sparkline / Curve Points Generation
$max_u = max(1, max($monthly_users));
$pts = [];
$x_step = 560 / 11;
foreach ($monthly_users as $i => $val) {
    $x = $i * $x_step;
    $y = 120 - (($val / $max_u) * 90);
    $pts[] = "$x,$y";
}
$polyline_pts = implode(" ", $pts);


// -------------------------------------------------------------
// 3. USER ROLE DISTRIBUTION (From students, counsellors, faculty tables)
// -------------------------------------------------------------
$res_st = mysqli_query($conn, "SELECT COUNT(*) AS total FROM students");
$students_cnt = ($res_st) ? mysqli_fetch_assoc($res_st)['total'] : 0;

$counsellors_cnt = $total_counsellors;

$res_fc = mysqli_query($conn, "SELECT COUNT(*) AS total FROM faculty");
$faculty_cnt = ($res_fc) ? mysqli_fetch_assoc($res_fc)['total'] : 0;

$role_total = max(1, $students_cnt + $counsellors_cnt + $faculty_cnt);
$p_student = round(($students_cnt / $role_total) * 100, 1);
$p_counsellor = round(($counsellors_cnt / $role_total) * 100, 1);
$p_faculty = round(($faculty_cnt / $role_total) * 100, 1);


// -------------------------------------------------------------
// 4. MONTHLY APPOINTMENTS BAR CHART
// -------------------------------------------------------------
$monthly_appts = array_fill(0, 12, 0);
$app_res = mysqli_query($conn, "SELECT MONTH(created_at) AS month_num, COUNT(*) AS count FROM appointments WHERE YEAR(created_at) = YEAR(CURRENT_DATE()) GROUP BY MONTH(created_at)");
if ($app_res) {
    while ($row = mysqli_fetch_assoc($app_res)) {
        $idx = (int)$row['month_num'] - 1;
        if ($idx >= 0 && $idx < 12) $monthly_appts[$idx] = (int)$row['count'];
    }
}
$max_app = max(1, max($monthly_appts));


// -------------------------------------------------------------
// 5. WORKSHOP OVERVIEW STATUS
// -------------------------------------------------------------
$res_ws_up = mysqli_query($conn, "SELECT COUNT(*) AS total FROM workshops WHERE LOWER(status) = 'upcoming' OR LOWER(status) = 'active'");
$upcoming_ws = ($res_ws_up) ? mysqli_fetch_assoc($res_ws_up)['total'] : 0;

$res_ws_cm = mysqli_query($conn, "SELECT COUNT(*) AS total FROM workshops WHERE LOWER(status) = 'completed'");
$completed_ws = ($res_ws_cm) ? mysqli_fetch_assoc($res_ws_cm)['total'] : 0;

$res_ws_cn = mysqli_query($conn, "SELECT COUNT(*) AS total FROM workshops WHERE LOWER(status) = 'cancelled'");
$cancelled_ws = ($res_ws_cn) ? mysqli_fetch_assoc($res_ws_cn)['total'] : 0;


// -------------------------------------------------------------
// 6. DYNAMIC RECENT ACTIVITIES (From Users & Appointments)
// -------------------------------------------------------------
$activities_query = "
    SELECT 'New user registered' AS title, CONCAT('User ', username, ' created an account') AS sub, 'fas fa-user-plus' AS icon, 'bg-emerald' AS color, created_at 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
";
$recent_activities = mysqli_query($conn, $activities_query);

function time_ago($datetime) {
    if (!$datetime) return 'Recently';
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d', $time);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BUP Counselling & Wellness - Administrator Dashboard</title>
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: #06110d;
            color: #d1d5db;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            background: radial-gradient(circle at 80% 20%, rgba(16, 185, 129, 0.08) 0%, transparent 40%),
                        radial-gradient(circle at 20% 80%, rgba(5, 150, 105, 0.05) 0%, transparent 40%);
        }

        /* SIDEBAR */
        .sidebar {
            width: 240px;
            background: rgba(8, 22, 17, 0.85);
            backdrop-filter: blur(15px);
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
            padding: 0 8px;
        }

        .brand-logo-icon {
            width: 36px;
            height: 36px;
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #10b981;
            font-size: 18px;
        }

        .brand-text h2 {
            font-size: 16px;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.5px;
        }

        .brand-text span {
            font-size: 10px;
            color: #6ee7b7;
            display: block;
        }

        .nav-list {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 4px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            color: #9ca3af;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .nav-link i {
            font-size: 15px;
            width: 20px;
            text-align: center;
        }

        .nav-link:hover, .nav-item.active .nav-link {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .sidebar-footer-text {
            font-size: 10px;
            color: #4b5563;
            text-align: center;
            font-style: italic;
        }

        /* MAIN CONTENT */
        .main-content {
            flex: 1;
            padding: 24px 32px;
            overflow-y: auto;
        }

        /* HEADER TOPBAR */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            width: 100%;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 8px 16px 8px 38px;
            color: #fff;
            font-size: 12px;
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 12px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .date-badge {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 6px 14px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: #9ca3af;
        }

        .date-badge i { color: #10b981; }

        .motto-tag {
            font-family: 'Georgia', serif;
            font-style: italic;
            color: #6ee7b7;
            font-size: 13px;
            opacity: 0.8;
            margin-right: 10px;
        }

        .user-profile-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 4px 12px 4px 4px;
            border-radius: 20px;
        }

        .avatar {
            width: 30px;
            height: 30px;
            background: #10b981;
            color: #042f2e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 11px;
        }

        .profile-info h4 { font-size: 12px; color: #fff; line-height: 1; }
        .profile-info p { font-size: 9px; color: #6b7280; margin-top: 2px; }

        /* WELCOME BANNER */
        .welcome-title h1 {
            font-size: 22px;
            color: #ffffff;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .welcome-title p {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 20px;
        }

        /* STAT CARDS GRID */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: rgba(13, 29, 23, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .stat-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        .icon-emerald { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .icon-blue { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .icon-purple { background: rgba(139, 92, 246, 0.15); color: #a78bfa; }
        .icon-amber { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }

        .stat-data span { font-size: 11px; color: #9ca3af; }
        .stat-data h2 { font-size: 22px; color: #fff; font-weight: 700; margin-top: 2px; }
        .stat-growth { font-size: 10px; color: #34d399; margin-top: 2px; display: flex; align-items: center; gap: 4px; }

        .sparkline-svg {
            width: 70px;
            height: 35px;
        }

        /* MIDDLE SECTION GRID */
        .middle-grid {
            display: grid;
            grid-template-columns: 2.2fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .card {
            background: rgba(13, 29, 23, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 12px;
            padding: 18px;
            backdrop-filter: blur(10px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .card-header h3 {
            font-size: 14px;
            color: #fff;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header h3 i { color: #10b981; }

        .filter-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #9ca3af;
            font-size: 10px;
            padding: 4px 8px;
            border-radius: 6px;
            outline: none;
        }

        /* MAIN TREND CHART */
        .trend-chart-container {
            height: 160px;
            width: 100%;
        }

        .month-axis {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 10px;
            color: #6b7280;
        }

        /* RECENT ACTIVITIES LIST */
        .activities-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .act-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .act-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .bg-emerald { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .act-text h5 { font-size: 12px; color: #e5e7eb; font-weight: 500; }
        .act-text p { font-size: 10px; color: #6b7280; }
        .act-time { font-size: 10px; color: #6b7280; }

        /* BOTTOM SECTION GRID (3 CARDS) */
        .bottom-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }

        /* DONUT CHART COMPONENT */
        .donut-container {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-top: 8px;
        }

        .donut-chart {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: conic-gradient(
                #10b981 0% <?php echo $p_student; ?>%, 
                #3b82f6 <?php echo $p_student; ?>% <?php echo $p_student + $p_counsellor; ?>%, 
                #8b5cf6 <?php echo $p_student + $p_counsellor; ?>% 100%
            );
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .donut-inner {
            width: 55px;
            height: 55px;
            background: #091712;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .donut-inner span { font-size: 14px; font-weight: 700; color: #fff; }
        .donut-inner small { font-size: 8px; color: #6b7280; }

        .legend-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
        }

        .legend-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
        }

        .legend-label { display: flex; align-items: center; gap: 6px; color: #9ca3af; }
        .dot { width: 8px; height: 8px; border-radius: 50%; }
        .dot-green { background: #10b981; }
        .dot-blue { background: #3b82f6; }
        .dot-purple { background: #8b5cf6; }

        /* CSS BAR CHART */
        .bars-container {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 80px;
            padding-top: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .bar-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            justify-content: flex-end;
            width: 6%;
        }

        .bar {
            width: 100%;
            background: rgba(16, 185, 129, 0.3);
            border-radius: 3px 3px 0 0;
            transition: all 0.3s;
        }

        .bar:hover { background: #10b981; }

        /* WORKSHOP SINGLE DONUT */
        .ws-donut {
            background: conic-gradient(
                #10b981 0% <?php echo $total_workshops > 0 ? ($upcoming_ws/$total_workshops)*100 : 0; ?>%, 
                #3b82f6 <?php echo $total_workshops > 0 ? ($upcoming_ws/$total_workshops)*100 : 0; ?>% <?php echo $total_workshops > 0 ? (($upcoming_ws+$completed_ws)/$total_workshops)*100 : 0; ?>%, 
                #ef4444 <?php echo $total_workshops > 0 ? (($upcoming_ws+$completed_ws)/$total_workshops)*100 : 0; ?>% 100%
            );
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
                <li class="nav-item active">
                    <a href="#" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="manage-users.php" class="nav-link"><i class="fas fa-users-gear"></i> User Management</a>
                </li>
                <li class="nav-item">
                    <a href="counsellors.php" class="nav-link"><i class="fas fa-user-md"></i> Counsellors</a>
                </li>
                <li class="nav-item">
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
    <a href="logout.php" class="nav-link" style="color: #ef4444;">
        <i class="fas fa-right-from-bracket"></i> Logout
    </a>
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
                <input type="text" placeholder="Search anything...">
            </div>

            <div class="topbar-right">
                <div class="date-badge">
                    <i class="fas fa-calendar-day"></i>
                    <span><?php echo date('l, d F Y'); ?></span>
                </div>

                <span class="motto-tag">Better Minds, Brighter Tomorrows <i class="fas fa-leaf"></i></span>

                <div class="user-profile-badge">
                    <div class="avatar">AD</div>
                    <div class="profile-info">
                        <h4><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></h4>
                        <p><?php echo htmlspecialchars($_SESSION['role'] ?? 'Administrator'); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <!-- WELCOME HEADER -->
        <div class="welcome-title">
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>! 👋</h1>
            <p>Manage, Monitor, Make a Difference.</p>
        </div>

        <!-- 4 TOP STAT CARDS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-left">
                    <div class="stat-icon icon-emerald"><i class="fas fa-users"></i></div>
                    <div class="stat-data">
                        <span>Total Users</span>
                        <h2><?php echo $total_users; ?></h2>
                        <div class="stat-growth"><i class="fas fa-arrow-up"></i> +<?php echo $users_this_month; ?> this month</div>
                    </div>
                </div>
                <svg class="sparkline-svg" viewBox="0 0 100 40">
                    <path d="M0 30 Q 25 10, 50 25 T 100 5" fill="none" stroke="#10b981" stroke-width="2"/>
                </svg>
            </div>

            <div class="stat-card">
                <div class="stat-left">
                    <div class="stat-icon icon-blue"><i class="fas fa-user-md"></i></div>
                    <div class="stat-data">
                        <span>Counsellors</span>
                        <h2><?php echo $total_counsellors; ?></h2>
                        <div class="stat-growth"><i class="fas fa-arrow-up"></i> +<?php echo $counsellors_this_month; ?> this month</div>
                    </div>
                </div>
                <svg class="sparkline-svg" viewBox="0 0 100 40">
                    <path d="M0 35 Q 30 15, 60 28 T 100 10" fill="none" stroke="#3b82f6" stroke-width="2"/>
                </svg>
            </div>

            <div class="stat-card">
                <div class="stat-left">
                    <div class="stat-icon icon-purple"><i class="fas fa-chalkboard-user"></i></div>
                    <div class="stat-data">
                        <span>Workshops</span>
                        <h2><?php echo $total_workshops; ?></h2>
                        <div class="stat-growth"><i class="fas fa-arrow-up"></i> +<?php echo $workshops_this_month; ?> this month</div>
                    </div>
                </div>
                <svg class="sparkline-svg" viewBox="0 0 100 40">
                    <path d="M0 25 Q 20 35, 50 10 T 100 20" fill="none" stroke="#8b5cf6" stroke-width="2"/>
                </svg>
            </div>

            <div class="stat-card">
                <div class="stat-left">
                    <div class="stat-icon icon-amber"><i class="fas fa-clipboard-list"></i></div>
                    <div class="stat-data">
                        <span>Appointments</span>
                        <h2><?php echo $total_appointments; ?></h2>
                        <div class="stat-growth"><i class="fas fa-arrow-up"></i> +<?php echo $appointments_this_month; ?> this month</div>
                    </div>
                </div>
                <svg class="sparkline-svg" viewBox="0 0 100 40">
                    <path d="M0 20 Q 35 5, 70 30 T 100 12" fill="none" stroke="#f59e0b" stroke-width="2"/>
                </svg>
            </div>
        </div>

        <!-- MIDDLE SECTION: CHART + RECENT ACTIVITIES -->
        <div class="middle-grid">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-line"></i> User Registration Trend</h3>
                    <select class="filter-select"><option>This Year</option></select>
                </div>
                <div class="trend-chart-container">
                    <svg width="100%" height="100%" viewBox="0 0 560 130" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="grad" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#10b981" stop-opacity="0.3"/>
                                <stop offset="100%" stop-color="#10b981" stop-opacity="0.0"/>
                            </linearGradient>
                        </defs>
                        <polygon points="0,130 <?php echo $polyline_pts; ?> 560,130" fill="url(#grad)" />
                        <polyline fill="none" stroke="#10b981" stroke-width="3" points="<?php echo $polyline_pts; ?>" />
                    </svg>
                </div>
                <div class="month-axis">
                    <?php foreach ($months as $m): ?>
                        <span><?php echo $m; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-history"></i> Recent Activities</h3>
                    <a href="#" style="font-size: 10px; color: #10b981; text-decoration: none;">View All →</a>
                </div>
                <div class="activities-list">
                    <?php if ($recent_activities && mysqli_num_rows($recent_activities) > 0): ?>
                        <?php while ($act = mysqli_fetch_assoc($recent_activities)): ?>
                            <div class="activity-item">
                                <div class="act-left">
                                    <div class="act-icon <?php echo $act['color']; ?>"><i class="<?php echo $act['icon']; ?>"></i></div>
                                    <div class="act-text">
                                        <h5><?php echo htmlspecialchars($act['title']); ?></h5>
                                        <p><?php echo htmlspecialchars($act['sub']); ?></p>
                                    </div>
                                </div>
                                <span class="act-time"><?php echo time_ago($act['created_at']); ?></span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="font-size: 11px; color: #6b7280;">No recent activities found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- BOTTOM SECTION: 3 CARDS -->
        <div class="bottom-grid">
            
            <!-- USER ROLE DISTRIBUTION -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-pie"></i> User Role Distribution</h3>
                </div>
                <div class="donut-container">
                    <div class="donut-chart">
                        <div class="donut-inner">
                            <span><?php echo $students_cnt + $counsellors_cnt + $faculty_cnt; ?></span>
                            <small>Total</small>
                        </div>
                    </div>
                    <div class="legend-list">
                        <div class="legend-item">
                            <span class="legend-label"><i class="dot dot-green"></i> Students</span>
                            <span style="color:#fff; font-weight:600;"><?php echo $students_cnt; ?> (<?php echo $p_student; ?>%)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-label"><i class="dot dot-blue"></i> Counsellors</span>
                            <span style="color:#fff; font-weight:600;"><?php echo $counsellors_cnt; ?> (<?php echo $p_counsellor; ?>%)</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-label"><i class="dot dot-purple"></i> Faculty</span>
                            <span style="color:#fff; font-weight:600;"><?php echo $faculty_cnt; ?> (<?php echo $p_faculty; ?>%)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MONTHLY APPOINTMENTS -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-chart-bar"></i> Monthly Appointments</h3>
                </div>
                <div class="bars-container">
                    <?php foreach ($monthly_appts as $idx => $cnt): 
                        $height = ($cnt / $max_app) * 100;
                    ?>
                        <div class="bar-wrapper" title="<?php echo $months[$idx] . ': ' . $cnt; ?>">
                            <div class="bar" style="height: <?php echo max(5, $height); ?>%;"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="month-axis" style="margin-top: 6px;">
                    <?php foreach ($months as $m): ?>
                        <span><?php echo substr($m, 0, 1); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- WORKSHOP OVERVIEW -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-book-open"></i> Workshop Overview</h3>
                </div>
                <div class="donut-container">
                    <div class="donut-chart ws-donut">
                        <div class="donut-inner">
                            <span><?php echo $total_workshops; ?></span>
                            <small>Total</small>
                        </div>
                    </div>
                    <div class="legend-list">
                        <div class="legend-item">
                            <span class="legend-label"><i class="dot dot-green"></i> Upcoming</span>
                            <span style="color:#fff; font-weight:600;"><?php echo $upcoming_ws; ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-label"><i class="dot dot-blue"></i> Completed</span>
                            <span style="color:#fff; font-weight:600;"><?php echo $completed_ws; ?></span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-label"><i class="dot" style="background:#ef4444;"></i> Cancelled</span>
                            <span style="color:#fff; font-weight:600;"><?php echo $cancelled_ws; ?></span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </main>

</div>

</body>
</html>