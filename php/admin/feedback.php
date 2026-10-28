<?php
$conn = new mysqli("localhost", "root", "oishi123", "bup_counselling_management");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

// Action: Handle Delete Request Safely
if (isset($_GET['action']) &&$_GET['action'] === 'delete' && isset($_GET['id'])) {$delete_id = intval($_GET['id']);$conn->query("DELETE FROM feedback WHERE feedback_id = $delete_id");
    header("Location: feedback.php");
    exit();
}

// Filter Query Setup
$whereClauses = [];
if (!empty($_GET['rating'])) {
    $rating = intval($_GET['rating']);
    if ($rating >= 1 &&$rating <= 5) {
        $whereClauses[] = "f.rating = $rating";
    }
}

$whereSql = count($whereClauses) > 0 ? " WHERE " . implode(" AND ", $whereClauses) : "";

// Join query: Fetch Student and Counsellor details dynamically
$sql = "
SELECT 
    f.feedback_id,
    f.user_id,
    f.appointment_id,
    f.workshop_id,
    f.rating,
    f.comments,
    f.submitted_at,
    COALESCE(s.full_name, CONCAT('User #', f.user_id)) AS student_name,
    COALESCE(c.full_name, IF(f.appointment_id IS NOT NULL, CONCAT('Appointment #', f.appointment_id), 'General Service')) AS counsellor_name
FROM feedback f
LEFT JOIN students s ON f.user_id = s.user_id
LEFT JOIN appointments a ON f.appointment_id = a.appointment_id
LEFT JOIN counsellors c ON a.counsellor_id = c.counsellor_id
$whereSql
ORDER BY f.feedback_id DESC
";

$result = $conn->query($sql);

/* Statistics Query */
$totalFeedbackQuery =$conn->query("SELECT COUNT(*) as total, AVG(rating) as avg_rating FROM feedback");
$stats = $totalFeedbackQuery ? $totalFeedbackQuery->fetch_assoc() : [];
$totalCount =$stats['total'] ?? 0;
$avgRating = number_format($stats['avg_rating'] ?? 0, 1);

$positiveCountQuery =$conn->query("SELECT COUNT(*) as pos_total FROM feedback WHERE rating >= 4");
$posStats = $positiveCountQuery ? $positiveCountQuery->fetch_assoc() : [];
$positiveCount =$posStats['pos_total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Feedback Records - BUP CMS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
:root {
    --primary-green: #062219;
    --accent-green: #0a3a2a;
    --active-green: #0f523a;
    --light-bg: #f4f7f6;
    --card-bg: #ffffff;
    --text-dark: #2c3e50;
    --text-muted: #6c757d;
    --border-color: #e2e8f0;
}

* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
body { background-color: var(--light-bg); color: var(--text-dark); display: flex; min-height: 100vh; }

.sidebar {
    width: 250px;
    background-color: var(--primary-green);
    color: white;
    position: fixed;
    height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 20px 15px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 12px;
    padding-bottom: 25px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    margin-bottom: 20px;
}

.brand-icon {
    width: 38px;
    height: 38px;
    background: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 18px;
}

.brand h2 { 
    font-size: 16px; 
    color: #fff; 
    font-weight: 700; 
    line-height: 1.2;
}

.brand span {
    font-size: 11px;
    color: #9ca3af;
    display: block;
    font-weight: normal;
}

.sidebar ul { list-style: none; }

.sidebar ul li a {
    display: flex;
    align-items: center;
    gap: 14px;
    color: #9ca3af;
    text-decoration: none;
    padding: 12px 16px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 6px;
    transition: all 0.2s ease;
}

.sidebar ul li a:hover {
    background: rgba(255,255,255,0.05);
    color: #ffffff;
}

.sidebar ul li a.active {
    background: var(--active-green);
    color: #ffffff;
    border-left: 4px solid #10b981;
}

.sidebar-footer {
    font-size: 11px;
    color: #6b7280;
    text-align: center;
    padding-top: 15px;
    border-top: 1px solid rgba(255,255,255,0.08);
}

.main-wrapper { margin-left: 250px; flex: 1; display: flex; flex-direction: column; }

.topbar {
    height: 65px;
    background: #ffffff;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
}

.topbar-search {
    display: flex;
    align-items: center;
    background: var(--light-bg);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 8px 14px;
    width: 300px;
}

.topbar-search input { background: transparent; border: none; outline: none; margin-left: 10px; width: 100%; }

.user-profile { display: flex; align-items: center; gap: 12px; }

.avatar {
    width: 34px;
    height: 34px;
    background: #10b981;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 12px;
}

.content { padding: 30px; }

.page-title h1 { font-size: 24px; color: var(--primary-green); }
.page-title p { font-size: 13px; color: var(--text-muted); margin-bottom: 25px; }

.cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; }

.card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    display: flex;
    align-items: center;
    gap: 20px;
}

.card-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.card-icon.total { background: #e3f2fd; color: #1976d2; }
.card-icon.rating { background: #fff8e1; color: #f57f17; }
.card-icon.positive { background: #e8f5e9; color: #2e7d32; }

.filter-box {
    background: white;
    padding: 15px;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    margin-bottom: 20px;
    display: flex;
    gap: 12px;
    align-items: center;
}

.filter-box input, .filter-box select {
    padding: 9px 12px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    outline: none;
    font-size: 13px;
}

.filter-box input[type="text"] { flex: 1; }

.filter-btn {
    background: var(--primary-green);
    color: white;
    border: none;
    padding: 9px 18px;
    border-radius: 6px;
    cursor: pointer;
    transition: 0.2s;
}

.filter-btn:hover { background: var(--active-green); }

.table-container {
    background: white;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0,0,0,0.02);
}

table { width: 100%; border-collapse: collapse; }

th {
    background: #0f523a;
    color: white;
    padding: 14px 16px;
    font-size: 13px;
    text-align: left;
}

td { padding: 12px 16px; border-bottom: 1px solid #eee; font-size: 13px; color: #333; }
tr:hover { background-color: #f9fbf9; }

.stars { color: #ffb400; font-size: 13px; }

.btn-action {
    background: #f1f3f5;
    border: 1px solid #ced4da;
    width: 32px;
    height: 32px;
    border-radius: 6px;
    cursor: pointer;
    color: #495057;
    transition: 0.2s;
}

.btn-action:hover { background: #e9ecef; color: #000; }
.btn-action.delete:hover { background: #f8d7da; color: #dc3545; }

/* Modal Styling */
.modal {
    display: none;
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    justify-content: center;
    align-items: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    padding: 25px;
    border-radius: 10px;
    width: 480px;
    max-width: 90%;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
.close-btn { cursor: pointer; font-size: 20px; color: #aaa; }
.close-btn:hover { color: #000; }
</style>
</head>

<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div>
        <div class="brand">
            <div class="brand-icon"><i class="fas fa-leaf"></i></div>
            <div class="brand-text">
                <h2>BUP</h2>
                <span>Counselling & Wellness</span>
            </div>
        </div>

        <ul>
            <li>
                <a href="admin.php"><i class="fas fa-th-large"></i> Dashboard</a>
            </li>
            <li>
                <a href="manage-users.php"><i class="fas fa-users-gear"></i> User Management</a>
            </li>
            <li>
                <a href="counsellors.php"><i class="fas fa-user-md"></i> Counsellors</a>
            </li>
            <li>
                <a href="workshops.php"><i class="fas fa-chalkboard-user"></i> Workshops</a>
            </li>
            <li>
                <a href="appointments.php"><i class="fas fa-calendar-check"></i> Appointments</a>
            </li>
            <li>
                <a href="feedback.php" class="active"><i class="fas fa-comments"></i> Feedback</a>
            </li>
            <li>
                <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
            </li>
            <li style="margin-top: 25px;">
                <a href="logout.php" style="color: #ef4444;"><i class="fas fa-right-from-bracket"></i> Logout</a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        "A Safer, Healthier, Stronger BUP"
    </div>
</aside>

<div class="main-wrapper">

    <div class="topbar">
        <div class="topbar-search">
            <i class="fa-solid fa-magnifying-glass" style="color: #aaa;"></i>
            <input type="text" placeholder="Search anything...">
        </div>
        <div class="user-profile">
            <i class="fa-regular fa-bell"></i>
            <div class="avatar">EO</div>
            <div>
                <p style="font-size: 13px; font-weight: bold;">Evana Hossain Oishi</p>
                <p style="font-size: 10px; color: var(--text-muted);">ADMIN</p>
            </div>
        </div>
    </div>

    <div class="content">

        <div class="page-title">
            <h1>Manage Feedback Records</h1>
            <p>Review student feedback, ratings, and comments</p>
        </div>

        <div class="cards">
            <div class="card">
                <div class="card-icon total"><i class="fa-solid fa-comments"></i></div>
                <div>
                    <h2><?php echo $totalCount; ?></h2>
                    <p style="font-size:12px; color:var(--text-muted);">Total Feedback</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon rating"><i class="fa-solid fa-star"></i></div>
                <div>
                    <h2><?php echo $avgRating; ?> / 5.0</h2>
                    <p style="font-size:12px; color:var(--text-muted);">Average Rating</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon positive"><i class="fa-solid fa-thumbs-up"></i></div>
                <div>
                    <h2><?php echo $positiveCount; ?></h2>
                    <p style="font-size:12px; color:var(--text-muted);">Positive Ratings</p>
                </div>
            </div>
        </div>

        <form method="GET" action="" class="filter-box">
            <input type="text" id="searchInput" placeholder="Search student, counsellor, or comment...">
            
            <select name="rating">
                <option value="">All Ratings</option>
                <option value="5" <?php echo (isset($_GET['rating']) &&$_GET['rating'] === '5') ? 'selected' : ''; ?>>5 Stars ⭐⭐⭐⭐⭐</option>
                <option value="4" <?php echo (isset($_GET['rating']) &&$_GET['rating'] === '4') ? 'selected' : ''; ?>>4 Stars ⭐⭐⭐⭐</option>
                <option value="3" <?php echo (isset($_GET['rating']) &&$_GET['rating'] === '3') ? 'selected' : ''; ?>>3 Stars ⭐⭐⭐</option>
                <option value="2" <?php echo (isset($_GET['rating']) &&$_GET['rating'] === '2') ? 'selected' : ''; ?>>2 Stars ⭐⭐</option>
                <option value="1" <?php echo (isset($_GET['rating']) &&$_GET['rating'] === '1') ? 'selected' : ''; ?>>1 Star ⭐</option>
            </select>

            <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
        </form>

        <div class="table-container">
            <table id="feedbackTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Counsellor Name</th>
                        <th>Rating</th>
                        <th>Comment</th>
                        <th>Submitted At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if($result &&$result->num_rows > 0){
                        while($row = $result->fetch_assoc()){$studentName = htmlspecialchars($row['student_name']);$counsellorName = htmlspecialchars($row['counsellor_name']);$ratingStars = str_repeat("<i class='fa-solid fa-star'></i> ", intval($row['rating']));
                            
                            $commentsRaw =$row['comments'] ?? '';
                            $commentShort = htmlspecialchars(mb_substr($commentsRaw, 0, 45));

                            // Safe JavaScript Parameter Conversion
                            $jsonStudent = json_encode($row['student_name']);
                            $jsonCounsellor = json_encode($row['counsellor_name']);
                            $jsonRating = json_encode($row['rating']);
                            $jsonComment = json_encode($commentsRaw);

                            echo "<tr>";
                            echo "<td>#{$row['feedback_id']}</td>";
                            echo "<td>{$studentName}</td>";
                            echo "<td>{$counsellorName}</td>";
                            echo "<td class='stars'>{$ratingStars}</td>";
                            echo "<td>{$commentShort}" . (mb_strlen($commentsRaw) > 45 ? '...' : '') . "</td>";
                            echo "<td>{$row['submitted_at']}</td>";
                            echo "<td>
                                    <button class='btn-action' title='View' onclick='viewFeedback({$jsonStudent}, {$jsonCounsellor}, {$jsonRating}, {$jsonComment})'><i class='fa-regular fa-eye'></i></button>
                                    <a href='feedback.php?action=delete&id={$row['feedback_id']}' onclick='return confirm(\"Are you sure you want to delete this record?\")'><button class='btn-action delete' title='Delete'><i class='fa-solid fa-trash'></i></button></a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; padding: 20px;'>No Feedback Records Found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<div class="modal" id="viewModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Feedback Details</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <div id="viewModalBody" style="font-size:14px; line-height: 1.8;"></div>
    </div>
</div>

<script>
document.getElementById("searchInput").addEventListener("input", function() {
    let filter = this.value.trim().toLowerCase();
    let rows = document.querySelectorAll("#feedbackTable tbody tr");

    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});

function viewFeedback(student, counsellor, rating, comment) {
    let details = `
        <p><strong>Student:</strong> ${student}</p>
        <p><strong>Counsellor:</strong> ${counsellor}</p>
        <p><strong>Rating:</strong> ${rating} / 5 ⭐</p>
        <p style="margin-top:10px;"><strong>Full Comment:</strong></p>
        <div style="background:#f8f9fa; padding:12px; border-radius:6px; margin-top:5px; border: 1px solid #e2e8f0; white-space: pre-wrap;">${comment}</div>
    `;
    document.getElementById("viewModalBody").innerHTML = details;
    document.getElementById("viewModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("viewModal").style.display = "none";
}
</script>

</body>
</html>