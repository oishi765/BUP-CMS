<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$counsellor_id = isset($_GET['counsellor_id']) ? intval($_GET['counsellor_id']) : 7;

// Fetch Counsellor Details
$c_query = mysqli_query($conn, "SELECT * FROM counsellors WHERE counsellor_id = '$counsellor_id'");
$counsellor = mysqli_fetch_assoc($c_query);

if (!$counsellor) {
    $c_query = mysqli_query($conn, "SELECT * FROM counsellors LIMIT 1");
    $counsellor = mysqli_fetch_assoc($c_query);
    $counsellor_id = $counsellor['counsellor_id'] ?? 1;
}

// Handle Form Submission
$booking_success = false;
$booking_error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $appointment_date = mysqli_real_escape_string($conn, $_POST['appointment_date']);
    $appointment_time = mysqli_real_escape_string($conn, $_POST['appointment_time']);
    $session_type = mysqli_real_escape_string($conn, $_POST['session_type'] ?? 'Online Session');

    if(!empty($appointment_date) && !empty($appointment_time)){
        $insert = mysqli_query($conn, "INSERT INTO appointments (student_id, counsellor_id, appointment_date, appointment_time, counselling_type, appointment_status) 
                                       VALUES ('$student_id', '$counsellor_id', '$appointment_date', '$appointment_time', '$session_type', 'PENDING')");
        if ($insert) {
            $booking_success = true;
        } else {
            $booking_error = "Database Error: " . mysqli_error($conn);
        }
    } else {
        $booking_error = "Please select date and time slot.";
    }
}

// Fetch User Profile Name
$user_query = mysqli_query($conn, "SELECT full_name, username FROM users WHERE user_id = '$student_id'");
$user_data = mysqli_fetch_assoc($user_query);
$display_name = !empty($user_data['full_name']) ? $user_data['full_name'] : ($user_data['username'] ?? 'Student');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - BUP Counselling</title>
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
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .breadcrumb { font-size: 12px; color: #666; margin-top: 4px; }
        .breadcrumb a { color: #2d6a4f; text-decoration: none; }
        .user-profile { display: flex; align-items: center; gap: 12px; }
        .user-profile .avatar { width: 38px; height: 38px; background: #2d6a4f; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 13px; }

        /* Booking Grid Layout */
        .booking-container { display: grid; grid-template-columns: 320px 1fr; gap: 20px; margin-top: 20px; align-items: start; }
        
        /* Left Column: Profile Card */
        .profile-card { background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .counsellor-head { text-align: center; margin-bottom: 16px; }
        .counsellor-img { width: 75px; height: 75px; background: #1b4332; color: #52b788; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 10px; }
        .counsellor-head h3 { font-size: 16px; color: #111827; }
        .counsellor-head .badge { display: inline-block; background: #e8f5e9; color: #2e7d32; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 12px; margin-top: 4px; }

        .meta-list { margin-bottom: 15px; font-size: 12px; color: #4b5563; }
        .meta-item { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
        .meta-item i { color: #2d6a4f; width: 16px; }

        .section-subtitle { font-size: 12px; font-weight: 700; color: #111827; margin-bottom: 6px; }
        .about-text { font-size: 12px; color: #6b7280; line-height: 1.4; margin-bottom: 15px; }

        .tag-cloud { display: flex; flex-wrap: wrap; gap: 6px; }
        .tag { background: #f0fdf4; color: #166534; font-size: 10px; font-weight: 600; padding: 3px 8px; border-radius: 6px; border: 1px solid #bbf7d0; }

        /* Right Column: Steps & Calendar */
        .booking-form-card { background: #ffffff; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        .steps-bar { display: flex; align-items: center; gap: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px; font-size: 12px; font-weight: 600; }
        .step { display: flex; align-items: center; gap: 8px; color: #9ca3af; }
        .step.active { color: #2d6a4f; }
        .step-num { width: 22px; height: 22px; border-radius: 50%; background: #e5e7eb; color: #4b5563; display: flex; align-items: center; justify-content: center; font-size: 11px; }
        .step.active .step-num { background: #2d6a4f; color: #fff; }

        .date-time-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #374151; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; }
        
        .slots-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }
        .slot-btn { padding: 10px; text-align: center; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12px; cursor: pointer; background: #fff; transition: 0.2s; }
        .slot-btn:hover, .slot-btn.selected { background: #2d6a4f; color: #fff; border-color: #2d6a4f; }

        .summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-top: 20px; display: flex; justify-content: space-between; align-items: center; }
        .summary-info p { font-size: 12px; color: #4b5563; margin-bottom: 4px; }
        .btn-confirm { background: #2d6a4f; color: #fff; border: none; padding: 12px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px; }
        .btn-confirm:hover { background: #1b4332; }

        .alert-popup { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; display: flex; align-items: center; justify-content: space-between; }
        .alert-error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; }
        
        #step2_section { display: none; }
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
            <li class="active"><a href="bookappointment.php"><i class="fa-solid fa-calendar-plus"></i> Book Appointment</a></li>
            <li><a href="myappointment.php"><i class="fa-solid fa-calendar-check"></i> Appointments</a></li>
            <li><a href="workshops.php"><i class="fa-solid fa-graduation-cap"></i> Workshops</a></li>
            <li><a href="my_enrollments.php"><i class="fa-solid fa-book-open"></i> My Enrollments</a></li>
            <li><a href="counselling_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Counselling History</a></li>
            <li><a href="feedback.php"><i class="fa-solid fa-comment-dots"></i> Feedback</a></li>
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
            <h2 style="font-size: 20px; color: #111827;"><i class="fa-solid fa-calendar-plus" style="color: #2d6a4f;"></i> Book an Appointment</h2>
            <div class="breadcrumb"><a href="counsellors.php">Counsellors</a> &gt; Book Appointment</div>
        </div>
        <div class="user-profile">
            <div class="avatar"><?php echo strtoupper(substr($display_name, 0, 2)); ?></div>
            <div>
                <strong style="font-size: 13px;"><?php echo htmlspecialchars($display_name); ?></strong><br>
                <small style="color: #52b788;">Student</small>
            </div>
        </div>
    </div>

    <?php if ($booking_success): ?>
        <div class="alert-popup">
            <span><i class="fa-solid fa-circle-check"></i> <strong>Appointment Booked Successfully!</strong> You can view it in My Appointments.</span>
            <a href="myappointment.php" style="color: #166534; font-weight: bold; text-decoration: underline;">View Appointments</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($booking_error)): ?>
        <div class="alert-error">
            <span><i class="fa-solid fa-circle-xmark"></i> <strong>Error:</strong> <?php echo $booking_error; ?></span>
        </div>
    <?php endif; ?>

    <div class="booking-container">
        <!-- Left: Counsellor Info Card -->
        <div class="profile-card">
            <div class="counsellor-head">
                <div class="counsellor-img"><i class="fa-solid fa-user-doctor"></i></div>
                <h3><?php echo htmlspecialchars($counsellor['full_name'] ?? 'Dr. Farhana Rahman'); ?></h3>
                <span class="badge"><?php echo htmlspecialchars($counsellor['specialization'] ?? 'Mental Health Support'); ?></span>
            </div>

            <div class="meta-list">
                <div class="meta-item"><i class="fa-solid fa-graduation-cap"></i> <?php echo htmlspecialchars($counsellor['degree'] ?? 'PhD in Psychology'); ?></div>
                <div class="meta-item"><i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($counsellor['department'] ?? 'Department of Student Wellness'); ?></div>
                <div class="meta-item"><i class="fa-solid fa-briefcase"></i> <?php echo htmlspecialchars($counsellor['experience_years'] ?? '8'); ?> Years Experience</div>
                <div class="meta-item"><i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($counsellor['email'] ?? 'farhana@bup.edu.bd'); ?></div>
                <div class="meta-item"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($counsellor['phone'] ?? '01711111114'); ?></div>
            </div>

            <div class="section-subtitle">About</div>
            <p class="about-text">
                <?php echo htmlspecialchars($counsellor['about'] ?? 'Specializes in anxiety management, stress reduction, and overall mental well-being for students.'); ?>
            </p>

            <div class="section-subtitle">Specializations</div>
            <div class="tag-cloud">
                <?php 
                $tags = explode(',', $counsellor['tags'] ?? 'Anxiety Management,Stress Reduction,Mental Wellness');
                foreach($tags as $tag): 
                ?>
                    <span class="tag"><?php echo trim($tag); ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Right: Booking Selection -->
        <div class="booking-form-card">
            <div class="steps-bar">
                <div class="step active" id="st_1"><span class="step-num">1</span> Select Date & Time</div>
                <div class="step" id="st_2"><span class="step-num">2</span> Confirm Details</div>
            </div>

            <form action="bookappointment.php?counsellor_id=<?php echo $counsellor_id; ?>" method="POST" id="appointmentForm">
                <input type="hidden" name="book_appointment" value="1">
                <input type="hidden" name="appointment_time" id="selected_time" value="10:00 AM">

                <div id="step1_section">
                    <div class="date-time-grid">
                        <div class="form-group">
                            <label><i class="fa-solid fa-calendar-days" style="color: #2d6a4f;"></i> Select Date</label>
                            <input type="date" name="appointment_date" id="appointment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label><i class="fa-solid fa-video" style="color: #2d6a4f;"></i> Session Type</label>
                            <select name="session_type" id="session_type" class="form-control">
                                <option value="Online Session">Online Session</option>
                                <option value="Student Wellness Center">In-Person (Wellness Center)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fa-solid fa-clock" style="color: #2d6a4f;"></i> Available Time Slots</label>
                        <div class="slots-grid">
                            <div class="slot-btn" onclick="selectSlot(this, '09:00 AM')">09:00 AM</div>
                            <div class="slot-btn selected" onclick="selectSlot(this, '10:00 AM')">10:00 AM</div>
                            <div class="slot-btn" onclick="selectSlot(this, '11:00 AM')">11:00 AM</div>
                            <div class="slot-btn" onclick="selectSlot(this, '12:00 PM')">12:00 PM</div>
                            <div class="slot-btn" onclick="selectSlot(this, '02:00 PM')">02:00 PM</div>
                            <div class="slot-btn" onclick="selectSlot(this, '03:00 PM')">03:00 PM</div>
                        </div>
                    </div>

                    <div class="summary-box">
                        <div class="summary-info">
                            <p><strong>Counsellor:</strong> <?php echo htmlspecialchars($counsellor['full_name'] ?? 'Dr. Farhana Rahman'); ?></p>
                            <p><strong>Selected Time:</strong> <span id="time_display">10:00 AM</span></p>
                        </div>
                        <button type="button" onclick="goToStep2()" class="btn-confirm">Next: Confirm Booking &rarr;</button>
                    </div>
                </div>

                <div id="step2_section">
                    <h3 style="font-size: 16px; margin-bottom: 12px; color: #111827;">Review Booking Details</h3>
                    <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; font-size: 13px; line-height: 1.8;">
                        <p><strong>Counsellor:</strong> <?php echo htmlspecialchars($counsellor['full_name'] ?? ''); ?></p>
                        <p><strong>Date:</strong> <span id="conf_date"></span></p>
                        <p><strong>Time:</strong> <span id="conf_time">10:00 AM</span></p>
                        <p><strong>Session Type:</strong> <span id="conf_type"></span></p>
                    </div>
                    
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="backToStep1()" style="background: #e2e8f0; color: #333; border: none; padding: 12px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px;">Back</button>
                        <button type="submit" class="btn-confirm"><i class="fa-solid fa-check-circle"></i> Complete Booking</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectSlot(element, time) {
    document.querySelectorAll('.slot-btn').forEach(btn => btn.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('selected_time').value = time;
    document.getElementById('time_display').innerText = time;
}

function goToStep2() {
    let dateVal = document.getElementById('appointment_date').value;
    let timeVal = document.getElementById('selected_time').value;
    let typeVal = document.getElementById('session_type').value;

    if (!dateVal) {
        alert("Please select a date.");
        return;
    }

    document.getElementById('conf_date').innerText = dateVal;
    document.getElementById('conf_time').innerText = timeVal;
    document.getElementById('conf_type').innerText = typeVal;

    document.getElementById('step1_section').style.display = 'none';
    document.getElementById('step2_section').style.display = 'block';

    document.getElementById('st_1').classList.remove('active');
    document.getElementById('st_2').classList.add('active');
}

function backToStep1() {
    document.getElementById('step2_section').style.display = 'none';
    document.getElementById('step1_section').style.display = 'block';

    document.getElementById('st_2').classList.remove('active');
    document.getElementById('st_1').classList.add('active');
}
</script>

</body>
</html>