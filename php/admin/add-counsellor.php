<?php
require_once "../config/db.php";

if(isset($_POST['save']))
{
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $specialization = mysqli_real_escape_string($conn, $_POST['specialization']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $experience_years = mysqli_real_escape_string($conn, $_POST['experience_years']);

    $sql = "INSERT INTO counsellors
    (user_id, full_name, specialization, email, phone, experience_years)
    VALUES
    ('$user_id','$full_name','$specialization','$email','$phone','$experience_years')";

    if(mysqli_query($conn, $sql))
    {
        header("Location: counsellors.php");
        exit();
    }
    else
    {
        $error = mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Counsellor</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:'Segoe UI',sans-serif;
}

body{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:#edf7f3;
    position:relative;
    overflow-y:auto; /* স্ক্রোল করার সুযোগ দেবে */
    padding:40px 15px; /* উপর-নিচে পর্যাপ্ত জায়গা রাখবে */
}

.bg-circle1{
    position:fixed;
    width:320px;
    height:320px;
    background:#d7ebe4;
    border-radius:50%;
    top:-100px;
    left:-100px;
    z-index: 1;
}

.bg-circle2{
    position:fixed;
    width:400px;
    height:400px;
    background:#d7ebe4;
    border-radius:50%;
    bottom:-150px;
    right:-150px;
    z-index: 1;
}

.form-card{
    width:100%;
    max-width:520px;
    background:#fff;
    border-radius:20px;
    padding:30px 35px;
    box-shadow:0 15px 35px rgba(0,0,0,0.08);
    z-index:2;
    margin: auto;
}

.logo{
    width:75px;
    height:75px;
    object-fit:contain;
    display:block;
    margin:0 auto 10px;
}

.title{
    text-align:center;
    color:#0b4f3b;
    font-weight:700;
    margin-bottom:4px;
    font-size: 24px;
}

.subtitle{
    text-align:center;
    color:#6c8f85;
    font-size:13px;
    margin-bottom:20px;
}

.form-label{
    color:#0b4f3b;
    font-weight:600;
    font-size:14px;
    margin-bottom:5px;
}

.form-control{
    height:44px;
    border-radius:10px;
    border:1px solid #d6e5df;
    font-size:14px;
}

.form-control:focus{
    border-color:#198754;
    box-shadow:0 0 0 0.15rem rgba(25,135,84,.15);
}

.mb-3 {
    margin-bottom: 12px !important;
}

.btn-save{
    width:100%;
    height:46px;
    border:none;
    border-radius:10px;
    background:linear-gradient(135deg,#198754,#0b4f3b);
    color:white;
    font-weight:600;
    transition:0.3s;
    margin-top:10px;
}

.btn-save:hover{
    transform:translateY(-2px);
    background:linear-gradient(135deg,#157347,#083d2d);
}

.back-btn{
    display:block;
    text-align:center;
    margin-top:15px;
    text-decoration:none;
    color:#198754;
    font-weight:600;
    font-size:14px;
}

.back-btn:hover{
    color:#0b4f3b;
}

</style>

</head>
<body>

<div class="bg-circle1"></div>
<div class="bg-circle2"></div>

<div class="form-card">

    <img src="../../images/logo.png" class="logo" alt="Logo">

    <h2 class="title">Add Counsellor</h2>

    <p class="subtitle">
        BUP Counselling & Wellness Management System
    </p>

    <?php if(isset($error)){ ?>
        <div class="alert alert-danger py-2" style="font-size: 13px;">
            <?= $error; ?>
        </div>
    <?php } ?>

    <form method="POST">

        <div class="mb-3">
            <label class="form-label">User ID</label>
            <input type="number"
                   name="user_id"
                   class="form-control"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text"
                   name="full_name"
                   class="form-control"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Specialization</label>
            <input type="text"
                   name="specialization"
                   class="form-control"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email"
                   name="email"
                   class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Phone Number</label>
            <input type="text"
                   name="phone"
                   class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Experience (Years)</label>
            <input type="number"
                   name="experience_years"
                   class="form-control"
                   min="0">
        </div>

        <button type="submit"
                name="save"
                class="btn-save">
            Save Counsellor
        </button>

        <a href="counsellors.php" class="back-btn">
            ← Back to Counsellors
        </a>

    </form>

</div>

</body>
</html>