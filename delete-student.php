<?php
session_start();
if (!isset($_SESSION["admin"])) {
    exit;
}

require "../backend/db.php";
require "photo-utils.php";

$id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
if ($id <= 0) {
    header("Location: dashboard.php");
    exit;
}

$photoStmt = $conn->prepare("SELECT photo FROM students WHERE id = ? LIMIT 1");
if ($photoStmt) {
    $photoStmt->bind_param("i", $id);
    $photoStmt->execute();
    $photoResult = $photoStmt->get_result();
    if ($photoResult && ($photoRow = $photoResult->fetch_assoc())) {
        delete_student_photo_file($photoRow["photo"] ?? "");
    }
}

$deleteStmt = $conn->prepare("DELETE FROM students WHERE id = ?");
if ($deleteStmt) {
    $deleteStmt->bind_param("i", $id);
    $deleteStmt->execute();
}

header("Location: export_students_json.php");
exit;
