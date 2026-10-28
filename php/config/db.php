<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "oishi123",
    "bup_counselling_management"
);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}

//echo "Database Connected Successfully";
?>