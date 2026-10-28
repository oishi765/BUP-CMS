<?php
$conn = new mysqli("localhost", "root", "oishi123", "bup_counselling_management");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

// Action: Handle Insert New Appointment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_appointment'])) {
    $student_id = intval($_POST['student_id']);
    $counsellor_id = intval($_POST['counsellor_id']);
    $counselling_type = $conn->real_escape_string($_POST['counselling_type']);
    $availability_id = intval($_POST['availability_id']);
    $appointment_status = $conn->real_escape_string($_POST['appointment_status']);

    $stmt = $conn->prepare("INSERT INTO appointments (student_id, counsellor_id, counselling_type, availability_id, appointment_status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisis", $student_id, $counsellor_id, $counselling_type, $availability_id, $appointment_status);
    $stmt->execute();
    
    header("Location: appointments.php");
    exit();
}

// Action: Handle Delete Request
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $delete_id = intval($_GET['id']);
    $conn->query("DELETE FROM appointments WHERE appointment_id = $delete_id");
    header("Location: appointments.php");
    exit();
}

// Action: Handle Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_appointment'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $new_status = $conn->real_escape_string($_POST['appointment_status']);
    $conn->query("UPDATE appointments SET appointment_status = '$new_status' WHERE appointment_id = $appointment_id");
    header("Location: appointments.php");
    exit();
}

// Fetch Filter Query Data
$whereClauses = [];
if (!empty($_GET['counsellor'])) {
    $counsellor = $conn->real_escape_string($_GET['counsellor']);
    $whereClauses[] = "c.full_name = '$counsellor'";
}
if (!empty($_GET['type'])) {
    $type = $conn->real_escape_string($_GET['type']);
    $whereClauses[] = "a.counselling_type = '$type'";
}
if (!empty($_GET['status'])) {
    $status = $conn->real_escape_string($_GET['status']);
    $whereClauses[] = "a.appointment_status = '$status'";
}
if (!empty($_GET['date'])) {
    $date = $conn->real_escape_string($_GET['date']);
    $whereClauses[] = "av.available_date = '$date'";
}

$whereSql = count($whereClauses) > 0 ? " WHERE " . implode(" AND ", $whereClauses) : "";

// SQL Query (Corrected student_id JOIN)
$sql = "
SELECT
    a.appointment_id,
    s.full_name AS student_name,
    c.full_name AS counsellor_name,
    a.counselling_type,
    av.available_date,
    av.start_time,
    av.end_time,
    a.appointment_status
FROM appointments a
JOIN students s ON a.student_id = s.student_id
JOIN counsellors c ON a.counsellor_id = c.counsellor_id
JOIN availability av ON a.availability_id = av.availability_id
$whereSql
ORDER BY a.appointment_id DESC
";

$result = $conn->query($sql);

/* Dynamic Statistics */
$totalAppointments = mysqli_num_rows($conn->query("SELECT * FROM appointments"));
$pendingCount      = mysqli_num_rows($conn->query("SELECT * FROM appointments WHERE appointment_status='PENDING'"));
$approvedCount     = mysqli_num_rows($conn->query("SELECT * FROM appointments WHERE appointment_status='APPROVED'"));
$completedCount    = mysqli_num_rows($conn->query("SELECT * FROM appointments WHERE appointment_status='COMPLETED'"));
$cancelledCount    = mysqli_num_rows($conn->query("SELECT * FROM appointments WHERE appointment_status='CANCELLED'"));

// Fetch Lists for Dropdowns
$studentsList =$conn->query("SELECT student_id, full_name FROM students");
$counsellorsList =$conn->query("SELECT counsellor_id, full_name FROM counsellors");
$availabilityList =$conn->query("SELECT availability_id, available_date, start_time, end_time FROM availability");
$counsellorsFilterQuery =$conn->query("SELECT DISTINCT full_name FROM counsellors");
?>

<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Monitor Appointments - BUP CMS</title>
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

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background-color: var(--light-bg);
    color: var(--text-dark);
    display: flex;
    min-height: 100vh;
}

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

.sidebar ul {
    list-style: none;
}

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

.main-wrapper {
    margin-left: 250px;
    flex: 1;
    display: flex;
    flex-direction: column;
}

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

.topbar-search input {
    background: transparent;
    border: none;
    outline: none;
    margin-left: 10px;
    width: 100%;
}

.user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
}

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

.page-title-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.page-title h1 { font-size: 24px; color: var(--primary-green); }
.page-title p { font-size: 13px; color: var(--text-muted); }

.add-btn {
    background: #10b981;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: 0.2s;
    text-decoration: none;
}

.add-btn:hover { background: #059669; }

.cards {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.card {
    background: white;
    padding: 18px;
    border-radius: 10px;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 5px rgba(0,0,0,0.03);
    display: flex;
    align-items: center;
    gap: 15px;
}

.card-icon {
    width: 42px;
    height: 42px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.card-icon.total { background: #e8f5e9; color: #2e7d32; }
.card-icon.pending { background: #fff8e1; color: #f57f17; }
.card-icon.approved { background: #e8f5e9; color: #1b5e20; }
.card-icon.completed { background: #e3f2fd; color: #1565c0; }
.card-icon.cancelled { background: #ffebee; color: #c62828; }

.card-info h2 { font-size: 20px; line-height: 1.2; }
.card-info p { font-size: 12px; color: var(--text-muted); }

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
    padding: 9px 16px;
    border-radius: 6px;
    cursor: pointer;
}

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

td {
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    font-size: 13px;
    color: #333;
}

tr:hover { background-color: #f9fbf9; }

.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.badge.pending { background: #fff3cd; color: #856404; }
.badge.approved { background: #d4edda; color: #155724; }
.badge.completed { background: #cce5ff; color: #004085; }
.badge.cancelled { background: #f8d7da; color: #721c24; }

mark {
    background-color: #ffeb3b;
    color: #000;
    padding: 0 2px;
    border-radius: 2px;
}

.btn-action {
    background: #f1f3f5;
    border: 1px solid #ced4da;
    width: 30px;
    height: 30px;
    border-radius: 6px;
    cursor: pointer;
    color: #495057;
    margin-right: 2px;
}

.btn-action:hover { background: #e9ecef; color: #000; }
.btn-action.delete:hover { background: #f8d7da; color: #dc3545; }

.footer-tools {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 20px;
}

.export-btns button {
    background: white;
    border: 1px solid var(--border-color);
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    margin-left: 5px;
}

.export-btns button:hover { background: var(--primary-green); color: white; }

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
    width: 450px;
    max-width: 90%;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
}

.close-btn { cursor: pointer; font-size: 18px; }

.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; }
.form-group select, .form-group input { width: 100%; padding: 8px; border: 1px solid var(--border-color); border-radius: 6px; outline: none; }
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
                <a href="appointments.php" class="active"><i class="fas fa-calendar-check"></i> Appointments</a>
            </li>
            <li>
                <a href="feedback.php"><i class="fas fa-comments"></i> Feedback</a>
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
            <input type="text" placeholder="Search appointments...">
        </div>
        <div class="user-profile">
            <i class="fa-regular fa-bell"></i>
            <div class="avatar">AD</div>
            <div>
                <p style="font-size: 13px; font-weight: bold;">admin</p>
                <p style="font-size: 10px; color: var(--text-muted);">ADMIN</p>
            </div>
        </div>
    </div>

    <div class="content">

        <div class="page-title-row">
            <div class="page-title">
                <h1>Monitor Appointments</h1>
                <p>View and manage all counselling appointments</p>
            </div>
            <button class="add-btn" onclick="openAddModal()"><i class="fa-solid fa-plus"></i> Add Appointment</button>
        </div>

        <div class="cards">
            <div class="card">
                <div class="card-icon total"><i class="fa-solid fa-calendar"></i></div>
                <div class="card-info">
                    <h2><?php echo $totalAppointments; ?></h2>
                    <p>Total</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon pending"><i class="fa-solid fa-clock"></i></div>
                <div class="card-info">
                    <h2><?php echo $pendingCount; ?></h2>
                    <p>Pending</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon approved"><i class="fa-solid fa-check-circle"></i></div>
                <div class="card-info">
                    <h2><?php echo $approvedCount; ?></h2>
                    <p>Approved</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon completed"><i class="fa-solid fa-circle-user"></i></div>
                <div class="card-info">
                    <h2><?php echo $completedCount; ?></h2>
                    <p>Completed</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon cancelled"><i class="fa-solid fa-times-circle"></i></div>
                <div class="card-info">
                    <h2><?php echo $cancelledCount; ?></h2>
                    <p>Cancelled</p>
                </div>
            </div>
        </div>

        <form method="GET" action="" class="filter-box">
            <input type="text" id="searchInput" placeholder="Search student name, counsellor or type...">
            
            <select name="counsellor">
                <option value="">All Counsellors</option>
                <?php while($c =$counsellorsFilterQuery->fetch_assoc()): ?>
                    <option value="<?php echo $c['full_name']; ?>"><?php echo $c['full_name']; ?></option>
                <?php endwhile; ?>
            </select>

            <select name="type">
                <option value="">All Types</option>
                <option value="Career Guidance">Career Guidance</option>
                <option value="Mental Health Support">Mental Health Support</option>
                <option value="Academic Counselling">Academic Counselling</option>
            </select>

            <select name="status">
                <option value="">All Status</option>
                <option value="PENDING">PENDING</option>
                <option value="APPROVED">APPROVED</option>
                <option value="COMPLETED">COMPLETED</option>
                <option value="CANCELLED">CANCELLED</option>
            </select>

            <input type="date" name="date">

            <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
        </form>

        <div class="table-container">
            <table id="appointmentTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Counsellor</th>
                        <th>Counselling Type</th>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if($result &&$result->num_rows > 0){
                        while($row =$result->fetch_assoc()){
                            $status = strtoupper($row['appointment_status']);
                            $statusClass = strtolower($status);
                            
                            echo "<tr>";
                            echo "<td>{$row['appointment_id']}</td>";
                            echo "<td class='searchable'>{$row['student_name']}</td>";
                            echo "<td class='searchable'>{$row['counsellor_name']}</td>";
                            echo "<td class='searchable'>{$row['counselling_type']}</td>";
                            echo "<td>{$row['available_date']}</td>";
                            echo "<td>{$row['start_time']}</td>";
                            echo "<td>{$row['end_time']}</td>";
                            echo "<td><span class='badge {$statusClass}'>{$status}</span></td>";
                            echo "<td>
                                    <button class='btn-action' title='View' onclick='viewAppointment({$row['appointment_id']}, \"{$row['student_name']}\", \"{$row['counsellor_name']}\", \"{$row['counselling_type']}\", \"{$status}\")'><i class='fa-regular fa-eye'></i></button>
                                    <button class='btn-action' title='Edit' onclick='editAppointment({$row['appointment_id']}, \"{$status}\")'><i class='fa-solid fa-pen'></i></button>
                                    <button class='btn-action delete' title='Delete' onclick='deleteAppointment({$row['appointment_id']})'><i class='fa-solid fa-trash'></i></button>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9' style='text-align:center;'>No Appointments Found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <div class="footer-tools">
            <div style="font-size:13px; color:var(--text-muted);">
                Showing total entries
            </div>
            <div class="export-btns">
                <button onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
                <button onclick="alert('Exporting PDF...')"><i class="fa-regular fa-file-pdf"></i> Export PDF</button>
                <button onclick="alert('Exporting Excel...')"><i class="fa-regular fa-file-excel"></i> Export Excel</button>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Add New Appointment -->
<div class="modal" id="addModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Appointment</h3>
            <span class="close-btn" onclick="closeModal('addModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <div class="form-group">
                <label>Select Student:</label>
                <select name="student_id" required>
                    <option value="">Select Student</option>
                    <?php while($s =$studentsList->fetch_assoc()): ?>
                        <option value="<?php echo $s['student_id']; ?>"><?php echo $s['full_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Select Counsellor:</label>
                <select name="counsellor_id" required>
                    <option value="">Select Counsellor</option>
                    <?php while($c =$counsellorsList->fetch_assoc()): ?>
                        <option value="<?php echo $c['counsellor_id']; ?>"><?php echo $c['full_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Counselling Type:</label>
                <select name="counselling_type" required>
                    <option value="Career Guidance">Career Guidance</option>
                    <option value="Mental Health Support">Mental Health Support</option>
                    <option value="Academic Counselling">Academic Counselling</option>
                </select>
            </div>

            <div class="form-group">
                <label>Select Availability Slot:</label>
                <select name="availability_id" required>
                    <option value="">Select Date & Time</option>
                    <?php while($av =$availabilityList->fetch_assoc()): ?>
                        <option value="<?php echo $av['availability_id']; ?>">
                            <?php echo $av['available_date'] . ' (' . $av['start_time'] . ' - ' .$av['end_time'] . ')'; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Appointment Status:</label>
                <select name="appointment_status" required>
                    <option value="PENDING">PENDING</option>
                    <option value="APPROVED">APPROVED</option>
                    <option value="COMPLETED">COMPLETED</option>
                    <option value="CANCELLED">CANCELLED</option>
                </select>
            </div>

            <button type="submit" name="add_appointment" class="add-btn" style="width:100%; justify-content:center; margin-top:10px;">Save Appointment</button>
        </form>
    </div>
</div>

<!-- Modal View -->
<div class="modal" id="viewModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Appointment Details</h3>
            <span class="close-btn" onclick="closeModal('viewModal')">&times;</span>
        </div>
        <div id="viewModalBody" style="font-size:14px; line-height: 1.8;"></div>
    </div>
</div>

<!-- Modal Edit -->
<div class="modal" id="editModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit Appointment Status</h3>
            <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="appointment_id" id="editAppId">
            <div class="form-group">
                <label>Change Status:</label>
                <select name="appointment_status" id="editStatusSelect">
                    <option value="PENDING">PENDING</option>
                    <option value="APPROVED">APPROVED</option>
                    <option value="COMPLETED">COMPLETED</option>
                    <option value="CANCELLED">CANCELLED</option>
                </select>
            </div>
            <button type="submit" name="update_appointment" class="add-btn" style="width:100%; justify-content:center;">Update Status</button>
        </form>
    </div>
</div>

<script>
// Search with Text Highlighting
document.getElementById("searchInput").addEventListener("input", function() {
    let filter = this.value.trim().toLowerCase();
    let rows = document.querySelectorAll("#appointmentTable tbody tr");

    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        if (text.includes(filter)) {
            row.style.display = "";
            highlightRowText(row, filter);
        } else {
            row.style.display = "none";
        }
    });
});

function highlightRowText(row, filter) {
    let searchableCells = row.querySelectorAll(".searchable");
    searchableCells.forEach(cell => {
        let originalText = cell.getAttribute("data-original") || cell.innerText;
        if(!cell.getAttribute("data-original")) {
            cell.setAttribute("data-original", originalText);
        }

        if (filter === "") {
            cell.innerHTML = originalText;
        } else {
            let regex = new RegExp(`(${filter})`, "gi");
            cell.innerHTML = originalText.replace(regex, "<mark>$1</mark>");
        }
    });
}

function openAddModal() {
    document.getElementById("addModal").style.display = "flex";
}

function viewAppointment(id, student, counsellor, type, status) {
    let details = `
        <p><strong>Appointment ID:</strong> #${id}</p>
        <p><strong>Student Name:</strong> ${student}</p>
        <p><strong>Counsellor Name:</strong> ${counsellor}</p>
        <p><strong>Counselling Type:</strong> ${type}</p>
        <p><strong>Status:</strong> ${status}</p>
    `;
    document.getElementById("viewModalBody").innerHTML = details;
    document.getElementById("viewModal").style.display = "flex";
}

function editAppointment(id, currentStatus) {
    document.getElementById("editAppId").value = id;
    document.getElementById("editStatusSelect").value = currentStatus;
    document.getElementById("editModal").style.display = "flex";
}

function deleteAppointment(id) {
    if (confirm("Are you sure you want to delete appointment #" + id + "?")) {
        window.location.href = "appointments.php?action=delete&id=" + id;
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = "none";
}
</script>

</body>
</html>