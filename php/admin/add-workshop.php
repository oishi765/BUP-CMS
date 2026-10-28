<?php

require_once "../config/db.php";

if(isset($_POST['save']))
{
    $organizer_id=$_POST['organizer_id'];
    $trainer_id=$_POST['trainer_id'];
    $title=$_POST['title'];
    $description=$_POST['description'];
    $venue=$_POST['venue'];
    $workshop_date=$_POST['workshop_date'];
    $start_time=$_POST['start_time'];
    $end_time=$_POST['end_time'];
    $capacity=$_POST['capacity'];
    $status=$_POST['status'];

    mysqli_query($conn,"
    INSERT INTO workshops
    (
    organizer_id,
    trainer_id,
    title,
    description,
    venue,
    workshop_date,
    start_time,
    end_time,
    capacity,
    status
    )
    VALUES
    (
    '$organizer_id',
    '$trainer_id',
    '$title',
    '$description',
    '$venue',
    '$workshop_date',
    '$start_time',
    '$end_time',
    '$capacity',
    '$status'
    )
    ");

    header("Location:workshops.php");
}

?>

<!DOCTYPE html>
<html>
<head>

<title>Add Workshop</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

body{
background:#f4fdf9;
font-family:Segoe UI;
}

.container-box{
width:800px;
margin:40px auto;
background:white;
padding:30px;
border-radius:20px;
box-shadow:0 10px 25px rgba(0,0,0,.08);
}

h2{
color:#0b5d4b;
margin-bottom:25px;
}

.btn-save{
background:#10b981;
color:white;
}

</style>

</head>

<body>

<div class="container-box">

<h2>Add Workshop</h2>

<form method="POST">

<div class="row">

<div class="col-md-6 mb-3">
<label>Organizer ID</label>
<input type="number" name="organizer_id" class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Trainer ID</label>
<input type="number" name="trainer_id" class="form-control">
</div>

<div class="col-md-12 mb-3">
<label>Workshop Title</label>
<input type="text" name="title" class="form-control">
</div>

<div class="col-md-12 mb-3">
<label>Description</label>
<textarea name="description" class="form-control"></textarea>
</div>

<div class="col-md-6 mb-3">
<label>Venue</label>
<input type="text" name="venue" class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Date</label>
<input type="date" name="workshop_date" class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Start Time</label>
<input type="time" name="start_time" class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>End Time</label>
<input type="time" name="end_time" class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Capacity</label>
<input type="number" name="capacity" class="form-control">
</div>

<div class="col-md-6 mb-3">
<label>Status</label>

<select name="status" class="form-control">

<option value="ACTIVE">ACTIVE</option>
<option value="COMPLETED">COMPLETED</option>
<option value="CANCELLED">CANCELLED</option>

</select>

</div>

</div>

<button class="btn btn-save" name="save">
Save Workshop
</button>

</form>

</div>

</body>
</html>