<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require "../backend/db.php";

$query = "SELECT student_id, registration_no, name, date_of_birth, department, degree, cgpa, passing_year, certificate_no, photo FROM students ORDER BY id ASC";
$result = $conn->query($query);

if (!$result) {
    header("Location: dashboard.php?export=error");
    exit;
}

$rows = [];
while ($row = $result->fetch_assoc()) {
    $photo = trim((string) ($row["photo"] ?? ""));
    $photo = str_replace("\\", "/", ltrim($photo, "/"));
    if ($photo !== "") {
        if (strpos($photo, "uploads/students/") === 0) {
            // valid relative path
        } elseif (strpos($photo, "/") === false && preg_match("/\\.(jpe?g|png|webp)$/i", $photo)) {
            $photo = "uploads/students/" . $photo;
        } else {
            $photo = "";
        }
    }

    $rows[] = [
        "studentId" => (string) $row["student_id"],
        "regNo" => (string) $row["registration_no"],
        "name" => (string) $row["name"],
        "dateOfBirth" => (string) ($row["date_of_birth"] ?? ""),
        "department" => (string) $row["department"],
        "degree" => (string) $row["degree"],
        "cgpa" => (string) $row["cgpa"],
        "year" => (string) $row["passing_year"],
        "certNo" => (string) $row["certificate_no"],
        "photo" => $photo
    ];
}

$dataDir = realpath(__DIR__ . "/../data");
if ($dataDir === false) {
    $dataDir = __DIR__ . "/../data";
    if (!is_dir($dataDir) && !mkdir($dataDir, 0777, true)) {
        header("Location: dashboard.php?export=error");
        exit;
    }
}

$filePath = rtrim($dataDir, "/\\") . DIRECTORY_SEPARATOR . "students.json";
$json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

if ($json === false || file_put_contents($filePath, $json) === false) {
    header("Location: dashboard.php?export=error");
    exit;
}

header("Location: dashboard.php?export=ok&count=" . count($rows));
exit;
