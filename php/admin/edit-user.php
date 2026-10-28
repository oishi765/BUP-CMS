<?php
session_start();
require_once "../config/db.php";

$id = isset($_GET['id']) ? mysqli_real_escape_string($conn, $_GET['id']) : '';

if (!empty($id)) {
    $result = mysqli_query(
        $conn,
        "SELECT * FROM users WHERE user_id='$id'"
    );
    $user = mysqli_fetch_assoc($result);
}

if (!$user) {
    header("Location: manage-users.php");
    exit();
}

if(isset($_POST['update']))
{
    $full_name  = mysqli_real_escape_string($conn, $_POST['full_name']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    $username   = mysqli_real_escape_string($conn, $_POST['username']);
    $role       = mysqli_real_escape_string($conn, $_POST['role']);

    mysqli_query(
        $conn,
        "UPDATE users SET
        full_name='$full_name',
        student_id='$student_id',
        department='$department',
        username='$username',
        role='$role'
        WHERE user_id='$id'"
    );

    header("Location: manage-users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>

    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', sans-serif;
    }

    body {
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        background: linear-gradient(135deg, #021c17, #063126, #0a4d3a);
        overflow-y: auto;
        padding: 20px 0;
        position: relative;
    }

    /* Background Design */
    body::before {
        content: "";
        position: absolute;
        width: 450px;
        height: 450px;
        border-radius: 50%;
        background: rgba(0, 255, 150, .08);
        top: -150px;
        right: -120px;
    }

    body::after {
        content: "";
        position: absolute;
        width: 400px;
        height: 400px;
        border-radius: 50%;
        background: rgba(0, 255, 150, .05);
        bottom: -150px;
        left: -120px;
    }

    .container {
        width: 600px;
        max-width: 90%;
        background: rgba(255, 255, 255, .05);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: 25px;
        padding: 40px;
        box-shadow: 0 8px 40px rgba(0, 0, 0, .35);
        position: relative;
        z-index: 2;
    }

    /* Logo */
    .logo {
        text-align: center;
        margin-bottom: 15px;
    }

    .logo img {
        width: 90px;
        height: auto;
    }

    /* Headings */
    h2 {
        text-align: center;
        color: #42e5a8;
        font-size: 32px;
        margin-bottom: 8px;
    }

    .subtitle {
        text-align: center;
        color: #cbd5d1;
        margin-bottom: 25px;
        font-size: 14px;
    }

    /* Labels */
    label {
        display: block;
        margin-bottom: 6px;
        color: white;
        font-weight: 600;
        font-size: 14px;
    }

    /* Inputs & Select */
    input,
    select {
        width: 100%;
        padding: 14px;
        margin-bottom: 18px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, .15);
        background: rgba(255, 255, 255, .05);
        color: white;
        font-size: 15px;
    }

    /* Options Background Fix */
    option {
        background: #0a4d3a;
        color: white;
    }

    input:focus,
    select:focus {
        outline: none;
        border-color: #42e5a8;
        box-shadow: 0 0 15px rgba(66, 229, 168, .4);
    }

    /* Button */
    button {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, #1fa463, #0f7b4f);
        color: white;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: .3s;
        margin-top: 10px;
    }

    button:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 255, 150, .25);
    }

    .footer-text {
        text-align: center;
        color: #cbd5d1;
        margin-top: 20px;
        font-style: italic;
        font-size: 13px;
    }
    </style>
</head>

<body>

<div class="container">
    <div class="logo">
        <img src="../../images/logo.png" alt="BUP Logo">
    </div>

    <h2>Edit User</h2>

    <p class="subtitle">
        BUP Counselling & Wellness Management System
    </p>

    <form method="POST">

        <label>Full Name</label>
        <input 
            type="text" 
            name="full_name" 
            value="<?php echo htmlspecialchars($user['full_name']); ?>" 
            required>

        <label>Student ID</label>
        <input 
            type="text" 
            name="student_id" 
            value="<?php echo htmlspecialchars($user['student_id']); ?>" 
            required>

        <label>Department</label>
        <input 
            type="text" 
            name="department" 
            value="<?php echo htmlspecialchars($user['department']); ?>" 
            required>

        <label>Username</label>
        <input 
            type="text" 
            name="username" 
            value="<?php echo htmlspecialchars($user['username']); ?>" 
            required>

        <label>Role</label>
        <select name="role">
            <?php 
            $roles = ['ADMIN', 'STUDENT', 'FACULTY', 'COUNSELLOR', 'ORGANIZER', 'STAFF', 'TRAINER', 'AUTHORITY'];
            foreach($roles as $r) {
                $selected = ($user['role'] == $r) ? 'selected' : '';
                echo "<option value='$r' $selected>$r</option>";
            }
            ?>
        </select>

        <button type="submit" name="update">
            Update User
        </button>

        <p class="footer-text">
            "A Safer, Healthier, Stronger BUP"
        </p>

    </form>
</div>

</body>
</html>