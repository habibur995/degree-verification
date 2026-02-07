<?php
header("Content-Type: application/json");
require "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$studentId = "";

if (is_array($data) && isset($data["studentId"])) {
    $studentId = trim((string) $data["studentId"]);
} elseif (isset($_POST["studentId"])) {
    $studentId = trim((string) $_POST["studentId"]);
}

if ($studentId === "") {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid request"]);
    exit;
}

$hasPhotoColumn = false;
$photoColumnResult = $conn->query("SHOW COLUMNS FROM students LIKE 'photo'");
if ($photoColumnResult && $photoColumnResult->num_rows === 1) {
    $hasPhotoColumn = true;
}

$selectFields = "student_id, registration_no, name, date_of_birth, department, degree, cgpa, passing_year, certificate_no";
if ($hasPhotoColumn) {
    $selectFields .= ", photo";
}

$stmt = $conn->prepare(
    "SELECT $selectFields
     FROM students
     WHERE student_id = ? OR registration_no = ?
     LIMIT 1"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to prepare query"]);
    exit;
}

$stmt->bind_param("ss", $studentId, $studentId);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Query execution failed"]);
    exit;
}

$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(["status" => "not_found", "message" => "Student record not found"]);
    exit;
}

$photoValue = "";
if ($hasPhotoColumn) {
    $stmt->bind_result(
        $studentIdValue,
        $regNoValue,
        $nameValue,
        $dateOfBirthValue,
        $departmentValue,
        $degreeValue,
        $cgpaValue,
        $yearValue,
        $certNoValue,
        $photoValue
    );
} else {
    $stmt->bind_result(
        $studentIdValue,
        $regNoValue,
        $nameValue,
        $dateOfBirthValue,
        $departmentValue,
        $degreeValue,
        $cgpaValue,
        $yearValue,
        $certNoValue
    );
}
$stmt->fetch();

$photoValue = trim((string) $photoValue);
$photoValue = str_replace("\\", "/", ltrim($photoValue, "/"));
if ($photoValue !== "") {
    if (strpos($photoValue, "uploads/students/") === 0) {
        // valid relative path
    } elseif (strpos($photoValue, "/") === false && preg_match("/\\.(jpe?g|png|webp)$/i", $photoValue)) {
        $photoValue = "uploads/students/" . $photoValue;
    } else {
        $photoValue = "";
    }
}

echo json_encode([
    "status" => "success",
    "data" => [
        "studentId" => $studentIdValue,
        "regNo" => $regNoValue,
        "name" => $nameValue,
        "dateOfBirth" => $dateOfBirthValue,
        "department" => $departmentValue,
        "degree" => $degreeValue,
        "cgpa" => $cgpaValue,
        "year" => $yearValue,
        "certNo" => $certNoValue,
        "photo" => $photoValue
    ]
]);
