<?php
session_start();
require_once "../config/db.php";

// Check if counsellor is logged in
if (!isset($_SESSION['counsellor_id'])) {
    header("Location: ../authorization/login.php");
    exit();
}

$counsellor_id = $_SESSION['counsellor_id'];

if (isset($_POST['quick_note'])) {
    $note_content = mysqli_real_escape_string($conn, $_POST['quick_note']);
    
    if (!empty($note_content)) {
        // Insert into session_notes table
        $query = "INSERT INTO session_notes (counsellor_id, note_content, created_at) VALUES ('$counsellor_id', '$note_content', NOW())";
        $run = mysqli_query($conn, $query);
        
        if (!$run) {
            // Create table if it doesn't exist
            $create_table = "CREATE TABLE IF NOT EXISTS session_notes (
                note_id INT AUTO_INCREMENT PRIMARY KEY,
                counsellor_id INT,
                student_id INT DEFAULT NULL,
                note_content TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            mysqli_query($conn, $create_table);
            mysqli_query($conn, $query);
        }
    }
}

header("Location: counsellor_dashboard.php");
exit();
?>