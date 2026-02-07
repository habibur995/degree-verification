<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require "../backend/db.php";
require "photo-utils.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $studentId = trim($_POST["student_id"] ?? "");
    $regNo = trim($_POST["reg_no"] ?? "");
    $name = trim($_POST["name"] ?? "");
    $dateOfBirth = trim($_POST["date_of_birth"] ?? "");
    $department = trim($_POST["dept"] ?? "");
    $degree = trim($_POST["degree"] ?? "");
    $cgpa = trim($_POST["cgpa"] ?? "");
    $year = trim($_POST["year"] ?? "");
    $certNo = trim($_POST["cert"] ?? "");

    if (
        $studentId === "" || $regNo === "" || $name === "" || $dateOfBirth === "" || $department === "" ||
        $degree === "" || $cgpa === "" || $year === "" || $certNo === ""
    ) {
        $error = "All fields except photo are required.";
    } else {
        $photoPath = "";
        if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE) {
            $uploadError = "";
            $uploaded = upload_student_photo($_FILES["photo"], $uploadError);
            if ($uploaded === null) {
                $error = $uploadError;
            } else {
                $photoPath = $uploaded;
            }
        }

        if ($error === "") {
            $stmt = $conn->prepare(
                "INSERT INTO students
                (student_id, registration_no, name, date_of_birth, department, degree, cgpa, passing_year, certificate_no, photo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                $error = "Could not prepare insert query.";
                if ($photoPath !== "") {
                    delete_student_photo_file($photoPath);
                }
            } else {
                $stmt->bind_param(
                    "ssssssssss",
                    $studentId,
                    $regNo,
                    $name,
                    $dateOfBirth,
                    $department,
                    $degree,
                    $cgpa,
                    $year,
                    $certNo,
                    $photoPath
                );

                if ($stmt->execute()) {
                    header("Location: export_students_json.php");
                    exit;
                }

                $error = "Failed to add student. Student ID may already exist.";
                if ($photoPath !== "") {
                    delete_student_photo_file($photoPath);
                }
            }
        }
    }
}
?>
<form method="POST" enctype="multipart/form-data">
<h3>Add Student</h3>
<?php if ($error !== ""): ?>
<p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
<?php endif; ?>
<input name="student_id" placeholder="Student ID" required><br>
<input name="reg_no" placeholder="Reg No" required><br>
<input name="name" placeholder="Name" required><br>
<input name="date_of_birth" placeholder="Date of Birth (DD.MM.YYYY)" required><br>
<input name="dept" placeholder="Department" required><br>
<input name="degree" placeholder="Degree" required><br>
<input name="cgpa" placeholder="CGPA" required><br>
<input name="year" placeholder="Passing Year" required><br>
<input name="cert" placeholder="Certificate No" required><br>
<label>Student Photo (optional):</label><br>
<input name="photo" type="file" accept=".jpg,.jpeg,.png,.webp,image/*"><br>
<small>Allowed: JPG/PNG/WEBP, max 2MB</small><br><br>
<button>Save</button>
</form>
