<?php
session_start();
require_once "../config/db.php";

// 1. Session Check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_role = $_SESSION['role'] ?? 'STUDENT';
$logged_user_id = $_SESSION['user_id'];

/* Delete Counsellor (Only for ADMIN) */
if (isset($_GET['delete']) && $user_role === 'ADMIN') {
    $id = mysqli_real_escape_string($conn, $_GET['delete']);
    mysqli_query($conn, "DELETE FROM counsellors WHERE counsellor_id='$id'");
    header("Location: counsellors.php");
    exit();
}

// Fetch Counsellors Data
$result = mysqli_query($conn, "SELECT * FROM counsellors ORDER BY counsellor_id DESC");

// Fetch User Profile Name
$user_query = mysqli_query($conn, "SELECT full_name, username FROM users WHERE user_id = '$logged_user_id'");
$user_data = mysqli_fetch_assoc($user_query);
$display_name = !empty($user_data['full_name']) ? $user_data['full_name'] : ($user_data['username'] ?? 'User');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counsellors - BUP Counselling & Wellness</title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        <?php if ($user_role === 'ADMIN'): ?>
        /* ================= ADMIN STYLES ================= */
        body { background-color: #06110d; color: #d1d5db; min-height: 100vh; }
        .dashboard-container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background: #081611; border-right: 1px solid rgba(255, 255, 255, 0.05); padding: 24px 16px; display: flex; flex-direction: column; justify-content: space-between; }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 30px; padding: 0 8px; }
        .brand-logo-icon { width: 36px; height: 36px; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #10b981; font-size: 18px; }
        .brand-text h2 { font-size: 16px; font-weight: 700; color: #ffffff; }
        .brand-text span { font-size: 10px; color: #6ee7b7; display: block; }
        .nav-list { list-style: none; }
        .nav-item { margin-bottom: 4px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 10px 14px; color: #9ca3af; text-decoration: none; font-size: 13px; font-weight: 500; border-radius: 8px; transition: all 0.2s ease; }
        .nav-link i { font-size: 15px; width: 20px; text-align: center; }
        .nav-link:hover, .nav-item.active .nav-link { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.2); }
        .sidebar-footer-text { font-size: 10px; color: #4b5563; text-align: center; font-style: italic; }
        .main-content { flex: 1; padding: 24px 32px; background: #f4f7f6; color: #333333; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .search-box { position: relative; width: 300px; }
        .search-box input { width: 100%; background: #ffffff; border: 1px solid #cccccc; border-radius: 20px; padding: 8px 16px 8px 38px; color: #333; font-size: 12px; outline: none; }
        .search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #888888; font-size: 12px; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .date-badge { background: #ffffff; border: 1px solid #dddddd; padding: 6px 14px; border-radius: 8px; display: flex; align-items: center; gap: 8px; font-size: 11px; color: #555555; }
        .date-badge i { color: #198754; }
        .user-profile-badge { display: flex; align-items: center; gap: 10px; background: #ffffff; border: 1px solid #dddddd; padding: 4px 12px 4px 4px; border-radius: 20px; }
        .avatar { width: 30px; height: 30px; background: #198754; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 11px; }
        .profile-info h4 { font-size: 12px; color: #333; line-height: 1; }
        .profile-info p { font-size: 9px; color: #777; margin-top: 2px; }
        .card { background: #ffffff; border-radius: 10px; padding: 24px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-header h2 { font-size: 20px; color: #198754; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .top-btn { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; background: #198754; color: #ffffff; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: bold; }
        .top-btn:hover { background: #146c43; }
        .table-responsive { width: 100%; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        table th { background: #198754; color: #ffffff; font-size: 13px; font-weight: bold; padding: 12px 15px; text-align: center; }
        table td { padding: 12px 15px; font-size: 13px; color: #444444; border: 1px solid #eeeeee; text-align: center; }
        table tr:nth-child(even) { background: #f9f9f9; }
        .edit-btn { background: #ff9800; color: #ffffff; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: bold; margin-right: 4px; display: inline-block; }
        .delete-btn { background: #dc3545; color: #ffffff; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 12px; font-weight: bold; display: inline-block; }

        <?php else: ?>
        /* ================= USER / STUDENT STYLES (MODERN DESIGN) ================= */
        body { 
            display: flex; 
            background-color: #f4f7f5; 
            color: #2c3e50; 
        }

        .sidebar { 
            width: 260px; 
            height: 100vh; 
            background-color: #081c15; 
            color: #fff; 
            position: fixed; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
            padding: 20px 0; 
        }

        .sidebar .brand { 
            padding: 0 20px; 
            font-size: 16px; 
            font-weight: bold; 
            margin-bottom: 25px; 
            color: #52b788; 
            display: flex; 
            align-items: center; 
            gap: 10px; 
        }

        .sidebar ul { list-style: none; }
        .sidebar ul li a { 
            display: flex; 
            align-items: center; 
            padding: 12px 25px; 
            color: #d1e0d7; 
            text-decoration: none; 
            font-size: 13px; 
            transition: 0.3s; 
        }
        .sidebar ul li a i { margin-right: 12px; width: 18px; }
        .sidebar ul li.active a, .sidebar ul li a:hover { 
            background-color: #1b4332; 
            color: #fff; 
            border-left: 4px solid #52b788; 
        }
        .sidebar-footer { 
            padding: 20px; 
            font-size: 11px; 
            color: #74a892; 
            border-top: 1px solid #1b4332; 
        }

        .main-content { 
            margin-left: 260px; 
            width: calc(100% - 260px); 
            padding: 30px 40px; 
        }

        .header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 30px; 
        }

        .search-bar { 
            background: #ffffff; 
            padding: 10px 18px; 
            border-radius: 25px; 
            border: 1px solid #e2e8f0; 
            width: 320px; 
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .search-bar input { border: none; outline: none; margin-left: 8px; width: 85%; font-size: 13px; }

        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-profile .avatar { 
            width: 40px; 
            height: 40px; 
            background: #2d6a4f; 
            color: white; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-weight: bold; 
            font-size: 14px; 
            box-shadow: 0 2px 8px rgba(45, 106, 79, 0.3);
        }

        /* --- ENHANCED COUNSELLOR CARDS GRID --- */
        .counsellor-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); 
            gap: 24px; 
            margin-top: 20px; 
        }

        .counsellor-card { 
            background: #ffffff; 
            border-radius: 16px; 
            padding: 25px 20px; 
            border: 1px solid #e8f0ec; 
            text-align: center; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.03); 
            transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); 
            position: relative;
            overflow: hidden;
        }

        .counsellor-card:hover { 
            transform: translateY(-7px); 
            box-shadow: 0 15px 30px rgba(45, 106, 79, 0.12); 
            border-color: #2d6a4f;
        }

        .counsellor-avatar { 
            width: 75px; 
            height: 75px; 
            background: linear-gradient(135deg, #1b4332, #2d6a4f); 
            color: #52b788; 
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 28px; 
            margin: 0 auto 16px; 
            box-shadow: 0 4px 12px rgba(27, 67, 50, 0.2);
        }

        .counsellor-card h3 { 
            font-size: 17px; 
            color: #111827; 
            font-weight: 700;
            margin-bottom: 6px; 
        }

        .counsellor-card .spec { 
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32; 
            font-weight: 600; 
            font-size: 11px; 
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 16px; 
        }

        .info-list {
            background: #f8faf9;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
        }

        .info-list p { 
            font-size: 12px; 
            color: #4b5563; 
            margin-bottom: 6px; 
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .info-list p:last-child { margin-bottom: 0; }
        .info-list p i { color: #2d6a4f; }

        .btn-book { 
            display: flex; 
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #2d6a4f; 
            color: white; 
            padding: 10px 18px; 
            border-radius: 10px; 
            text-decoration: none; 
            font-size: 13px; 
            font-weight: 600; 
            transition: all 0.3s ease; 
            box-shadow: 0 4px 10px rgba(45, 106, 79, 0.2);
        }

        .btn-book:hover { 
            background: #1b4332; 
            box-shadow: 0 6px 15px rgba(27, 67, 50, 0.35);
        }
        <?php endif; ?>

        .no-data { text-align: center; padding: 30px; color: #777777; font-size: 14px; }
    </style>
</head>
<body>

<?php if ($user_role === 'ADMIN'): ?>
<!-- ================= ADMIN LAYOUT ================= -->
<div class="dashboard-container">
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
                <li class="nav-item"><a href="admin.php" class="nav-link"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li class="nav-item"><a href="manage-users.php" class="nav-link"><i class="fas fa-users-gear"></i> User Management</a></li>
                <li class="nav-item active"><a href="counsellors.php" class="nav-link"><i class="fas fa-user-md"></i> Counsellors</a></li>
                <li class="nav-item"><a href="workshops.php" class="nav-link"><i class="fas fa-chalkboard-user"></i> Workshops</a></li>
                <li class="nav-item"><a href="appointments.php" class="nav-link"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                <li class="nav-item"><a href="feedback.php" class="nav-link"><i class="fas fa-comments"></i> Feedback</a></li>
                <li class="nav-item"><a href="reports.php" class="nav-link"><i class="fas fa-chart-line"></i> Reports</a></li>
                <li class="nav-item" style="margin-top: 15px;"><a href="logout.php" class="nav-link" style="color: #ef4444;"><i class="fas fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer-text">"A Safer, Healthier, Stronger BUP"</div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search counsellor...">
            </div>
            <div class="topbar-right">
                <div class="date-badge">
                    <i class="fas fa-calendar-day"></i>
                    <span><?php echo date('l, d F Y'); ?></span>
                </div>
                <div class="user-profile-badge">
                    <div class="avatar"><?php echo strtoupper(substr($display_name, 0, 2)); ?></div>
                    <div class="profile-info">
                        <h4><?php echo htmlspecialchars($display_name); ?></h4>
                        <p><?php echo htmlspecialchars($user_role); ?></p>
                    </div>
                </div>
            </div>
        </header>

        <div class="card">
            <div class="card-header">
                <h2><i class="fas fa-user-md"></i> Counsellor Management</h2>
                <a href="add-counsellor.php" class="top-btn"><i class="fas fa-plus"></i> Add Counsellor</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Experience</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($row['counsellor_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['specialization']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['experience_years'] ?? '0'); ?> Years</td>
                                    <td>
                                        <a href="edit-counsellor.php?id=<?php echo $row['counsellor_id']; ?>" class="edit-btn"><i class="fas fa-pen"></i> Edit</a>
                                        <a href="counsellors.php?delete=<?php echo $row['counsellor_id']; ?>" class="delete-btn" onclick="return confirm('Delete this counsellor?')"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="no-data">No counsellor found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php else: ?>
<!-- ================= STUDENT & OTHER ROLES LAYOUT ================= -->
<div class="sidebar">
    <div>
        <div class="brand"><i class="fa-solid fa-heart-pulse"></i> Counselling & Wellness</div>
        <ul>
            <li><a href="<?php echo strtolower($user_role); ?>_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="my_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
            <li class="active"><a href="counsellors.php"><i class="fa-solid fa-user-doctor"></i> Counsellors</a></li>
            <li><a href="bookappointment.php"><i class="fa-solid fa-calendar-plus"></i> Book Appointment</a></li>
            <li><a href="appointments.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
            <li><a href="workshops.php"><i class="fa-solid fa-graduation-cap"></i> Workshops</a></li>
            <li><a href="my_enrollments.php"><i class="fa-solid fa-book-open"></i> My Enrollments</a></li>
            <li><a href="counselling_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Counselling History</a></li>
            <li><a href="feedback.php"><i class="fa-solid fa-comment-dots"></i> Feedback</a></li>
            <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>
    <div class="sidebar-footer">
        <strong>Bangladesh University of Professionals</strong><br>"A Safer, Healthier, Stronger BUP"
    </div>
</div>

<div class="main-content">
    <div class="header">
        <div class="search-bar">
            <i class="fa-solid fa-magnifying-glass" style="color: #2d6a4f;"></i>
            <input type="text" placeholder="Search counsellor...">
        </div>
        <div class="user-profile">
            <div class="avatar"><?php echo strtoupper(substr($display_name, 0, 2)); ?></div>
            <div>
                <strong style="font-size: 13px;"><?php echo htmlspecialchars($display_name); ?></strong><br>
                <small style="color: #52b788;"><?php echo ucfirst(strtolower($user_role)); ?></small>
            </div>
        </div>
    </div>

    <h2 style="color: #081c15; margin-bottom: 6px; font-weight: 700;"><i class="fa-solid fa-user-doctor" style="color: #2d6a4f;"></i> Available Counsellors</h2>
    <p style="color: #666; font-size: 13px; margin-bottom: 25px;">Browse our professional counsellors and book an appointment.</p>

    <div class="counsellor-grid">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="counsellor-card">
                    <div class="counsellor-avatar"><i class="fa-solid fa-user-doctor"></i></div>
                    <h3><?php echo htmlspecialchars($row['full_name']); ?></h3>
                    <span class="spec"><?php echo htmlspecialchars($row['specialization']); ?></span>
                    
                    <div class="info-list">
                        <p><i class="fa-solid fa-briefcase"></i> Experience: <?php echo htmlspecialchars($row['experience_years'] ?? '0'); ?> Years</p>
                        <p><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($row['email']); ?></p>
                        <p><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($row['phone']); ?></p>
                    </div>
                    
                    <?php if ($user_role === 'STUDENT'): ?>
                        <a href="bookappointment.php?counsellor_id=<?php echo $row['counsellor_id']; ?>" class="btn-book">
                            <i class="fa-solid fa-calendar-plus"></i> Book Appointment
                        </a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data" style="grid-column: 1/-1; background: #fff; border-radius: 12px; padding: 40px; border: 1px solid #e8f0ec;">No counsellors available right now.</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</body>
</html>