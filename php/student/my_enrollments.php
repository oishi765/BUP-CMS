<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// Handle Unenroll / Cancel Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_enrollment'])) {
    $workshop_id = intval($_POST['workshop_id']);
    
    $delete_query = mysqli_query($conn, "DELETE FROM workshop_registrations WHERE workshop_id = '$workshop_id' AND user_id = '$user_id'");
    
    if ($delete_query) {
        $msg = "Successfully unenrolled from the workshop.";
        $msg_type = "success";
    } else {
        $msg = "Failed to unenroll. Error: " . mysqli_error($conn);
        $msg_type = "error";
    }
}

// Fetch User Profile
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$user_id'");
$user_data = ($user_query) ? mysqli_fetch_assoc($user_query) : null;
$display_name = !empty($user_data['full_name']) ? $user_data['full_name'] : ($user_data['username'] ?? 'Student');

// Fetch Enrolled Workshops
$enrolled_query = mysqli_query($conn, "
    SELECT w.*, r.registration_date 
    FROM workshop_registrations r 
    JOIN workshops w ON r.workshop_id = w.workshop_id 
    WHERE r.user_id = '$user_id' 
    ORDER BY r.registration_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Enrollments - BUP Counselling</title>
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

        /* Grid Layout */
        .workshop-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .card { background: #fff; border-radius: 14px; border: 1px solid #e2e8f0; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between; }
        .card-header h3 { font-size: 16px; color: #111827; margin-bottom: 8px; }
        .card-header p { font-size: 12px; color: #6b7280; line-height: 1.4; margin-bottom: 12px; }
        .info-list { font-size: 12px; color: #374151; margin-bottom: 15px; }
        .info-item { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
        .info-item i { color: #2d6a4f; width: 16px; }

        .status-badge { display: inline-block; background: #dcfce7; color: #166534; font-size: 11px; font-weight: bold; padding: 4px 10px; border-radius: 20px; margin-bottom: 12px; }

        .btn-cancel { width: 100%; padding: 9px; border: 1px solid #fca5a5; background: #fff5f5; color: #991b1b; border-radius: 8px; font-weight: bold; font-size: 12px; cursor: pointer; transition: 0.2s; }
        .btn-cancel:hover { background: #fee2e2; }

        /* Modal Details */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: #fff; padding: 25px; border-radius: 14px; width: 90%; max-width: 500px; position: relative; }
        .close-btn { position: absolute; top: 15px; right: 20px; font-size: 18px; cursor: pointer; color: #888; }

        .alert { padding: 12px 15px; border-radius: 8px; font-size: 13px; margin-bottom: 20px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .no-data { background: #fff; padding: 30px; border-radius: 12px; text-align: center; border: 1px solid #e2e8f0; color: #666; font-size: 14px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div>
        <div class="brand"><i class="fa-solid fa-heart-pulse"></i> Counselling & Wellness</div>
        <ul>
            <li><a href="student_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
            <li><a href="my_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
            <li><a href="counsellors.php"><i class="fa-solid fa-user-doctor"></i> Counsellors</a></li>
            
            <li><a href="myappointment.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
            <li><a href="myworkshop.php"><i class="fa-solid fa-graduation-cap"></i> Workshops</a></li>
            <li class="active"><a href="my_enrollments.php"><i class="fa-solid fa-book-open"></i> My Enrollments</a></li>
            <li><a href="counselling_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Counselling History</a></li>
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
            <h2 style="font-size: 20px; color: #111827;"><i class="fa-solid fa-book-open" style="color: #2d6a4f;"></i> My Enrolled Workshops</h2>
            <p style="font-size: 12px; color: #666;">View and manage your registered workshops</p>
        </div>
        <div class="user-profile">
            <div class="avatar"><?php echo strtoupper(substr($display_name, 0, 2)); ?></div>
            <div>
                <strong style="font-size: 13px;"><?php echo htmlspecialchars($display_name); ?></strong><br>
                <small style="color: #52b788;">Student</small>
            </div>
        </div>
    </div>

    <?php if(!empty($msg)): ?>
        <div class="alert alert-<?php echo $msg_type; ?>">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <!-- Enrolled Workshops Grid -->
    <div class="workshop-grid">
        <?php if($enrolled_query && mysqli_num_rows($enrolled_query) > 0): ?>
            <?php while($row = mysqli_fetch_assoc($enrolled_query)): 
                $w_date = isset($row['date']) ? date('M d, Y', strtotime($row['date'])) : (isset($row['start_date']) ? date('M d, Y', strtotime($row['start_date'])) : 'TBA');
                $reg_date = date('M d, Y', strtotime($row['registration_date']));
            ?>
                <div class="card">
                    <div>
                        <span class="status-badge"><i class="fa-solid fa-check-circle"></i> Enrolled</span>
                        <div class="card-header">
                            <h3><?php echo htmlspecialchars($row['title'] ?? 'Workshop'); ?></h3>
                            <p><?php echo htmlspecialchars(substr($row['description'] ?? '', 0, 90)) . '...'; ?></p>
                        </div>
                        <div class="info-list">
                            <div class="info-item"><i class="fa-solid fa-calendar"></i> <?php echo $w_date; ?></div>
                            <div class="info-item"><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($row['time'] ?? $row['start_time'] ?? '10:00 AM'); ?></div>
                            <div class="info-item"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($row['location'] ?? $row['venue'] ?? 'Auditorium'); ?></div>
                            <div class="info-item" style="color: #6b7280; font-size: 11px; margin-top: 8px;">
                                <i class="fa-solid fa-user-check"></i> Registered on: <?php echo $reg_date; ?>
                            </div>
                        </div>
                    </div>

                    <div>
                        <button type="button" onclick="openDetails(<?php echo htmlspecialchars(json_encode($row)); ?>)" style="background: none; border: none; color: #2d6a4f; font-size: 11px; font-weight: bold; cursor: pointer; margin-bottom: 12px;">View Details &rarr;</button>
                        
                        <form method="POST" action="my_enrollments.php" onsubmit="return confirm('Are you sure you want to cancel enrollment for this workshop?');">
                            <input type="hidden" name="workshop_id" value="<?php echo $row['workshop_id']; ?>">
                            <button type="submit" name="cancel_enrollment" class="btn-cancel"><i class="fa-solid fa-xmark"></i> Cancel Enrollment</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="no-data" style="grid-column: 1 / -1;">
                <i class="fa-solid fa-folder-open" style="font-size: 30px; color: #a0aec0; margin-bottom: 10px; display: block;"></i>
                You haven't enrolled in any workshops yet. <a href="myworkshop.php" style="color: #2d6a4f; font-weight: bold; text-decoration: none;">Browse Workshops</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Details Modal -->
<div class="modal" id="detailsModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeDetails()">&times;</span>
        <h3 id="m_title" style="margin-bottom: 10px; font-size: 16px;"></h3>
        <p id="m_desc" style="font-size: 12px; color: #555; margin-bottom: 15px; line-height: 1.5;"></p>
        <div class="info-list">
            <div class="info-item"><i class="fa-solid fa-calendar"></i> <span id="m_date"></span></div>
            <div class="info-item"><i class="fa-solid fa-clock"></i> <span id="m_time"></span></div>
            <div class="info-item"><i class="fa-solid fa-location-dot"></i> <span id="m_location"></span></div>
        </div>
    </div>
</div>

<script>
function openDetails(data) {
    document.getElementById('m_title').innerText = data.title || 'Workshop Details';
    document.getElementById('m_desc').innerText = data.description || 'No description available.';
    document.getElementById('m_date').innerText = data.date || data.start_date || 'N/A';
    document.getElementById('m_time').innerText = data.time || data.start_time || 'N/A';
    document.getElementById('m_location').innerText = data.location || data.venue || 'N/A';
    document.getElementById('detailsModal').style.display = 'flex';
}

function closeDetails() {
    document.getElementById('detailsModal').style.display = 'none';
}
</script>

</body>
</html>