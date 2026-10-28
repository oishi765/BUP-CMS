<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Student';

// Gender Check Logic (Primary Key Column: user_id)
$gender = $_SESSION['gender'] ?? '';
if (empty($gender)) {
    $gender_query = mysqli_query($conn, "SELECT gender FROM users WHERE user_id = '$user_id'");
    if ($gender_query && mysqli_num_rows($gender_query) > 0) {
        $user_data = mysqli_fetch_assoc($gender_query);
        $gender = $user_data['gender'] ?? '';
    }
}


$gender = strtolower(trim($gender));

$banner_img = ($gender === 'male' || $gender === 'm')
    ? '../../images/m.png'
    : '../../images/f.png';

// User Initials
$name_parts = explode(' ', trim($student_name));
$initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// Stats Queries
$completed_sessions = 0;
$sessions_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM appointments WHERE user_id = '$user_id' AND status = 'Completed'");
if ($sessions_query) {
    $completed_sessions = mysqli_fetch_assoc($sessions_query)['total'];
}

$workshops_attended = 0;
$work_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM workshop_registrations WHERE user_id = '$user_id'");
if ($work_query) {
    $workshops_attended = mysqli_fetch_assoc($work_query)['total'];
}

// Mood Handler & Calculation
$mood_msg = "";
if (isset($_POST['save_mood'])) {
    $selected_mood = mysqli_real_escape_string($conn, $_POST['mood']);
    $insert_mood = "INSERT INTO user_moods (user_id, mood, date) VALUES ('$user_id', '$selected_mood', NOW())";
    if (mysqli_query($conn, $insert_mood)) {
        $mood_msg = "Mood saved!";
    }
}

$latest_mood = 'Positive';
$mood_score = 25; // Default score
$current_mood_query = mysqli_query($conn, "SELECT mood FROM user_moods WHERE user_id = '$user_id' ORDER BY id DESC LIMIT 1");
if ($current_mood_query && mysqli_num_rows($current_mood_query) > 0) {
    $latest_mood = mysqli_fetch_assoc($current_mood_query)['mood'];
    
    // Mood wise score assignment
    switch (strtolower($latest_mood)) {
        case 'great': $mood_score = 30; break;
        case 'good': case 'positive': $mood_score = 25; break;
        case 'okay': $mood_score = 20; break;
        case 'stressed': $mood_score = 10; break;
        case 'down': $mood_score = 5; break;
        default: $mood_score = 20;
    }
}

// DYNAMIC WELLNESS SCORE CALCULATION (Out of 100)
$session_score = min($completed_sessions * 6, 30);  // Max 30
$workshop_score = min($workshops_attended * 10, 40); // Max 40
$wellness_score = $mood_score + $session_score + $workshop_score;

// Recommended Workshop
$recommended_workshop = null;
$workshop_query = mysqli_query($conn, "SELECT * FROM workshops WHERE date >= CURDATE() ORDER BY date ASC LIMIT 1");
if ($workshop_query && mysqli_num_rows($workshop_query) > 0) {
    $recommended_workshop = mysqli_fetch_assoc($workshop_query);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - BUP Counselling</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f3f7f5; color: #2c3e50; }

        .sidebar { width: 250px; height: 100vh; background-color: #052618; color: #fff; position: fixed; display: flex; flex-direction: column; justify-content: space-between; padding: 20px 0; }
        .sidebar .brand { padding: 0 20px; font-size: 15px; font-weight: bold; color: #52b788; display: flex; align-items: center; gap: 10px; margin-bottom: 25px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li a { display: flex; align-items: center; padding: 11px 22px; color: #b7d6c3; text-decoration: none; font-size: 13px; transition: 0.3s; }
        .sidebar ul li a i { margin-right: 12px; width: 18px; }
        .sidebar ul li.active a, .sidebar ul li a:hover { background-color: #0e3d27; color: #fff; border-left: 4px solid #52b788; }
        .sidebar-footer { padding: 20px; font-size: 11px; color: #74a892; border-top: 1px solid #0e3d27; }

        .main-content { margin-left: 250px; width: calc(100% - 250px); padding: 20px 35px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; }
        .search-bar { background: #fff; padding: 8px 18px; border-radius: 20px; border: 1px solid #e1ebe5; width: 320px; display: flex; align-items: center; }
        .search-bar input { border: none; outline: none; margin-left: 10px; width: 100%; font-size: 13px; background: transparent; }
        
        .user-profile { display: flex; align-items: center; gap: 10px; }
        .avatar-circle { width: 36px; height: 36px; border-radius: 50%; background-color: #0e3d27; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 13px; }

        .hero-banner { 
            background-color: #e2f0e8; 
            border-radius: 16px; 
            display: flex; 
            justify-content: space-between;
            align-items: flex-end; 
            margin-bottom: 20px; 
            border: 1px solid #d0e4d7;
            overflow: hidden;
            height: 165px;
            position: relative;
        }
        .hero-text { padding: 30px; z-index: 2; position: relative; align-self: center; }
        .hero-text h2 { font-size: 22px; color: #081c15; margin-bottom: 6px; }
        .hero-text p.quote { font-size: 12px; font-weight: 600; color: #2d6a4f; margin-bottom: 4px; }
        .hero-text p.sub { font-size: 11px; color: #555; }
        
        .hero-bg-img { 
            position: absolute;
            bottom: 0;
            right: 0;
            height: 100%;
            max-height: 165px;
            width: auto;
            object-fit: contain;
            object-position: bottom right;
            z-index: 1;
        }

        .top-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: #fff; padding: 15px; border-radius: 14px; display: flex; align-items: center; gap: 12px; border: 1px solid #e1ebe5; }
        .stat-icon-wrapper { width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; }
        .icon-green { background: #d8f3dc; color: #2d6a4f; }
        .icon-blue { background: #e0f2fe; color: #0284c7; }
        .icon-purple { background: #f3e8ff; color: #9333ea; }
        .icon-orange { background: #ffedd5; color: #ea580c; }

        .main-dashboard-grid { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 18px; margin-bottom: 20px; }
        .card-box { background: #fff; border-radius: 16px; padding: 18px; border: 1px solid #e1ebe5; }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .card-header-flex h3 { font-size: 13px; color: #081c15; display: flex; align-items: center; gap: 6px; }

        .workshop-card-body { display: flex; gap: 12px; }
        .workshop-img { width: 85px; height: 85px; border-radius: 10px; object-fit: cover; }

        .mood-selector { display: flex; justify-content: space-between; margin: 12px 0; }
        .mood-selector label { cursor: pointer; text-align: center; }
        .mood-selector input[type="radio"] { display: none; }
        .mood-selector input[type="radio"]:checked + .emoji-box { transform: scale(1.25); }
        .emoji-box { font-size: 20px; display: block; transition: 0.2s; }

        .btn-save-mood { width: 100%; background: #1b4332; color: white; border: none; padding: 9px; border-radius: 8px; font-size: 12px; cursor: pointer; font-weight: 600; }
        .support-banner { background: #d8eae1; border-radius: 12px; padding: 12px 22px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #c2decb; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="brand"><i class="fa-solid fa-heart-pulse"></i> Counselling & Wellness</div>
            <ul>
                <li class="active"><a href="student_dashboard.php"><i class="fa-solid fa-house"></i> Dashboard</a></li>
                <li><a href="my_profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                <li><a href="counsellors.php"><i class="fa-solid fa-user-doctor"></i> Counsellors</a></li>
               
                <li><a href="myappointment.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
                <li><a href="myworkshop.php"><i class="fa-solid fa-graduation-cap"></i> Workshops</a></li>
                <li><a href="my_enrollments.php"><i class="fa-solid fa-book-open"></i> My Enrollments</a></li>
                <li><a href="counselling_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Counselling History</a></li>
                <li><a href="my_feedback.php"><i class="fa-solid fa-comment-dots"></i> Feedback</a></li>
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
                <i class="fa-solid fa-magnifying-glass" style="color: #74a892;"></i>
                <input type="text" placeholder="Search anything...">
            </div>
            <div class="user-profile">
                <i class="fa-regular fa-bell" style="color: #555; margin-right: 10px; cursor: pointer;"></i>
                <div class="avatar-circle"><?php echo $initials; ?></div>
                <div>
                    <strong style="font-size: 13px; color: #081c15; display: block;"><?php echo htmlspecialchars($student_name); ?></strong>
                    <small style="color: #74a892;">Student</small>
                </div>
            </div>
        </div>

        <div class="hero-banner">
            <div class="hero-text">
                <h2>Good Evening, <?php echo htmlspecialchars(explode(' ', trim($student_name))[0]); ?> 👋</h2>
                <p class="quote">"A healthier mind leads to a brighter tomorrow."</p>
                <p class="sub">Take care of yourself, you're doing great!</p>
            </div>
            <img src="<?php echo $banner_img; ?>" alt="Banner Image" class="hero-bg-img">
        </div>

        <div class="top-stats-grid">
            <div class="stat-card">
                <div class="stat-icon-wrapper icon-green"><i class="fa-regular fa-face-smile"></i></div>
                <div>
                    <h4 style="font-size: 11px; color:#74a892;">Current Mood</h4>
                    <h3 style="font-size: 14px; color:#081c15;"><?php echo htmlspecialchars($latest_mood); ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon-wrapper icon-blue"><i class="fa-regular fa-comment"></i></div>
                <div>
                    <h4 style="font-size: 11px; color:#74a892;">Completed Sessions</h4>
                    <h3 style="font-size: 14px; color:#081c15;"><?php echo $completed_sessions; ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon-wrapper icon-purple"><i class="fa-solid fa-users"></i></div>
                <div>
                    <h4 style="font-size: 11px; color:#74a892;">Workshops Attended</h4>
                    <h3 style="font-size: 14px; color:#081c15;"><?php echo $workshops_attended; ?></h3>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon-wrapper icon-orange"><i class="fa-regular fa-star"></i></div>
                <div>
                    <h4 style="font-size: 11px; color:#74a892;">Wellness Score</h4>
                    <h3 style="font-size: 14px; color:#081c15;"><?php echo $wellness_score; ?> / 100</h3>
                </div>
            </div>
        </div>

        <div class="main-dashboard-grid">
            <div class="card-box">
                <div class="card-header-flex">
                    <h3><i class="fa-regular fa-calendar" style="color: #2d6a4f;"></i> Recommended Workshop</h3>
                    <a href="myworkshop.php" style="color:#2d6a4f; font-size:11px; text-decoration:none;">View All →</a>
                </div>
                <div class="workshop-card-body">
                    <img src="../images/workshop1.png" alt="Workshop" class="workshop-img" onerror="this.src='https://via.placeholder.com/85';">
                    <div>
                        <h4 style="font-size: 12px; color:#081c15;"><?php echo htmlspecialchars($recommended_workshop['title'] ?? 'Managing Academic Stress'); ?></h4>
                        <p style="font-size: 11px; color:#666; margin-top:4px;"><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($recommended_workshop['time'] ?? '2:00 PM - 3:30 PM'); ?></p>
                        <p style="font-size: 11px; color:#666;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($recommended_workshop['location'] ?? 'Online (Zoom)'); ?></p>
                    </div>
                </div>
            </div>

            <div class="card-box">
                <div class="card-header-flex">
                    <h3><i class="fa-regular fa-lightbulb" style="color: #eab308;"></i> Wellness Tip of the Day</h3>
                </div>
                <p style="font-size: 12px; font-style: italic; color: #555; background: #fffdf5; padding: 12px; border-radius: 8px; border: 1px dashed #f3d5a3;">
                    "Take a 10-minute break today. A fresh mind works better."
                </p>
            </div>

            <div class="card-box">
                <div class="card-header-flex">
                    <h3><i class="fa-regular fa-heart" style="color: #e11d48;"></i> Quick Self Check-In</h3>
                </div>
                <?php if ($mood_msg): ?>
                    <p style="color: green; font-size: 11px; margin-bottom: 5px;"><?php echo $mood_msg; ?></p>
                <?php endif; ?>
                <form action="" method="POST">
                    <div class="mood-selector">
                        <label>
                            <input type="radio" name="mood" value="Great" required>
                            <span class="emoji-box">😄</span>
                        </label>
                        <label>
                            <input type="radio" name="mood" value="Good">
                            <span class="emoji-box">🙂</span>
                        </label>
                        <label>
                            <input type="radio" name="mood" value="Okay">
                            <span class="emoji-box">😐</span>
                        </label>
                        <label>
                            <input type="radio" name="mood" value="Stressed">
                            <span class="emoji-box">😫</span>
                        </label>
                        <label>
                            <input type="radio" name="mood" value="Down">
                            <span class="emoji-box">😔</span>
                        </label>
                    </div>
                    <button type="submit" name="save_mood" class="btn-save-mood">Save My Mood</button>
                </form>
            </div>
        </div>

        <div class="support-banner">
            <div>
                <strong style="font-size: 13px;">Need Someone to Talk To?</strong>
                <p style="font-size: 11px; color: #407b61;">You're not alone. Reach out for support anytime.</p>
            </div>
            <a href="counsellors.php" style="background: #0e3d27; color: #fff; padding: 8px 18px; border-radius: 20px; text-decoration: none; font-size: 12px;"><i class="fa-regular fa-comment-dots"></i> Contact Support</a>
        </div>
    </div>

</body>
</html>