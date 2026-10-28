<?php

require_once "../config/db.php";

$id=$_GET['id'];

$result=mysqli_query(
$conn,
"SELECT * FROM workshops
WHERE workshop_id='$id'"
);

$data=mysqli_fetch_assoc($result);

if(isset($_POST['update']))
{
    mysqli_query($conn,"
    UPDATE workshops
    SET
    organizer_id='$_POST[organizer_id]',
    trainer_id='$_POST[trainer_id]',
    title='$_POST[title]',
    description='$_POST[description]',
    venue='$_POST[venue]',
    workshop_date='$_POST[workshop_date]',
    start_time='$_POST[start_time]',
    end_time='$_POST[end_time]',
    capacity='$_POST[capacity]',
    status='$_POST[status]'
    WHERE workshop_id='$id'
    ");

    header("Location:workshops.php");
}
?>

<!DOCTYPE html>
<html>
<head>

<title>Edit Workshop</title>

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

</style>

</head>

<body>

<div class="container-box">

<h2>Edit Workshop</h2>

<form method="POST">

<input type="number" class="form-control mb-3"
name="organizer_id"
value="<?= $data['organizer_id']; ?>">

<input type="number" class="form-control mb-3"
name="trainer_id"
value="<?= $data['trainer_id']; ?>">

<input type="text" class="form-control mb-3"
name="title"
value="<?= $data['title']; ?>">

<textarea class="form-control mb-3"
name="description"><?= $data['description']; ?></textarea>

<input type="text" class="form-control mb-3"
name="venue"
value="<?= $data['venue']; ?>">

<input type="date" class="form-control mb-3"
name="workshop_date"
value="<?= $data['workshop_date']; ?>">

<input type="time" class="form-control mb-3"
name="start_time"
value="<?= $data['start_time']; ?>">

<input type="time" class="form-control mb-3"
name="end_time"
value="<?= $data['end_time']; ?>">

<input type="number" class="form-control mb-3"
name="capacity"
value="<?= $data['capacity']; ?>">

<select name="status"
class="form-control mb-3">

<option <?= $data['status']=="ACTIVE"?"selected":"" ?>>
ACTIVE
</option>

<option <?= $data['status']=="COMPLETED"?"selected":"" ?>>
COMPLETED
</option>

<option <?= $data['status']=="CANCELLED"?"selected":"" ?>>
CANCELLED
</option>

</select>

<button class="btn btn-success"
name="update">
Update Workshop
</button>

</form>

</div>

</body>
</html>