<?php
require_once "../config/db.php";

$id = $_GET['id'];

$result = mysqli_query(
$conn,
"SELECT * FROM counsellors
WHERE counsellor_id='$id'"
);

$data = mysqli_fetch_assoc($result);

if(isset($_POST['update']))
{
    $user_id = $_POST['user_id'];
    $full_name = $_POST['full_name'];
    $specialization = $_POST['specialization'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $experience_years = $_POST['experience_years'];

    mysqli_query($conn,"
    UPDATE counsellors
    SET
    user_id='$user_id',
    full_name='$full_name',
    specialization='$specialization',
    email='$email',
    phone='$phone',
    experience_years='$experience_years'
    WHERE counsellor_id='$id'
    ");

    header("Location:counsellors.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Edit Counsellor</title>

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

    background:
    linear-gradient(
    135deg,
    #021c17,
    #063126,
    #0a4d3a
    );

    overflow:hidden;
    position:relative;
}

/* Background Design */

body::before{
    content:"";
    position:absolute;
    width:450px;
    height:450px;
    border-radius:50%;

    background:
    rgba(0,255,150,.08);

    top:-150px;
    right:-120px;
}

body::after{
    content:"";
    position:absolute;
    width:400px;
    height:400px;
    border-radius:50%;

    background:
    rgba(0,255,150,.05);

    bottom:-150px;
    left:-120px;
}

.container{
    width:650px;

    background:
    rgba(255,255,255,.05);

    backdrop-filter:blur(15px);

    border:
    1px solid rgba(255,255,255,.08);

    border-radius:25px;

    padding:40px;

    box-shadow:
    0 8px 40px rgba(0,0,0,.35);

    position:relative;
    z-index:2;
}

/* Logo */

.logo{
    text-align:center;
    margin-bottom:15px;
}

.logo img{
    width:90px;
}

/* Heading */

h2{
    text-align:center;
    color:#42e5a8;
    font-size:36px;
    margin-bottom:8px;
}

.subtitle{
    text-align:center;
    color:#cbd5d1;
    margin-bottom:30px;
}

/* Labels */

label{
    display:block;
    margin-bottom:6px;
    color:white;
    font-weight:600;
}

/* Inputs */

input{
    width:100%;
    padding:14px;

    margin-bottom:18px;

    border-radius:12px;

    border:
    1px solid rgba(255,255,255,.15);

    background:
    rgba(255,255,255,.05);

    color:white;

    font-size:15px;
}

input:focus{
    outline:none;

    border-color:#42e5a8;

    box-shadow:
    0 0 15px rgba(66,229,168,.4);
}

button{
    width:100%;
    padding:15px;

    border:none;
    border-radius:12px;

    background:
    linear-gradient(
    135deg,
    #1fa463,
    #0f7b4f
    );

    color:white;

    font-size:16px;
    font-weight:600;

    cursor:pointer;

    transition:.3s;
}

button:hover{
    transform:translateY(-2px);

    box-shadow:
    0 10px 25px rgba(0,255,150,.25);
}

.footer-text{
    text-align:center;
    color:#cbd5d1;
    margin-top:20px;
    font-style:italic;
}

</style>

</head>
<body>

<div class="container">

    <div class="logo">
        <img src="../../images/logo.png" class="logo">
    </div>

    <h2>Edit Counsellor</h2>

    <p class="subtitle">
        BUP Counselling & Wellness Management System
    </p>

    <form method="POST">

        <label>User ID</label>
        <input
        type="number"
        name="user_id"
        value="<?= $data['user_id']; ?>"
        required>

        <label>Full Name</label>
        <input
        type="text"
        name="full_name"
        value="<?= $data['full_name']; ?>"
        required>

        <label>Specialization</label>
        <input
        type="text"
        name="specialization"
        value="<?= $data['specialization']; ?>"
        required>

        <label>Email</label>
        <input
        type="email"
        name="email"
        value="<?= $data['email']; ?>"
        required>

        <label>Phone</label>
        <input
        type="text"
        name="phone"
        value="<?= $data['phone']; ?>"
        required>

        <label>Experience Years</label>
        <input
        type="number"
        name="experience_years"
        value="<?= $data['experience_years']; ?>"
        required>

        <button type="submit" name="update">
            Update Counsellor
        </button>

    </form>

    <div class="footer-text">
        🌿 Empowering Mental Wellness Through Technology
    </div>

</div>

</body>
</html>