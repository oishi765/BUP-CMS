<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch User Profile
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$user_id'");
$user_data = ($user_query) ? mysqli_fetch_assoc($user_query) : null;
$display_name = !empty($user_data['full_name']) ? $user_data['full_name'] : ($user_data['username'] ?? 'Student');

// Fetch Counselling History Query
// Joining appointments with counsellors or users table to get counsellor name
$history_query = mysqli_query($conn, "
    SELECT a.*, c.name AS counsellor_name, c.specialization 
    FROM appointments a 
    LEFT JOIN counsellors c ON a.counsellor_id = c.counsellor_id 
    WHERE a.user_id = '$user_id' 
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counselling History - BUP Counselling</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f4f7f5; color: #2c3e50; min-height: 100vh; }

        /* Sidebar */
        .sidebar { width: 260px; height: 100vh; background-color: #081c15; color: #fff; position: fixed; display: flex; flex-direction: column; justify-content: space-between; padding: 20px 0; }
        .sidebar .brand { padding: 0 20px; font-size: 15px; font-weight: bold; margin-bottom: 20px; color: #52b788; display: flex; align-items: center; gap: 10px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li a { display: flex; align-items: center; padding: 10px 25px; color: #d1e0d7; text-decoration: none; font-size: 13px; transition: 0.3s; }
        .sidebar ul li a i { margin-right: 12px; width: 18px; }
        .sidebar ul li.active a, .sidebar ul li a:hover { background-color: #1b4332; color: #fff; border-left: 4px solid #52b788; }
        .sidebar-footer { padding: 15px 20px; font-size: 11px; color: #74a892; border-top: 1px solid #1b4332; }

        /* Main Content */
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 25px 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-profile .avatar { width: 38px; height: 38px; background: #2d6a4f; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 13px; }

        /* Table Card */
        .card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); overflow-x: auto; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        th, td { padding: 14px 16px; border-bottom: 1px solid #edf2f7; }
        th { background-color: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background-color: #f8fafc; }

        /* Badges */
        .badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; text-transform: capitalize; }
        .badge-completed { background-color: #dcfce7; color: #166534; }
        .badge-pending { background-color: #fef9c3; color: #854d0e; }
        .badge-cancelled { background-color: #fee2e2; color: #991b1b; }
        .badge-approved { background-color: #e0f2fe; color: #075985; }

        .no-data { text-align: center; color: #64748b; padding: 40px 0; font-size: 14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="brand"><i class="fa-solid fa-heart-pulse"></i> Counselling & Wellness</div>
        <ul>
            <li><a href="student_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="my_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
            <
            <li><a href="myappointment.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
            <li><a href="myworkshop.php"><i class="fa-solid fa-graduation-cap"></i> Workshops</a></li>
            <li><a href="my_enrollments.php"><i class="fa-solid fa-book-open"></i> My Enrollments</a></li>
            <li class="active"><a href="counselling_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Counselling History</a></li>
            <li><a href="my_feedback.php"><i class="fa-solid fa-comment-dots"></i> Feedback</a></li>
            <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>
    <div class="sidebar-footer">
        <strong>Bangladesh University of Professionals</strong><br>
        <span style="font-size: 10px; opacity: 0.8;">"A Safer, Healthier, Stronger BUP"</span>
    </div>
</div>

<div class="main-content">
    <div class="header">
        <div>
            <h2 style="font-size: 20px; color: #111827;"><i class="fa-solid fa-clock-rotate-left" style="color: #2d6a4f;"></i> Counselling History</h2>
            <p style="font-size: 12px; color: #666;">View your previous counselling sessions and status</p>
        </div>
        <div class="user-profile">
            <div class="avatar"><?php echo strtoupper(substr($display_name, 0, 2)); ?></div>
            <div>
                <strong style="font-size: 13px;"><?php echo htmlspecialchars($display_name); ?></strong><br>
                <small style="color: #52b788;">Student</small>
            </div>
        </div>
    </div>

    <!-- History Table Card -->
    <div class="card">
        <?php if ($history_query && mysqli_num_rows($history_query) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Counsellor</th>
                        <th>Counselling Type / Reason</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sl = 1;
                    while ($row = mysqli_fetch_assoc($history_query)): 
                        $status = strtolower($row['status'] ?? 'pending');
                        $badge_class = 'badge-pending';
                        if ($status == 'completed') $badge_class = 'badge-completed';
                        elseif ($status == 'cancelled' || $status == 'rejected') $badge_class = 'badge-cancelled';
                        elseif ($status == 'approved' || $status == 'confirmed') $badge_class = 'badge-approved';

                        $counsellor = !empty($row['counsellor_name']) ? $row['counsellor_name'] : 'Assigned Counsellor';
                        $date = isset($row['appointment_date']) ? date('M d, Y', strtotime($row['appointment_date'])) : 'N/A';
                        $time = isset($row['appointment_time']) ? date('h:i A', strtotime($row['appointment_time'])) : 'N/A';
                    ?>
                        <tr>
                            <td><?php echo $sl++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($counsellor); ?></strong><br>
                                <small style="color: #64748b;"><?php echo htmlspecialchars($row['specialization'] ?? 'Mental Health'); ?></small>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($row['counselling_type'] ?? $row['reason'] ?? 'General Consultation'); ?>
                            </td>
                            <td>
                                <i class="fa-regular fa-calendar" style="color: #2d6a4f;"></i> <?php echo $date; ?><br>
                                <small style="color: #64748b;"><i class="fa-regular fa-clock"></i> <?php echo $time; ?></small>
                            </td>
                            <td>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($status); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <i class="fa-solid fa-folder-open" style="font-size: 36px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                No counselling history records found.
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>