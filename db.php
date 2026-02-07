<?php
$host = "localhost";
$user = "root";       // change if needed
$pass = "";           // change if needed
$db   = "uap_verification";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed");
}

// Best-effort schema update for student photo and date-of-birth support.
$studentsTable = $conn->query("SHOW TABLES LIKE 'students'");
if ($studentsTable && $studentsTable->num_rows === 1) {
    $photoColumn = $conn->query("SHOW COLUMNS FROM students LIKE 'photo'");
    if ($photoColumn && $photoColumn->num_rows === 0) {
        $conn->query("ALTER TABLE students ADD COLUMN photo VARCHAR(255) DEFAULT NULL");
    }

    $dobColumn = $conn->query("SHOW COLUMNS FROM students LIKE 'date_of_birth'");
    if ($dobColumn && $dobColumn->num_rows === 0) {
        $conn->query("ALTER TABLE students ADD COLUMN date_of_birth VARCHAR(20) DEFAULT NULL");
    }
}
?>
