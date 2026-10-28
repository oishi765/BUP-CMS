<?php
session_start();
require_once "../config/db.php";

// Check if user is logged in and role is STUDENT
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'STUDENT') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Student';

$message = "";
$error = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $category = mysqli_real_escape_string($conn, $_POST['category'] ?? '');
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comments = mysqli_real_escape_string($conn, $_POST['comments'] ?? '');

    if (!empty($category) && $rating >= 1 && $rating <= 5 && !empty($comments)) {
        $insert_query = "INSERT INTO feedback (user_id, category, rating, comments) VALUES ('$user_id', '$category', '$rating', '$comments')";
        
        if (mysqli_query($conn, $insert_query)) {
            $message = "Thank you! Your feedback has been submitted successfully.";
        } else {
            // Displays exact MySQL Error
            $error = "Database Error: " . mysqli_error($conn);
        }
    } else {
        $error = "Please select Category, Rating star, and write your comments.";
    }
}

// Fetch Previous Feedback History
$history_query = "SELECT * FROM feedback WHERE user_id = '$user_id' ORDER BY feedback_id DESC";
$history_result = mysqli_query($conn, $history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - BUP Counselling</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { display: flex; background-color: #f2f7f4; color: #333; }
        .sidebar { width: 260px; height: 100vh; background-color: #081c15; color: #fff; position: fixed; display: flex; flex-direction: column; justify-content: space-between; padding: 20px 0; }
        .sidebar .brand { padding: 0 20px; font-size: 16px; font-weight: bold; margin-bottom: 25px; color: #52b788; display: flex; align-items: center; gap: 10px; }
        .sidebar ul { list-style: none; }
        .sidebar ul li a { display: flex; align-items: center; padding: 12px 25px; color: #d1e0d7; text-decoration: none; font-size: 13px; transition: 0.3s; }
        .sidebar ul li a i { margin-right: 12px; width: 18px; }
        .sidebar ul li.active a, .sidebar ul li a:hover { background-color: #1b4332; color: #fff; border-left: 4px solid #52b788; }
        .sidebar-footer { padding: 20px; font-size: 11px; color: #74a892; border-top: 1px solid #1b4332; }
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 25px 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .user-profile { display: flex; align-items: center; gap: 12px; margin-left: auto; }
        .user-profile .avatar { width: 38px; height: 38px; background: #2d6a4f; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; }
        .page-title h2 { font-size: 22px; color: #081c15; }
        .page-title p { font-size: 13px; color: #52b788; margin-bottom: 20px; }
        .card { background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e0f0e6; box-shadow: 0 4px 10px rgba(0,0,0,0.02); margin-bottom: 25px; }
        .card h3 { font-size: 16px; color: #081c15; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .alert { padding: 12px 15px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; }
        .alert-success { background-color: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .alert-danger { background-color: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #1b4332; margin-bottom: 6px; }
        .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #cce3d5; border-radius: 8px; font-size: 13px; outline: none; background: #fdfdfd; }
        .rating-stars { display: flex; gap: 10px; flex-direction: row-reverse; justify-content: flex-end; }
        .rating-stars input { display: none; }
        .rating-stars label { font-size: 22px; color: #ccc; cursor: pointer; transition: color 0.2s; margin: 0; }
        .rating-stars input:checked ~ label, .rating-stars label:hover, .rating-stars label:hover ~ label { color: #ffc107; }
        .btn-submit { background: #2d6a4f; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-submit:hover { background: #1b4332; }
        .history-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .history-table th, .history-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e5ece7; }
        .history-table th { background-color: #f8faf9; color: #081c15; font-weight: 600; }
        .star-gold { color: #ffc107; }
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
                <li><a href="my_enrollments.php"><i class="fa-solid fa-book-open"></i> My Enrollments</a></li>
                <li><a href="counselling_history.php"><i class="fa-solid fa-clock-rotate-left"></i> Counselling History</a></li>
                <li class="active"><a href="my_feedback.php"><i class="fa-solid fa-comment-dots"></i> Feedback</a></li>
                <li><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">
            <strong>Bangladesh University of Professionals</strong><br>
            "A Safer, Healthier, Stronger BUP"
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="user-profile">
                <div class="avatar"><?php echo strtoupper(substr($student_name, 0, 2)); ?></div>
                <div>
                    <strong style="font-size: 13px;"><?php echo htmlspecialchars($student_name); ?></strong><br>
                    <small style="color: #52b788;">Student</small>
                </div>
            </div>
        </div>

        <div class="page-title">
            <h2>Give Your Feedback</h2>
            <p>Help us improve our counselling services and workshops by sharing your thoughts.</p>
        </div>

        <div class="card">
            <h3><i class="fa-solid fa-pen-to-square" style="color: #2d6a4f;"></i> Submit Feedback</h3>

            <?php if (!empty($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form action="my_feedback.php" method="POST">
                <div class="form-group">
                    <label for="category">Category</label>
                    <select name="category" id="category" required>
                        <option value="">Select Category</option>
                        <option value="Counselling Session">Counselling Session</option>
                        <option value="Workshop">Workshop</option>
                        <option value="General Service">General Service / Website</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Rating</label>
                    <div class="rating-stars">
                        <input type="radio" id="star5" name="rating" value="5" required/><label for="star5" title="5 stars"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star"><i class="fa-solid fa-star"></i></label>
                    </div>
                </div>

                <div class="form-group">
                    <label for="comments">Your Feedback / Comments</label>
                    <textarea name="comments" id="comments" rows="4" placeholder="Write your feedback here..." required></textarea>
                </div>

                <button type="submit" name="submit_feedback" class="btn-submit">
                    <i class="fa-solid fa-paper-plane"></i> Submit Feedback
                </button>
            </form>
        </div>

        <div class="card">
            <h3><i class="fa-solid fa-clock-rotate-left" style="color: #2d6a4f;"></i> My Feedback History</h3>

            <?php if ($history_result && mysqli_num_rows($history_result) > 0): ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Rating</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($history_result)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['category'] ?? 'General'); ?></strong></td>
                                <td>
                                    <?php 
                                    $stars = intval($row['rating']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        if ($i <= $stars) {
                                            echo '<i class="fa-solid fa-star star-gold"></i> ';
                                        } else {
                                            echo '<i class="fa-regular fa-star" style="color: #ccc;"></i> ';
                                        }
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['comments']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="font-size: 13px; color: #74a892;">You haven't submitted any feedback yet.</p>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>