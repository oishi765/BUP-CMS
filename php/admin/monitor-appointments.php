<?php
$conn = new mysqli("localhost", "root", "oishi123", "bup_counselling_management");

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

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
JOIN students s ON a.user_id = s.user_id
JOIN counsellors c ON a.counsellor_id = c.counsellor_id
JOIN availability av ON a.availability_id = av.availability_id
ORDER BY a.appointment_id DESC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Monitor Appointments</title>

<style>

body{
    font-family: Arial, sans-serif;
    background:#f4f6f9;
    padding:30px;
}

.container{
    width:95%;
    margin:auto;
}

h2{
    text-align:center;
    color:#003366;
    margin-bottom:20px;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
}

th{
    background:#003366;
    color:white;
    padding:12px;
}

td{
    padding:10px;
    border:1px solid #ddd;
    text-align:center;
}

tr:nth-child(even){
    background:#f2f2f2;
}

.pending{
    color:orange;
    font-weight:bold;
}

.approved{
    color:green;
    font-weight:bold;
}

.completed{
    color:blue;
    font-weight:bold;
}

.cancelled{
    color:red;
    font-weight:bold;
}

</style>
</head>

<body>

<div class="container">

<h2>Monitor Appointments</h2>

<table>

<tr>
    <th>ID</th>
    <th>Student</th>
    <th>Counsellor</th>
    <th>Counselling Type</th>
    <th>Date</th>
    <th>Start Time</th>
    <th>End Time</th>
    <th>Status</th>
</tr>

<?php
if($result->num_rows > 0){

while($row = $result->fetch_assoc()){

$status = strtolower($row['appointment_status']);

echo "
<tr>
<td>{$row['appointment_id']}</td>
<td>{$row['student_name']}</td>
<td>{$row['counsellor_name']}</td>
<td>{$row['counselling_type']}</td>
<td>{$row['available_date']}</td>
<td>{$row['start_time']}</td>
<td>{$row['end_time']}</td>
<td class='$status'>{$row['appointment_status']}</td>
</tr>
";
}

}else{

echo "
<tr>
<td colspan='8'>No Appointments Found</td>
</tr>
";

}
?>

</table>

</div>

</body>
</html>