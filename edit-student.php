<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}

require "../backend/db.php";
require "photo-utils.php";

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
if ($id <= 0) {
    header("Location: dashboard.php");
    exit;
}

$studentStmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
if (!$studentStmt) {
    header("Location: dashboard.php");
    exit;
}
$studentStmt->bind_param("i", $id);
$studentStmt->execute();
$studentResult = $studentStmt->get_result();
$row = $studentResult ? $studentResult->fetch_assoc() : null;

if (!$row) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $dateOfBirth = trim($_POST["date_of_birth"] ?? "");
    $cgpa = trim($_POST["cgpa"] ?? "");
    $currentPhoto = sanitize_student_photo_path($row["photo"] ?? "");
    $nextPhoto = $currentPhoto;

    if ($name === "" || $dateOfBirth === "" || $cgpa === "") {
        $error = "Name, Date of Birth and CGPA are required.";
    }

    if ($error === "" && isset($_POST["remove_photo"])) {
        if ($currentPhoto !== "") {
            delete_student_photo_file($currentPhoto);
        }
        $nextPhoto = "";
    }

    if ($error === "" && isset($_FILES["photo"]) && $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE) {
        $uploadError = "";
        $uploaded = upload_student_photo($_FILES["photo"], $uploadError);
        if ($uploaded === null) {
            $error = $uploadError;
        } else {
            if ($currentPhoto !== "" && $currentPhoto !== $uploaded) {
                delete_student_photo_file($currentPhoto);
            }
            $nextPhoto = $uploaded;
        }
    }

    if ($error === "") {
        $updateStmt = $conn->prepare("UPDATE students SET name = ?, date_of_birth = ?, cgpa = ?, photo = ? WHERE id = ?");
        if (!$updateStmt) {
            $error = "Could not prepare update query.";
        } else {
            $updateStmt->bind_param("ssssi", $name, $dateOfBirth, $cgpa, $nextPhoto, $id);
            if ($updateStmt->execute()) {
                header("Location: export_students_json.php");
                exit;
            }
            $error = "Failed to update student.";
        }
    }

    $row["name"] = $name;
    $row["date_of_birth"] = $dateOfBirth;
    $row["cgpa"] = $cgpa;
    $row["photo"] = $nextPhoto;
}

$currentPhotoPath = sanitize_student_photo_path($row["photo"] ?? "");
?>
<form method="POST" enctype="multipart/form-data">
<h3>Edit Student</h3>
<?php if ($error !== ""): ?>
<p style="color:red;"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
<?php endif; ?>
<input name="name" value="<?= htmlspecialchars($row["name"], ENT_QUOTES, "UTF-8") ?>"><br>
<input name="date_of_birth" value="<?= htmlspecialchars($row["date_of_birth"] ?? "", ENT_QUOTES, "UTF-8") ?>" placeholder="Date of Birth (DD.MM.YYYY)"><br>
<input name="cgpa" value="<?= htmlspecialchars($row["cgpa"], ENT_QUOTES, "UTF-8") ?>"><br><br>
<?php if ($currentPhotoPath !== ""): ?>
<div>
<p>Current Photo:</p>
<img src="../<?= htmlspecialchars($currentPhotoPath, ENT_QUOTES, "UTF-8") ?>" alt="Student photo" style="width:120px;height:120px;object-fit:cover;border:1px solid #ccc;">
</div>
<label><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label><br><br>
<?php endif; ?>
<label>Upload New Photo (optional):</label><br>
<input name="photo" type="file" accept=".jpg,.jpeg,.png,.webp,image/*"><br>
<small>Allowed: JPG/PNG/WEBP, max 2MB</small><br><br>
<button>Update</button>
</form>
