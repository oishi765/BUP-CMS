<?php

require_once "../config/db.php";

$id = $_GET['id'];

mysqli_query(
$conn,
"DELETE FROM counsellors
WHERE counsellor_id='$id'"
);

header("Location:counsellors.php");