<?php

require_once "../config/db.php";

$id=$_GET['id'];

mysqli_query(
$conn,
"DELETE FROM workshops
WHERE workshop_id='$id'"
);

header("Location:workshops.php");

?>