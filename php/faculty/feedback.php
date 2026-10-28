<?php
session_start();
require_once "../config/db.php";

if (!isset($_SESSION['faculty_user']) || $_SESSION['role'] !== 'FACULTY') {
    header("Location: ../authorization/login.php");
    exit();
}

$faculty_name = $_SESSION['faculty_name'] ?? 'Faculty Member';
$department = $_SESSION['department'] ?? 'Department';
$user_id = $_SESSION['user_id'] ?? 0;
$msg = "";

// Handle Delete Feedback
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    $del_query = mysqli_query($conn, "DELETE FROM feedback WHERE feedback_id = '$del_id' AND user_id = '$user_id'");
    if ($del_query) {
        $msg = "Feedback deleted successfully!";
    } else {
        $msg = "Failed to delete feedback.";
    }
}

// Handle Edit / Update Feedback
$edit_mode = false;
$edit_id = "";
$edit_category = "";
$edit_rating = "";
$edit_comments = "";

if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $edit_id = mysqli_real_escape_string($conn, $_GET['edit_id']);
    $fetch_edit = mysqli_query($conn, "SELECT * FROM feedback WHERE feedback_id = '$edit_id' AND user_id = '$user_id'");
    if (mysqli_num_rows($fetch_edit) > 0) {
        $row_edit = mysqli_fetch_assoc($fetch_edit);
        $edit_category = $row_edit['category'];
        $edit_rating = $row_edit['rating'];
        $edit_comments = $row_edit['comments'];
    }
}

// Handle Form Submission (Insert or Update)
if (isset($_POST['submit_feedback'])) {
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $rating = mysqli_real_escape_string($conn, $_POST['rating']);
    $comments = mysqli_real_escape_string($conn, $_POST['comments']);
    $update_id = $_POST['update_id'] ?? '';

    if (!empty($update_id)) {
        // Update existing feedback
        $update_fb = mysqli_query($conn, "UPDATE feedback SET category='$category', rating='$rating', comments='$comments' WHERE feedback_id='$update_id' AND user_id='$user_id'");
        if ($update_fb) {
            $msg = "Feedback updated successfully!";
            $edit_mode = false;
        } else {
            $msg = "Failed to update feedback.";
        }
    } else {
        // Insert new feedback
        $insert_fb = mysqli_query($conn, "INSERT INTO feedback (user_id, category, rating, comments, submitted_at) VALUES ('$user_id', '$category', '$rating', '$comments', NOW())");
        if ($insert_fb) {
            $msg = "Feedback submitted successfully. Thank you!";
        } else {
            $msg = "Failed to submit feedback: " . mysqli_error($conn);
        }
    }
}

// Fetch feedback submitted by this faculty user
$feedback_query = mysqli_query($conn, "SELECT * FROM feedback WHERE user_id = '$user_id' ORDER BY submitted_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - BUP Counselling & Wellness</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f7f6; color: #333; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background-color: #0b3c29; color: #fff; display: flex; flex-direction: column; justify-content: space-between; padding: 20px 0; position: fixed; height: 100%; left: 0; top: 0; }
        .sidebar-top .brand { display: flex; align-items: center; gap: 12px; padding: 0 20px 20px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-top .brand img { width: 38px; height: auto; object-fit: contain; }
        .sidebar-top .brand h2 { font-size: 15px; font-weight: 600; color: #fff; }
        .nav-links { list-style: none; padding: 20px 0; overflow-y: auto; flex-grow: 1; }
        .nav-links li a { display: flex; align-items: center; gap: 15px; padding: 12px 25px; color: #b8d8ce; text-decoration: none; font-size: 14px; transition: 0.3s; }
        .nav-links li a:hover, .nav-links li a.active { background-color: #124e37; color: #fff; border-left: 4px solid #52b788; }
        .nav-links li a i { font-size: 16px; width: 20px; text-align: center; }
        .sidebar-footer { padding: 15px 25px; border-top: 1px solid rgba(255, 255, 255, 0.1); font-size: 11px; color: #8abdb2; line-height: 1.4; }
        .main-content { margin-left: 260px; flex-grow: 1; padding: 25px 35px; overflow-y: auto; height: 100vh; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .page-title h2 { font-size: 22px; color: #0b3c29; }
        .user-profile-header { display: flex; align-items: center; gap: 12px; }
        .user-avatar { width: 38px; height: 38px; background-color: #0b3c29; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 14px; }
        .user-info h4 { font-size: 13px; color: #222; }
        .user-info span { font-size: 11px; color: #666; }
        .feedback-container { display: grid; grid-template-columns: 1fr 2fr; gap: 25px; }
        .card-box { background: #fff; border-radius: 12px; box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04); padding: 25px; height: fit-content; }
        .card-box h3 { font-size: 16px; color: #0b3c29; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #444; margin-bottom: 5px; text-transform: uppercase; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px 12px; border: 1px solid #ccc; border-radius: 8px; font-size: 13px; outline: none; background: #f8faf9; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #0b3c29; background: #fff; box-shadow: 0 0 0 2px rgba(11, 60, 41, 0.1); }
        .submit-btn { background: #198754; color: white; border: none; padding: 12px; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; width: 100%; transition: 0.3s; }
        .submit-btn:hover { background: #157347; }
        .cancel-btn { background: #6c757d; color: white; border: none; padding: 10px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 8px; text-align: center; display: block; text-decoration: none; }
        .cancel-btn:hover { background: #5a6268; }
        .alert-msg { background: #d1e7dd; color: #0f5132; padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        th { background-color: #f8faf9; color: #0b3c29; padding: 12px; border-bottom: 2px solid #e5ece7; font-weight: 600; }
        td { padding: 12px; border-bottom: 1px solid #eee; color: #444; }
        tr:hover { background-color: #fafbfc; }
        .action-btns a { padding: 5px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; text-decoration: none; margin-right: 4px; display: inline-block; }
        .btn-edit { background: #ffc107; color: #000; }
        .btn-delete { background: #dc3545; color: #fff; }
        .no-data { text-align: center; padding: 30px; color: #666; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-top">
            <div class="brand">
                <img src="../../images/logo.png" alt="BUP Logo">
                <h2>Counselling & Wellness</h2>
            </div>
            <ul class="nav-links">
                <li><a href="faculty_dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="faculty_profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="counsellors.php"><i class="fas fa-user-md"></i> Counsellors</a></li>
                <li><a href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a></li>
                <li><a href="workshops.php"><i class="fas fa-chalkboard-teacher"></i> Workshops</a></li>
                <li><a href="referrals.php"><i class="fas fa-share-nodes"></i> Student Referrals</a></li>
                <li><a href="feedback.php" class="active"><i class="fas fa-comments"></i> Feedback</a></li>
                <li><a href="../authorization/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
        <div class="sidebar-footer">Bangladesh University of Professionals<br>"A Safer, Healthier, Stronger BUP"</div>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div class="page-title"><h2>Submit & Manage Feedback</h2></div>
            <div class="user-profile-header">
                <div class="user-avatar"><?php echo strtoupper(substr($faculty_name, 0, 2)); ?></div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($faculty_name); ?></h4>
                    <span>Faculty (<?php echo htmlspecialchars($department); ?>)</span>
                </div>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert-msg"><i class="fas fa-info-circle"></i> <?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="feedback-container">
            <div class="card-box">
                <h3><i class="fas fa-comment-medical"></i> <?php echo $edit_mode ? 'Edit Feedback' : 'Give Feedback'; ?></h3>
                <form method="POST">
                    <input type="hidden" name="update_id" value="<?php echo $edit_id; ?>">
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">-- Select Category --</option>
                            <option value="Counselling Service" <?php if($edit_category == 'Counselling Service') echo 'selected'; ?>>Counselling Service</option>
                            <option value="Workshop" <?php if($edit_category == 'Workshop') echo 'selected'; ?>>Workshop</option>
                            <option value="General System" <?php if($edit_category == 'General System') echo 'selected'; ?>>General System</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Rating (1 to 5)</label>
                        <select name="rating" required>
                            <option value="5" <?php if($edit_rating == '5') echo 'selected'; ?>>5 - Excellent</option>
                            <option value="4" <?php if($edit_rating == '4') echo 'selected'; ?>>4 - Good</option>
                            <option value="3" <?php if($edit_rating == '3') echo 'selected'; ?>>3 - Average</option>
                            <option value="2" <?php if($edit_rating == '2') echo 'selected'; ?>>2 - Poor</option>
                            <option value="1" <?php if($edit_rating == '1') echo 'selected'; ?>>1 - Terrible</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Comments</label>
                        <textarea name="comments" rows="4" placeholder="Write your feedback here..." required><?php echo htmlspecialchars($edit_comments); ?></textarea>
                    </div>

                    <button type="submit" name="submit_feedback" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> <?php echo $edit_mode ? 'Update Feedback' : 'Submit Feedback'; ?>
                    </button>

                    <?php if($edit_mode): ?>
                        <a href="feedback.php" class="cancel-btn">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card-box">
                <h3><i class="fas fa-history"></i> My Feedback History</h3>
                <?php if (mysqli_num_rows($feedback_query) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Rating</th>
                                <th>Comments</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($f = mysqli_fetch_assoc($feedback_query)): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($f['category']); ?></strong></td>
                                    <td><span style="color: #e67e22; font-weight: 600;"><?php echo htmlspecialchars($f['rating']); ?> / 5 ⭐</span></td>
                                    <td><?php echo htmlspecialchars($f['comments']); ?></td>
                                    <td class="action-btns">
                                        <a href="feedback.php?edit_id=<?php echo $f['feedback_id']; ?>" class="btn-edit"><i class="fas fa-pen"></i> Edit</a>
                                        <a href="feedback.php?delete_id=<?php echo $f['feedback_id']; ?>" class="btn-delete" onclick="return confirm('Are you sure you want to delete this feedback?');"><i class="fas fa-trash"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data"><i class="fas fa-info-circle" style="font-size: 24px; color: #2d6a4f; margin-bottom: 10px;"></i><p>You haven't submitted any feedback yet.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>