<?php

session_start();

require_once "../config/db.php";

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../authorization/login.php");
    exit();
}

// Fetch counsellors
$query = "SELECT * FROM counsellors";
$result = mysqli_query($conn, $query);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Counsellors | BUP-CMS</title>

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #f5f8f6;
            color: #16382a;
        }

        /* =========================
           LAYOUT
        ========================= */

        .page-wrapper {
            display: flex;
            min-height: 100vh;
        }


        /* =========================
           SIDEBAR
        ========================= */

        .sidebar {
            width: 235px;
            min-height: 100vh;
            background: #06271c;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
        }

        .sidebar-logo {
            padding: 25px 18px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #2d6a4f;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d8f3dc;
            font-size: 18px;
        }

        .brand-text h3 {
            font-size: 15px;
            color: #ffffff;
        }

        .brand-text p {
            font-size: 9px;
            color: #74c69d;
            margin-top: 2px;
        }

        .nav {
            padding: 18px 0;
            flex: 1;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 13px 20px;
            color: #d6e8df;
            text-decoration: none;
            font-size: 13px;
            transition: 0.25s;
        }

        .nav a i {
            width: 18px;
            text-align: center;
            font-size: 14px;
        }

        .nav a:hover {
            background: #123f30;
            color: white;
        }

        .nav a.active {
            background: #194b38;
            color: white;
            border-left: 4px solid #52b788;
            padding-left: 16px;
        }

        .nav a.logout {
            color: #ff8d8d;
        }

        .nav a.logout:hover {
            background: rgba(239,68,68,0.12);
        }

        .sidebar-footer {
            padding: 20px 17px;
            border-top: 1px solid rgba(255,255,255,0.06);
        }

        .sidebar-footer strong {
            font-size: 9px;
            color: #74c69d;
            display: block;
        }

        .sidebar-footer span {
            font-size: 8px;
            color: #5f8f7c;
        }


        /* =========================
           MAIN CONTENT
        ========================= */

        .main {
            margin-left: 235px;
            width: calc(100% - 235px);
            min-height: 100vh;
        }


        /* =========================
           TOP BAR
        ========================= */

        .topbar {
            height: 72px;
            background: rgba(255,255,255,0.92);
            border-bottom: 1px solid #e5ece8;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 35px;
        }

        .topbar-title {
            font-size: 13px;
            color: #698176;
        }

        .user-box {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            background: #2d6a4f;
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: 700;
            font-size: 12px;
        }

        .user-info strong {
            display: block;
            font-size: 12px;
            color: #18392c;
        }

        .user-info span {
            font-size: 10px;
            color: #65a987;
        }


        /* =========================
           CONTENT
        ========================= */

        .content {
            padding: 32px 35px 45px;
        }

        .page-heading {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 28px;
        }

        .heading-left h1 {
            font-size: 27px;
            color: #092c20;
            margin-bottom: 7px;
        }

        .heading-left p {
            color: #71867d;
            font-size: 13px;
        }

        .heading-icon {
            color: #2d6a4f;
            margin-right: 7px;
        }


        /* =========================
           SEARCH
        ========================= */

        .search-box {
            width: 280px;
            position: relative;
        }

        .search-box i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #78a18f;
            font-size: 13px;
        }

        .search-box input {
            width: 100%;
            border: 1px solid #dfe9e4;
            background: white;
            border-radius: 11px;
            padding: 12px 15px 12px 38px;
            outline: none;
            font-size: 12px;
            color: #18392c;
        }

        .search-box input:focus {
            border-color: #52b788;
            box-shadow: 0 0 0 3px rgba(82,183,136,0.10);
        }


        /* =========================
           STAT CARDS
        ========================= */

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 27px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e3ece7;
            border-radius: 15px;
            padding: 19px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: #e4f4eb;
            color: #2d6a4f;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-card strong {
            display: block;
            font-size: 19px;
            color: #16382a;
        }

        .stat-card span {
            font-size: 10px;
            color: #799087;
        }


        /* =========================
           COUNSELLOR GRID
        ========================= */

        .counsellor-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .counsellor-card {
            background: white;
            border: 1px solid #e2ebe7;
            border-radius: 18px;
            padding: 23px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .counsellor-card::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(
                90deg,
                #1b4332,
                #52b788
            );
        }

        .counsellor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(20,70,50,0.10);
            border-color: #cce5d8;
        }


        /* PROFILE */

        .profile-top {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 20px;
        }

        .profile-avatar {
            width: 62px;
            height: 62px;
            border-radius: 17px;
            background: linear-gradient(
                135deg,
                #d8f3dc,
                #b7e4c7
            );
            display: flex;
            justify-content: center;
            align-items: center;
            color: #1b4332;
            font-size: 24px;
        }

        .profile-name h2 {
            font-size: 16px;
            color: #092c20;
            margin-bottom: 5px;
        }

        .profile-name p {
            font-size: 11px;
            color: #40916c;
            font-weight: 600;
        }


        /* INFO */

        .info-list {
            border-top: 1px solid #edf2ef;
            padding-top: 16px;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 11px;
            font-size: 11px;
            color: #6c7f76;
        }

        .info-row i {
            width: 18px;
            color: #40916c;
        }


        /* STATUS */

        .status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 16px;
        }

        .available {
            background: #e0f6e8;
            color: #24734c;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 700;
        }

        .available i {
            font-size: 6px;
            margin-right: 4px;
        }


        /* BOOK BUTTON */

        .book-btn {
            text-decoration: none;
            background: #1b4332;
            color: white;
            padding: 9px 13px;
            border-radius: 8px;
            font-size: 10px;
            font-weight: 600;
            transition: 0.25s;
        }

        .book-btn:hover {
            background: #2d6a4f;
        }


        /* EMPTY */

        .empty {
            background: white;
            border: 1px solid #e4ece8;
            border-radius: 17px;
            padding: 65px 20px;
            text-align: center;
        }

        .empty i {
            font-size: 45px;
            color: #9bc9b1;
            margin-bottom: 15px;
        }

        .empty h2 {
            color: #274d3d;
            font-size: 19px;
            margin-bottom: 7px;
        }

        .empty p {
            color: #81928b;
            font-size: 12px;
        }


        /* =========================
           RESPONSIVE
        ========================= */

        @media (max-width: 1000px) {

            .counsellor-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .stats {
                grid-template-columns: repeat(3, 1fr);
            }

        }

        @media (max-width: 750px) {

            .sidebar {
                width: 70px;
            }

            .brand-text,
            .nav span,
            .sidebar-footer {
                display: none;
            }

            .brand {
                justify-content: center;
            }

            .nav a {
                justify-content: center;
                padding: 15px;
            }

            .main {
                margin-left: 70px;
                width: calc(100% - 70px);
            }

            .content {
                padding: 25px 18px;
            }

            .page-heading {
                display: block;
            }

            .search-box {
                width: 100%;
                margin-top: 18px;
            }

            .counsellor-grid {
                grid-template-columns: 1fr;
            }

            .stats {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>


<body>


<div class="page-wrapper">


    <!-- =========================
         SIDEBAR
    ========================== -->

    <aside class="sidebar">

        <div class="sidebar-logo">

            <div class="brand">

                <div class="brand-icon">
                    <i class="fas fa-heart-pulse"></i>
                </div>

                <div class="brand-text">
                    <h3> Counselling & Wellness</h3>
                    <p>BUP-CMS</p>
                </div>

            </div>

        </div>


        <nav class="nav">

            <a href="student_dashboard.php">
                <i class="fas fa-house"></i>
                <span>Dashboard</span>
            </a>

            <a href="my_profile.php">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>

            <a href="counsellors.php" class="active">
                <i class="fas fa-user-doctor"></i>
                <span>Counsellors</span>
            </a>

            <a href="myappointment.php">
                <i class="fas fa-calendar-plus"></i>
                <span>Appointment</span>
            </a>

            <a href="myworkshop.php">
                <i class="fas fa-graduation-cap"></i>
                <span>Workshops</span>
            </a>

            <a href="my_enrollments.php">
                <i class="fas fa-book-open"></i>
                <span>My Enrollments</span>
            </a>

            <a href="counselling_history.php">
                <i class="fas fa-clock-rotate-left"></i>
                <span>Counselling History</span>
            </a>

            <a href="my_feedback.php">
                <i class="fas fa-comment-dots"></i>
                <span>Feedback</span>
            </a>

            <a href="../authorization/logout.php" class="logout">
                <i class="fas fa-right-from-bracket"></i>
                <span>Logout</span>
            </a>

        </nav>


        <div class="sidebar-footer">

            <strong>Bangladesh University of Professionals</strong>

            <span>
                "A Safer, Healthier, Stronger BUP"
            </span>

        </div>

    </aside>



    <!-- =========================
         MAIN
    ========================== -->

    <main class="main">


        <!-- TOP BAR -->

        <div class="topbar">

            <div class="topbar-title">
                Student Portal / Counsellors
            </div>

            <div class="user-box">

                <div class="user-avatar">
                    <?php
                    echo strtoupper(
                        substr($_SESSION['full_name'] ?? 'EV', 0, 2)
                    );
                    ?>
                </div>

                <div class="user-info">

                    <strong>
                        <?php
                        echo htmlspecialchars(
                            $_SESSION['full_name'] ?? 'Student'
                        );
                        ?>
                    </strong>

                    <span>Student</span>

                </div>

            </div>

        </div>



        <!-- CONTENT -->

        <section class="content">


            <!-- PAGE HEADING -->

            <div class="page-heading">

                <div class="heading-left">

                    <h1>
                        <i class="fas fa-user-doctor heading-icon"></i>
                        Find Your Counsellor
                    </h1>

                    <p>
                        Connect with our professional counsellors and get the support you need.
                    </p>

                </div>


                <div class="search-box">

                    <i class="fas fa-search"></i>

                    <input
                        type="text"
                        id="searchCounsellor"
                        placeholder="Search counsellor..."
                    >

                </div>

            </div>



            <!-- STATS -->

            <?php

            $total = 0;

            if ($result) {
                $total = mysqli_num_rows($result);
            }

            ?>

            <div class="stats">

                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fas fa-user-doctor"></i>
                    </div>

                    <div>
                        <strong>
                            <?php echo $total; ?>
                        </strong>

                        <span>
                            Total Counsellors
                        </span>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fas fa-circle-check"></i>
                    </div>

                    <div>
                        <strong>
                            <?php echo $total; ?>
                        </strong>

                        <span>
                            Available
                        </span>
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>

                    <div>
                        <strong>
                            24/7
                        </strong>

                        <span>
                            Support Platform
                        </span>
                    </div>

                </div>

            </div>



            <!-- COUNSELLORS -->

            <?php if ($result && mysqli_num_rows($result) > 0): ?>

                <div class="counsellor-grid" id="counsellorGrid">


                    <?php while ($counsellor = mysqli_fetch_assoc($result)): ?>


                        <div class="counsellor-card"
                             data-name="<?php echo strtolower(
                                 htmlspecialchars(
                                     $counsellor['full_name'] ?? ''
                                 )
                             ); ?>">


                            <div class="profile-top">


                                <div class="profile-avatar">
                                    <i class="fas fa-user-doctor"></i>
                                </div>


                                <div class="profile-name">

                                    <h2>
                                        <?php
                                        echo htmlspecialchars(
                                            $counsellor['full_name']
                                            ?? 'Counsellor'
                                        );
                                        ?>
                                    </h2>

                                    <p>
                                        <?php
                                        echo htmlspecialchars(
                                            $counsellor['specialization']
                                            ?? 'Mental Health Counselling'
                                        );
                                        ?>
                                    </p>

                                </div>


                            </div>



                            <div class="info-list">


                                <div class="info-row">

                                    <i class="fas fa-envelope"></i>

                                    <span>
                                        <?php
                                        echo htmlspecialchars(
                                            $counsellor['email']
                                            ?? 'Email not available'
                                        );
                                        ?>
                                    </span>

                                </div>


                                <div class="info-row">

                                    <i class="fas fa-phone"></i>

                                    <span>
                                        <?php
                                        echo htmlspecialchars(
                                            $counsellor['phone']
                                            ?? 'Phone not available'
                                        );
                                        ?>
                                    </span>

                                </div>


                                <div class="info-row">

                                    <i class="fas fa-shield-heart"></i>

                                    <span>
                                        Professional Counselling Support
                                    </span>

                                </div>


                            </div>



                            <div class="status-row">


                                <span class="available">

                                    <i class="fas fa-circle"></i>

                                    Available

                                </span>


                                <a
                                    href="book_appointment.php?counsellor_id=<?php echo $counsellor['counsellor_id']; ?>"
                                    class="book-btn"
                                >

                                    <i class="fas fa-calendar-plus"></i>

                                    Book Appointment

                                </a>


                            </div>


                        </div>


                    <?php endwhile; ?>


                </div>


            <?php else: ?>


                <div class="empty">

                    <i class="fas fa-user-slash"></i>

                    <h2>
                        No Counsellors Available
                    </h2>

                    <p>
                        There are currently no counsellors registered in the system.
                    </p>

                </div>


            <?php endif; ?>


        </section>

    </main>


</div>



<script>

    /* =========================
       COUNSELLOR SEARCH
    ========================= */

    const searchInput =
        document.getElementById("searchCounsellor");

    const cards =
        document.querySelectorAll(".counsellor-card");


    searchInput.addEventListener("keyup", function () {

        const searchValue =
            this.value.toLowerCase().trim();


        cards.forEach(function (card) {

            const name =
                card.getAttribute("data-name");


            if (name.includes(searchValue)) {

                card.style.display = "block";

            } else {

                card.style.display = "none";

            }

        });

    });

</script>


</body>
</html>