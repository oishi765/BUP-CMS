<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* Delete User */
if (isset($_GET['delete'])) {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);

    mysqli_query(
        $conn,
        "DELETE FROM users WHERE user_id='$id'"
    );

    header("Location: manage-users.php");
    exit();
}

$result = mysqli_query(
    $conn,
    "SELECT * FROM users ORDER BY user_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - BUP Counselling & Wellness</title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
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
            width: 250px;
            background: #081611;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
            padding: 24px 16px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
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

        /* MAIN CONTENT AREA - WHITE & GREEN COMBINATION */
        .main-content {
            flex: 1;
            padding: 24px 32px;
            background: #f4f7f6;
            color: #333333;
        }

        /* TOPBAR */
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
            background: #ffffff;
            border: 1px solid #cccccc;
            border-radius: 20px;
            padding: 8px 16px 8px 38px;
            color: #333;
            font-size: 12px;
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #888888;
            font-size: 12px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .date-badge {
            background: #ffffff;
            border: 1px solid #dddddd;
            padding: 6px 14px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: #555555;
        }

        .date-badge i { color: #198754; }

        .user-profile-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border: 1px solid #dddddd;
            padding: 4px 12px 4px 4px;
            border-radius: 20px;
        }

        .avatar {
            width: 30px;
            height: 30px;
            background: #198754;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 11px;
        }

        .profile-info h4 { font-size: 12px; color: #333; line-height: 1; }
        .profile-info p { font-size: 9px; color: #777; margin-top: 2px; }

        /* CARD & TABLE - WHITE AND GREEN */
        .card {
            background: #ffffff;
            border-radius: 10px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-header h2 {
            font-size: 20px;
            color: #198754;
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
            background: #198754;
            color: #ffffff;
            padding: 9px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            transition: background 0.2s;
        }

        .top-btn:hover {
            background: #146c43;
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
            background: #198754;
            color: #ffffff;
            font-size: 13px;
            font-weight: bold;
            padding: 12px 15px;
            text-align: center;
        }

        table td {
            padding: 12px 15px;
            font-size: 13px;
            color: #444444;
            border: 1px solid #eeeeee;
            text-align: center;
        }

        table tr:nth-child(even) {
            background: #f9f9f9;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            background: #e8f5e9;
            color: #198754;
            border: 1px solid #c8e6c9;
        }

        .edit-btn {
            background: #ff9800;
            color: #ffffff;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 4px;
            display: inline-block;
        }

        .edit-btn:hover {
            background: #e68a00;
        }

        .delete-btn {
            background: #dc3545;
            color: #ffffff;
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .delete-btn:hover {
            background: #bd2130;
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
                <li class="nav-item active">
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
                <input type="text" placeholder="Search user...">
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

        <!-- MAIN CARD -->
        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-users-gear"></i> User Management</h2>
                <a href="create-user.php" class="top-btn">
                    <i class="fas fa-user-plus"></i> Create New User
                </a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Student ID</th>
                            <th>Department</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($row['user_id']); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['full_name'] ?? 'N/A'); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['student_id'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['department'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td>
                                <span class="role-badge"><?php echo htmlspecialchars($row['role']); ?></span>
                            </td>
                            <td>
                                <a href="edit-user.php?id=<?php echo $row['user_id']; ?>" class="edit-btn">
                                    <i class="fas fa-pen"></i> Edit
                                </a>
                                <a href="manage-users.php?delete=<?php echo $row['user_id']; ?>" class="delete-btn" onclick="return confirm('Delete this user?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</div>

</body>
</html>