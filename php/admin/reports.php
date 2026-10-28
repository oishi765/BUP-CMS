<?php
$conn = new mysqli("localhost", "root", "oishi123", "bup_counselling_management");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

// Action: Handle Complaint Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {$complaint_id = intval($_POST['complaint_id']);$new_status = $conn->real_escape_string($_POST['complaint_status']);
    
    $updateStmt =$conn->prepare("UPDATE complaints SET complaint_status = ? WHERE complaint_id = ?");
    $updateStmt->bind_param("si", $new_status, $complaint_id);$updateStmt->execute();
    
    header("Location: reports.php");
    exit();
}

// Filter Query Setup for Complaints
$whereClauses = [];
if (!empty($_GET['status_filter'])) {$statusFilter = $conn->real_escape_string($_GET['status_filter']);
    $whereClauses[] = "c.complaint_status = '$statusFilter'";
}

$whereSql = count($whereClauses) > 0 ? " WHERE " . implode(" AND ", $whereClauses) : "";

// Fetch Complaints with Student Names
$complaintsSql = "
SELECT 
    c.complaint_id,
    c.user_id,
    c.subject,
    c.description,
    c.complaint_status,
    COALESCE(s.full_name, CONCAT('User #', c.user_id)) AS student_name
FROM complaints c
LEFT JOIN students s ON c.user_id = s.user_id
$whereSql
ORDER BY c.complaint_id DESC
";
$complaintsResult = $conn->query($complaintsSql);

// Fetch Complaints Statistics Summary
$totalComplaintsRes =$conn->query("SELECT COUNT(*) as total FROM complaints");
$totalComplaints = ($totalComplaintsRes ? $totalComplaintsRes->fetch_assoc()['total'] : 0) ?? 0;

$pendingRes =$conn->query("SELECT COUNT(*) as pending FROM complaints WHERE complaint_status = 'PENDING'");
$pendingCount = ($pendingRes ? $pendingRes->fetch_assoc()['pending'] : 0) ?? 0;

$inProgressRes =$conn->query("SELECT COUNT(*) as in_progress FROM complaints WHERE complaint_status = 'IN_PROGRESS'");
$inProgressCount = ($inProgressRes ? $inProgressRes->fetch_assoc()['in_progress'] : 0) ?? 0;

$resolvedRes =$conn->query("SELECT COUNT(*) as resolved FROM complaints WHERE complaint_status = 'RESOLVED'");
$resolvedCount = ($resolvedRes ? $resolvedRes->fetch_assoc()['resolved'] : 0) ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Reports & Complaints - BUP CMS</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
:root {
    --primary-green: #071e17; /* Darker background matching image */
    --accent-green: #00ce88;
    --active-green-bg: #0c3326;
    --light-bg: #f8fafc;
    --text-dark: #1e293b;
    --text-muted: #64748b;
    --border-color: #e2e8f0;
    --card-shadow: 0 4px 20px -2px rgba(0, 0, 0, 0.05);
}

* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
body { background-color: var(--light-bg); color: var(--text-dark); display: flex; min-height: 100vh; }

/* Sidebar Matching Image */
.sidebar { 
    width: 270px; 
    background-color: var(--primary-green); 
    color: white; 
    position: fixed; 
    height: 100vh; 
    display: flex; 
    flex-direction: column; 
    justify-content: space-between; 
    z-index: 100;
    padding: 24px 16px;
}

.brand { 
    display: flex; 
    align-items: center; 
    gap: 14px; 
    padding: 8px 10px 28px 10px;
}
.brand-icon { 
    width: 44px; 
    height: 44px; 
    background: rgba(0, 206, 136, 0.15); 
    border: 1px solid rgba(0, 206, 136, 0.3);
    border-radius: 12px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: var(--accent-green); 
    font-size: 20px;
}
.brand-title {
    display: flex;
    flex-direction: column;
}
.brand-title h2 { 
    font-size: 20px; 
    font-weight: 700;
    color: #fff; 
    letter-spacing: 0.5px; 
    line-height: 1.1;
}
.brand-title span {
    font-size: 11px;
    color: var(--accent-green);
    font-weight: 500;
    margin-top: 2px;
}

.sidebar ul { list-style: none; display: flex; flex-direction: column; gap: 6px; }
.sidebar ul li a { 
    display: flex; 
    align-items: center; 
    gap: 14px; 
    color: #94a3b8; 
    text-decoration: none; 
    padding: 12px 18px; 
    border-radius: 12px; 
    font-size: 14px; 
    font-weight: 500; 
    transition: all 0.2s ease; 
}
.sidebar ul li a i { font-size: 16px; width: 20px; text-align: center; }

.sidebar ul li a:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.05);
}

.sidebar ul li a.active { 
    background: var(--active-green-bg); 
    color: var(--accent-green); 
    border: 1px solid rgba(0, 206, 136, 0.2);
    font-weight: 600;
}

.sidebar ul li a.logout-btn {
    color: #ef4444;
    margin-top: 10px;
}
.sidebar ul li a.logout-btn:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #f87171;
}

.sidebar-footer { 
    padding: 10px; 
    font-size: 12px; 
    color: #475569; 
    text-align: center; 
    font-style: italic;
}

/* Main Section */
.main-wrapper { margin-left: 270px; flex: 1; display: flex; flex-direction: column; }
.topbar { height: 70px; background: #ffffff; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; padding: 0 35px; }
.topbar-search { display: flex; align-items: center; background: #f1f5f9; border: 1px solid transparent; border-radius: 10px; padding: 9px 16px; width: 320px; transition: 0.2s; }
.topbar-search:focus-within { border-color: #cbd5e1; background: #fff; }
.topbar-search input { background: transparent; border: none; outline: none; margin-left: 10px; width: 100%; font-size: 13px; }
.user-profile { display: flex; align-items: center; gap: 15px; }
.avatar { width: 38px; height: 38px; background: linear-gradient(135deg, #0d6efd, #0b5ed7); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 13px; box-shadow: 0 2px 8px rgba(13, 110, 253, 0.25); }

/* Content Grid */
.content { padding: 35px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
.page-title h1 { font-size: 26px; font-weight: 700; color: #0f172a; letter-spacing: -0.5px; }
.page-title p { font-size: 13px; color: var(--text-muted); margin-top: 2px; }

.export-btn { background: #071e17; color: white; border: none; padding: 11px 20px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: 0.2s; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }
.export-btn:hover { background: #0c3326; transform: translateY(-1px); }

/* KPI Stat Cards */
.cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 22px; margin-bottom: 28px; }
.card { background: white; padding: 22px; border-radius: 14px; border: 1px solid var(--border-color); display: flex; align-items: center; gap: 18px; box-shadow: var(--card-shadow); transition: transform 0.2s; }
.card:hover { transform: translateY(-2px); }
.card-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; }
.card-icon.total { background: #eff6ff; color: #2563eb; }
.card-icon.pending { background: #fef2f2; color: #dc2626; }
.card-icon.progress { background: #eff6ff; color: #2563eb; }
.card-icon.resolved { background: #f0fdf4; color: #16a34a; }

.card-info h2 { font-size: 24px; font-weight: 700; color: #0f172a; line-height: 1.2; }
.card-info p { font-size: 12px; color: var(--text-muted); font-weight: 500; margin-top: 2px; }

/* Charts Layout */
.charts-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 28px; }
.chart-card { background: white; padding: 24px; border-radius: 14px; border: 1px solid var(--border-color); box-shadow: var(--card-shadow); }
.chart-card h3 { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
.chart-container { position: relative; height: 220px; display: flex; justify-content: center; }

/* Filter Section */
.filter-box { background: white; padding: 16px 20px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 22px; display: flex; gap: 14px; align-items: center; box-shadow: var(--card-shadow); }
.filter-box input, .filter-box select { padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; outline: none; font-size: 13px; color: var(--text-dark); background: #f8fafc; transition: 0.2s; }
.filter-box input:focus, .filter-box select:focus { border-color: #94a3b8; background: #fff; }
.filter-box input[type="text"] { flex: 1; }
.filter-btn { background: #071e17; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; transition: 0.2s; }
.filter-btn:hover { background: #0c3326; }

/* Dynamic Table */
.table-container { background: white; border-radius: 14px; border: 1px solid var(--border-color); overflow: hidden; box-shadow: var(--card-shadow); }
table { width: 100%; border-collapse: collapse; }
th { background: #f8fafc; color: #475569; padding: 16px 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; text-align: left; border-bottom: 1px solid var(--border-color); }
td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
tr:last-child td { border-bottom: none; }
tr:hover { background-color: #f8fafc; }

/* Status Badges */
.badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; letter-spacing: 0.3px; }
.badge-PENDING { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.badge-IN_PROGRESS { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.badge-RESOLVED { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

.btn-action { background: #f1f5f9; border: 1px solid #e2e8f0; width: 34px; height: 34px; border-radius: 8px; cursor: pointer; color: #475569; transition: 0.2s; }
.btn-action:hover { background: #071e17; color: white; border-color: #071e17; }

/* Modal Window */
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); justify-content: center; align-items: center; z-index: 1000; }
.modal-content { background: white; padding: 28px; border-radius: 16px; width: 480px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; }
.modal-header h3 { font-size: 17px; font-weight: 700; color: #0f172a; }
.close-btn { cursor: pointer; font-size: 20px; color: #94a3b8; transition: 0.2s; }
.close-btn:hover { color: #0f172a; }

.form-group { margin-top: 15px; margin-bottom: 20px; }
.form-group label { display: block; font-size: 12px; font-weight: 700; margin-bottom: 6px; color: #475569; text-transform: uppercase; }
.form-group select { width: 100%; padding: 10px 14px; border: 1px solid var(--border-color); border-radius: 8px; outline: none; font-size: 13px; background: #f8fafc; }
.save-btn { width: 100%; background: #071e17; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 700; font-size: 14px; transition: 0.2s; }
.save-btn:hover { background: #0c3326; }
</style>
</head>

<body>

<!-- Sidebar matching image design -->
<div class="sidebar">
    <div>
        <div class="brand">
            <div class="brand-icon"><i class="fa-solid fa-leaf"></i></div>
            <div class="brand-title">
                <h2>BUP</h2>
                <span>Counselling & Wellness</span>
            </div>
        </div>
        <ul>
            <li><a href="admin.php"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
            <li><a href="manage-users.php"><i class="fa-solid fa-user-group"></i> User Management</a></li>
            <li><a href="counsellors.php"><i class="fa-solid fa-user-doctor"></i> Counsellors</a></li>
            <li><a href="workshops.php"><i class="fa-solid fa-chalkboard-user"></i> Workshops</a></li>
            <li><a href="appointments.php"><i class="fa-regular fa-calendar-check"></i> Appointments</a></li>
            <li><a href="feedback.php"><i class="fa-regular fa-comment-dots"></i> Feedback</a></li>
            <li><a href="reports.php" class="active"><i class="fa-solid fa-chart-line"></i> Reports</a></li>
            <li><a href="logout.php" class="logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
        </ul>
    </div>
    <div class="sidebar-footer">
        <p>"A Safer, Healthier, Stronger BUP"</p>
    </div>
</div>

<div class="main-wrapper">

    <div class="topbar">
        <div class="topbar-search">
            <i class="fa-solid fa-magnifying-glass" style="color: #94a3b8;"></i>
            <input type="text" placeholder="Search analytics or records...">
        </div>
        <div class="user-profile">
            <i class="fa-regular fa-bell" style="color: #64748b; font-size: 18px; cursor: pointer;"></i>
            <div class="avatar">EO</div>
            <div>
                <strong style="font-size: 13px; color: #0f172a;">Evana Hossain Oishi</strong>
                <p style="font-size: 11px; color: var(--text-muted);">Admin</p>
            </div>
        </div>
    </div>

    <div class="content">

        <div class="page-header">
            <div class="page-title">
                <h1>System Reports & Complaints</h1>
                <p>Track student complaints, resolution status, and analytic summaries</p>
            </div>
            <button class="export-btn" onclick="window.print()"><i class="fa-solid fa-file-pdf"></i> Export Report</button>
        </div>

        <div class="cards">
            <div class="card">
                <div class="card-icon total"><i class="fa-solid fa-clipboard-list"></i></div>
                <div class="card-info">
                    <h2><?php echo $totalComplaints; ?></h2>
                    <p>Total Complaints</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon pending"><i class="fa-solid fa-circle-exclamation"></i></div>
                <div class="card-info">
                    <h2><?php echo $pendingCount; ?></h2>
                    <p>Pending Review</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon progress"><i class="fa-solid fa-spinner"></i></div>
                <div class="card-info">
                    <h2><?php echo $inProgressCount; ?></h2>
                    <p>In Progress</p>
                </div>
            </div>
            <div class="card">
                <div class="card-icon resolved"><i class="fa-solid fa-circle-check"></i></div>
                <div class="card-info">
                    <h2><?php echo $resolvedCount; ?></h2>
                    <p>Resolved Cases</p>
                </div>
            </div>
        </div>

        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="fa-solid fa-chart-pie" style="color: #2563eb;"></i> Complaint Status Ratio</h3>
                <div class="chart-container">
                    <canvas id="statusPieChart"></canvas>
                </div>
            </div>
            <div class="chart-card">
                <h3><i class="fa-solid fa-chart-column" style="color: #16a34a;"></i> Status Distribution Breakdown</h3>
                <div class="chart-container">
                    <canvas id="statusBarChart"></canvas>
                </div>
            </div>
        </div>

        <form method="GET" action="" class="filter-box">
            <i class="fa-solid fa-magnifying-glass" style="color: #94a3b8;"></i>
            <input type="text" id="searchInput" placeholder="Search student name, subject, or description...">
            
            <select name="status_filter">
                <option value="">All Statuses</option>
                <option value="PENDING" <?php echo (isset($_GET['status_filter']) &&$_GET['status_filter'] === 'PENDING') ? 'selected' : ''; ?>>Pending</option>
                <option value="IN_PROGRESS" <?php echo (isset($_GET['status_filter']) &&$_GET['status_filter'] === 'IN_PROGRESS') ? 'selected' : ''; ?>>In Progress</option>
                <option value="RESOLVED" <?php echo (isset($_GET['status_filter']) &&$_GET['status_filter'] === 'RESOLVED') ? 'selected' : ''; ?>>Resolved</option>
            </select>

            <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> Filter</button>
        </form>

        <div class="table-container">
            <table id="complaintsTable">
                <thead>
                    <tr>
                        <th># ID</th>
                        <th>Student Name</th>
                        <th>Subject</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($complaintsResult &&$complaintsResult->num_rows > 0) {
                        while ($row =$complaintsResult->fetch_assoc()) {
                            $studentName = htmlspecialchars($row['student_name']);
                            $subject = htmlspecialchars($row['subject']);
                            $description = htmlspecialchars($row['description']);
                            $status = htmlspecialchars($row['complaint_status']);

                            $jsonStudent = json_encode($row['student_name']);
                            $jsonSubject = json_encode($row['subject']);
                            $jsonDesc = json_encode($row['description']);
                            $jsonStatus = json_encode($row['complaint_status']);

                            echo "<tr>";
                            echo "<td style='font-weight:600; color:#64748b;'>#{$row['complaint_id']}</td>";
                            echo "<td style='font-weight:600;'>{$studentName}</td>";
                            echo "<td>{$subject}</td>";
                            echo "<td style='color:#64748b;'>{$description}</td>";
                            echo "<td><span class='badge badge-{$status}'><i class='fa-solid fa-circle' style='font-size:6px;'></i> {$status}</span></td>";
                            echo "<td>
                                    <button class='btn-action' title='View & Update' onclick='openManageModal({$row['complaint_id']}, {$jsonStudent}, {$jsonSubject}, {$jsonDesc}, {$jsonStatus})'><i class='fa-regular fa-eye'></i></button>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding: 30px; color: #94a3b8;'>No Complaint Records Found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<div class="modal" id="manageModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Complaint Details</h3>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="complaint_id" id="modalComplaintId">
            <div id="modalDetails" style="font-size:13px; line-height: 1.8; color: #334155;"></div>
            
            <div class="form-group">
                <label for="modalStatusSelect">Update Status</label>
                <select name="complaint_status" id="modalStatusSelect" required>
                    <option value="PENDING">PENDING</option>
                    <option value="IN_PROGRESS">IN_PROGRESS</option>
                    <option value="RESOLVED">RESOLVED</option>
                </select>
            </div>
            
            <button type="submit" name="update_status" class="save-btn"><i class="fa-solid fa-floppy-disk"></i> Save Status</button>
        </form>
    </div>
</div>

<script>
// Dynamic Search Filter
document.getElementById("searchInput").addEventListener("input", function() {
    let filter = this.value.trim().toLowerCase();
    let rows = document.querySelectorAll("#complaintsTable tbody tr");

    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});

// Modal Control
function openManageModal(id, student, subject, desc, status) {
    document.getElementById("modalComplaintId").value = id;
    let details = `
        <p><strong>Complaint ID:</strong> #${id}</p>
        <p><strong>Student:</strong> ${student}</p>
        <p><strong>Subject:</strong> ${subject}</p>
        <p style="margin-top:8px;"><strong>Description:</strong></p>
        <div style="background:#f8fafc; padding:12px; border-radius:8px; margin-top:4px; border: 1px solid #e2e8f0; color:#475569; white-space: pre-wrap;">${desc}</div>
    `;
    document.getElementById("modalDetails").innerHTML = details;
    document.getElementById("modalStatusSelect").value = status;
    document.getElementById("manageModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("manageModal").style.display = "none";
}

// Chart Palette
const chartColors = {
    red: '#dc2626',
    blue: '#2563eb',
    green: '#16a34a'
};

// Pie Chart
const pieCtx = document.getElementById('statusPieChart').getContext('2d');
new Chart(pieCtx, {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'In Progress', 'Resolved'],
        datasets: [{
            data: [<?php echo $pendingCount; ?>, <?php echo $inProgressCount; ?>, <?php echo$resolvedCount; ?>],
            backgroundColor: [chartColors.red, chartColors.blue, chartColors.green],
            borderWidth: 2,
            borderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { 
                position: 'bottom',
                labels: { boxWidth: 12, font: { family: 'Plus Jakarta Sans', size: 12 } }
            } 
        },
        cutout: '65%'
    }
});

// Bar Chart
const barCtx = document.getElementById('statusBarChart').getContext('2d');
new Chart(barCtx, {
    type: 'bar',
    data: {
        labels: ['Pending', 'In Progress', 'Resolved'],
        datasets: [{
            label: 'Total Complaints',
            data: [<?php echo $pendingCount; ?>, <?php echo $inProgressCount; ?>, <?php echo$resolvedCount; ?>],
            backgroundColor: [chartColors.red, chartColors.blue, chartColors.green],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
            y: { 
                beginAtZero: true, 
                ticks: { stepSize: 1, font: { family: 'Plus Jakarta Sans' } },
                grid: { color: '#f1f5f9' }
            },
            x: {
                ticks: { font: { family: 'Plus Jakarta Sans' } },
                grid: { display: false }
            }
        }
    }
});
</script>

</body>
</html>