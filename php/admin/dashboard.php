<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if(!isset($_SESSION['user_id']))
{
    die("user_id session not found");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>

<h1>Dashboard Working</h1>

<p>User ID: <?php echo $_SESSION['user_id']; ?></p>
<p>Username: <?php echo $_SESSION['username']; ?></p>
<p>Role: <?php echo $_SESSION['role']; ?></p>
<p>Name: <?php echo $_SESSION['full_name']; ?></p>

</body>
</html>