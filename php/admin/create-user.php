<?php
session_start();
require_once "../config/db.php";

if(!isset($_SESSION['user_id']))
{
    header("Location: login.php");
    exit();
}

$message = "";

if(isset($_POST['create']))
{
    $role = $_POST['role'];

    $full_name = mysqli_real_escape_string($conn,$_POST['full_name']);
    $username = mysqli_real_escape_string($conn,$_POST['username']);
    $password = mysqli_real_escape_string($conn,$_POST['password']);

    $created_by = $_SESSION['user_id'];

    $check = mysqli_query($conn,"SELECT * FROM users WHERE username='$username'");

    if(mysqli_num_rows($check)>0)
    {
        $message="Username Already Exists!";
    }
    else
    {
        mysqli_begin_transaction($conn);

        try
        {
            $user_sql = "INSERT INTO users
            (
                full_name,
                username,
                password_hash,
                role,
                created_by
            )
            VALUES
            (
                '$full_name',
                '$username',
                '$password',
                '$role',
                '$created_by'
            )";

            mysqli_query($conn,$user_sql);

            $user_id = mysqli_insert_id($conn);

            // STUDENT
            if($role=="STUDENT")
            {
                $student_no = $_POST['student_no'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $department = $_POST['department'];
                $semester = $_POST['semester'];

                mysqli_query($conn,"
                INSERT INTO students
                (
                    user_id,
                    student_no,
                    full_name,
                    email,
                    phone,
                    department,
                    semester
                )
                VALUES
                (
                    '$user_id',
                    '$student_no',
                    '$full_name',
                    '$email',
                    '$phone',
                    '$department',
                    '$semester'
                )");
            }

            // FACULTY
            elseif($role=="FACULTY")
            {
                $faculty_no = $_POST['faculty_no'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $department = $_POST['department'];
                $designation = $_POST['designation'];

                mysqli_query($conn,"
                INSERT INTO faculty
                (
                    user_id,
                    faculty_no,
                    full_name,
                    email,
                    phone,
                    department,
                    designation
                )
                VALUES
                (
                    '$user_id',
                    '$faculty_no',
                    '$full_name',
                    '$email',
                    '$phone',
                    '$department',
                    '$designation'
                )");
            }

            // COUNSELLOR
            elseif($role=="COUNSELLOR")
            {
                $specialization = $_POST['specialization'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];
                $experience_years = $_POST['experience_years'];

                mysqli_query($conn,"
                INSERT INTO counsellors
                (
                    user_id,
                    full_name,
                    specialization,
                    email,
                    phone,
                    experience_years
                )
                VALUES
                (
                    '$user_id',
                    '$full_name',
                    '$specialization',
                    '$email',
                    '$phone',
                    '$experience_years'
                )");
            }

            // STAFF
            elseif($role=="STAFF")
            {
                $designation = $_POST['designation'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];

                mysqli_query($conn,"
                INSERT INTO staff
                (
                    user_id,
                    full_name,
                    designation,
                    email,
                    phone
                )
                VALUES
                (
                    '$user_id',
                    '$full_name',
                    '$designation',
                    '$email',
                    '$phone'
                )");
            }

            // TRAINER
            elseif($role=="TRAINER")
            {
                $expertise = $_POST['expertise'];
                $email = $_POST['email'];
                $phone = $_POST['phone'];

                mysqli_query($conn,"
                INSERT INTO trainers
                (
                    user_id,
                    full_name,
                    expertise,
                    email,
                    phone
                )
                VALUES
                (
                    '$user_id',
                    '$full_name',
                    '$expertise',
                    '$email',
                    '$phone'
                )");
            }

            // ORGANIZER
            elseif($role=="ORGANIZER")
            {
                $email = $_POST['email'];
                $phone = $_POST['phone'];

                mysqli_query($conn,"
                INSERT INTO organizers
                (
                    user_id,
                    full_name,
                    email,
                    phone
                )
                VALUES
                (
                    '$user_id',
                    '$full_name',
                    '$email',
                    '$phone'
                )");
            }

            // AUTHORITY
            elseif($role=="AUTHORITY")
            {
                $designation = $_POST['designation'];

                mysqli_query($conn,"
                INSERT INTO authority_members
                (
                    user_id,
                    full_name,
                    designation
                )
                VALUES
                (
                    '$user_id',
                    '$full_name',
                    '$designation'
                )");
            }

            mysqli_commit($conn);

            $message="User Created Successfully!";
        }
        catch(Exception $e)
        {
            mysqli_rollback($conn);
            $message="Database Error!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create User</title>

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
    background:#e8f5ee;
    overflow:hidden;
    position:relative;
}

.leaf1{
    position:absolute;
    top:-100px;
    left:-80px;
    width:350px;
    height:350px;
    background:radial-gradient(circle,#198754,#0b3d2e);
    border-radius:50%;
    opacity:.15;
}

.leaf2{
    position:absolute;
    bottom:-120px;
    right:-100px;
    width:450px;
    height:450px;
    background:radial-gradient(circle,#20c997,#0b3d2e);
    border-radius:50%;
    opacity:.12;
}

.container{
    width:650px;
    background:white;
    padding:35px;
    border-radius:20px;
    box-shadow:0 15px 40px rgba(0,0,0,.08);
    position:relative;
    z-index:10;
}

.logo-box{
    text-align:center;
    margin-bottom:20px;
}

.logo{
    width:90px;
    margin-bottom:10px;
}

.logo-box h2{
    color:#0b3d2e;
    font-size:30px;
    margin-bottom:5px;
}

.logo-box p{
    color:#198754;
    font-size:14px;
}

label{
    display:block;
    margin-bottom:6px;
    color:#0b3d2e;
    font-weight:600;
}

input,
select{
    width:100%;
    padding:13px;
    margin-bottom:15px;
    border:1px solid #d6e7dd;
    border-radius:10px;
    background:#f8fbf9;
    font-size:15px;
    outline:none;
}

input:focus,
select:focus{
    border-color:#198754;
    box-shadow:0 0 0 3px rgba(25,135,84,.15);
}

button{
    width:100%;
    padding:14px;
    background:linear-gradient(135deg,#198754,#0b3d2e);
    color:white;
    border:none;
    border-radius:10px;
    font-size:17px;
    font-weight:600;
    cursor:pointer;
}

button:hover{
    transform:translateY(-2px);
}

.message{
    text-align:center;
    padding:10px;
    margin-bottom:15px;
    border-radius:8px;
    background:#d1e7dd;
    color:#0f5132;
    font-weight:600;
}
</style>

</head>

<body>

<div class="leaf1"></div>
<div class="leaf2"></div>

<div class="container">

    <div class="logo-box">
        <img src="../../images/logo.png" class="logo">
        <h2>Create User</h2>
        <p>BUP Counselling & Wellness Management System</p>
    </div>

    <?php
    if(!empty($message))
    {
        echo "<div class='message'>$message</div>";
    }
    ?>

<!-- তোমার আগের head, css, body design সব একই রাখবে -->

<form method="POST">

<label>Full Name</label>
<input type="text" name="full_name" required>

<label>Username</label>
<input type="text" name="username" required>

<label>Password</label>
<input type="password" name="password" required>

<label>Role</label>
<select name="role" id="role" onchange="showFields()" required>
    <option value="">Select Role</option>
    <option value="STUDENT">Student</option>
    <option value="FACULTY">Faculty</option>
    <option value="COUNSELLOR">Counsellor</option>
    <option value="STAFF">Staff</option>
    <option value="TRAINER">Trainer</option>
    <option value="ORGANIZER">Organizer</option>
    <option value="AUTHORITY">Authority</option>
</select>

<div id="dynamicFields"></div>

<button type="submit" name="create">
    Create User
</button>

</form>

<script>
function showFields()
{
    let role=document.getElementById("role").value;
    let html="";

    if(role=="STUDENT")
    {
        html=`
        <input type="text" name="student_no" placeholder="Student ID" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone" required>
        <input type="text" name="department" placeholder="Department" required>
        <input type="text" name="semester" placeholder="Semester" required>
        `;
    }

    else if(role=="FACULTY")
    {
        html=`
        <input type="text" name="faculty_no" placeholder="Faculty ID" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone" required>
        <input type="text" name="department" placeholder="Department" required>
        <input type="text" name="designation" placeholder="Designation" required>
        `;
    }

    else if(role=="COUNSELLOR")
    {
        html=`
        <input type="text" name="specialization" placeholder="Specialization" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone" required>
        <input type="number" name="experience_years" placeholder="Experience Years" required>
        `;
    }

    else if(role=="STAFF")
    {
        html=`
        <input type="text" name="designation" placeholder="Designation" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone" required>
        `;
    }

    else if(role=="TRAINER")
    {
        html=`
        <input type="text" name="expertise" placeholder="Expertise" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone" required>
        `;
    }

    else if(role=="ORGANIZER")
    {
        html=`
        <input type="email" name="email" placeholder="Email" required>
        <input type="text" name="phone" placeholder="Phone" required>
        `;
    }

    else if(role=="AUTHORITY")
    {
        html=`
        <input type="text" name="designation" placeholder="Designation" required>
        `;
    }

    document.getElementById("dynamicFields").innerHTML=html;
}
</script>
</div>
</body>
</html>