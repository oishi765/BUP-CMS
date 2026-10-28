<?php
session_start();
require_once "../config/db.php";

if(isset($_POST['login']))
{
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    $query = "SELECT * FROM users WHERE username='$username'";
    $result = mysqli_query($conn, $query);

    if(mysqli_num_rows($result) == 1)
    {
        $user = mysqli_fetch_assoc($result);

        if($password == $user['password_hash'])
        {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            switch($user['role'])
            {
                case 'ADMIN':
                    header("Location: ../admin/admin.php");
                    exit();

                case 'STUDENT':
                    header("Location: ../student/student_dashboard.php");
                    exit();

                case 'FACULTY':
                    $user_id = $user['user_id'];
                    $f_query = "SELECT faculty_id, faculty_no, department, designation FROM faculty WHERE user_id = '$user_id'";
                    $f_result = mysqli_query($conn, $f_query);
                    if($f_row = mysqli_fetch_assoc($f_result)) {
                        $_SESSION['faculty_id'] = $f_row['faculty_id'];
                        $_SESSION['faculty_no'] = $f_row['faculty_no'];
                        $_SESSION['department'] = $f_row['department'];
                        $_SESSION['designation'] = $f_row['designation'];
                    }
                    $_SESSION['faculty_user'] = $user['username'];
                    $_SESSION['faculty_name'] = $user['full_name'];
                    header("Location: ../faculty/faculty_dashboard.php");
                    exit();

                case 'COUNSELLOR':
                    // Fetch counsellor_id from counsellors table using user_id
                    $user_id = $user['user_id'];
                    $c_query = "SELECT counsellor_id FROM counsellors WHERE user_id = '$user_id'";
                    $c_result = mysqli_query($conn, $c_query);
                    if($c_row = mysqli_fetch_assoc($c_result)) {
                        $_SESSION['counsellor_id'] = $c_row['counsellor_id'];
                    }
                    header("Location: ../counsellor/counsellor_dashboard.php");
                    exit();

                case 'STAFF':
                    $_SESSION['staff_id'] = $user['user_id'];
                    $_SESSION['staff_name'] = $user['full_name'];
                    header("Location: ../staff/staff_dashboard.php");
                    exit();

                case 'TRAINER':
                    $error = "Trainer dashboard is not available yet.";
                    break;

                case 'ORGANIZER':
                    $error = "Organizer dashboard is not available yet.";
                    break;

                case 'AUTHORITY':
                    $error = "Authority dashboard is not available yet.";
                    break;

                default:
                    $error = "Invalid user role.";
            }
        } 
        else
        {
            $error = "Invalid Password!";
        }
    }
    else
    {
        $error = "User Not Found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BUP-CMS Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background: #f2f7f4;
        }

        .page-wrapper {
            width: 100vw;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .bg-leaf-1 {
            position: absolute;
            top: -60px;
            left: -60px;
            width: 320px;
            height: 320px;
            opacity: 0.15;
            pointer-events: none;
            transform: rotate(-15deg);
        }

        .bg-leaf-2 {
            position: absolute;
            bottom: -80px;
            right: -80px;
            width: 380px;
            height: 380px;
            opacity: 0.12;
            pointer-events: none;
            transform: rotate(30deg);
        }

        .login-box {
            width: 380px;
            background: #ffffff;
            padding: 35px 30px;
            text-align: center;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(9, 71, 53, 0.08);
            border: 1px solid #e0f0e6;
            position: relative;
            z-index: 10;
        }

        .brand-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            margin-bottom: 25px;
        }

        .logo {
            width: 85px;
            height: auto;
            object-fit: contain;
            margin-bottom: 5px;
        }

        .brand-header h2 {
            font-size: 20px;
            color: #081c15;
            font-weight: 700;
        }

        .brand-header p {
            font-size: 12px;
            color: #52b788;
        }

        .input-group {
            position: relative;
            margin-bottom: 16px;
            text-align: left;
        }

        .input-group i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #2d6a4f;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 14px 12px 40px;
            border: 1px solid #e5ece7;
            border-radius: 10px;
            background: #f8faf9;
            font-size: 13px;
            color: #1b4332;
            outline: none;
            transition: all 0.3s ease;
        }

        input:focus {
            background: #ffffff;
            border-color: #2d6a4f;
            box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.1);
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #2d6a4f, #1b4332);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(27, 67, 50, 0.2);
            margin-top: 5px;
        }

        button:hover {
            background: linear-gradient(135deg, #1b4d32, #081c15);
            transform: translateY(-1px);
        }

        .error {
            background: #ffebe9;
            color: #c0392b;
            padding: 10px;
            border-radius: 8px;
            font-size: 12px;
            margin-bottom: 18px;
            border-left: 4px solid #e74c3c;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .footer-quote {
            margin-top: 20px;
            font-size: 11px;
            color: #52b788;
            font-style: italic;
        }
    </style>
</head>

<body>

    <div class="page-wrapper">

        <svg class="bg-leaf-1" viewBox="0 0 100 100" fill="#2d6a4f">
            <path d="M50,0 C20,20 10,50 10,80 C30,90 60,90 80,70 C100,50 80,20 50,0 Z M50,15 C65,30 70,50 60,70 C45,80 30,70 25,55 C20,40 35,25 50,15 Z"/>
        </svg>

        <svg class="bg-leaf-2" viewBox="0 0 100 100" fill="#1b4332">
            <path d="M50,0 C20,30 10,60 20,90 C40,100 70,90 90,60 C100,30 80,0 50,0 Z"/>
        </svg>

        <div class="login-box">

            <div class="brand-header">
                <img src="/BUP-CMS/images/logo.png" alt="BUP Logo" class="logo">
                <h2>BUP-CMS Portal</h2>
                <p>Counselling & Wellness Management System</p>
            </div>

            <?php
            if(isset($error))
            {
                echo "<div class='error'><i class='fas fa-circle-exclamation'></i> $error</div>";
            }
            ?>

            <form method="POST">

                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input 
                        type="text" 
                        name="username" 
                        placeholder="Username" 
                        required>
                </div>

                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="Password" 
                        required>
                </div>

                <button type="submit" name="login">
                    Login <i class="fas fa-arrow-right" style="margin-left: 6px; font-size: 12px;"></i>
                </button>

            </form>

            <div class="footer-quote">
                🌱 "A healthier mind leads to a brighter tomorrow."
            </div>

        </div>

    </div>

</body>
</html>