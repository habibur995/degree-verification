<?php

function uap_project_root() {
    $root = realpath(__DIR__ . "/..");
    return $root !== false ? $root : dirname(__DIR__);
}

function student_photo_upload_dir() {
    return uap_project_root() . DIRECTORY_SEPARATOR . "uploads" . DIRECTORY_SEPARATOR . "students";
}

function ensure_student_photo_dir() {
    $dir = student_photo_upload_dir();
    if (is_dir($dir)) {
        return true;
    }

    return mkdir($dir, 0777, true);
}

function sanitize_student_photo_path($path) {
    $path = trim((string) $path);
    if ($path === "") {
        return "";
    }

    $path = str_replace("\\", "/", $path);
    $path = ltrim($path, "/");

    if (strpos($path, "uploads/students/") !== 0) {
        return "";
    }

    return $path;
}

function delete_student_photo_file($relativePath) {
    $relativePath = sanitize_student_photo_path($relativePath);
    if ($relativePath === "") {
        return;
    }

    $fullPath = uap_project_root() . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativePath);
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}

function upload_student_photo($file, &$errorMessage) {
    if (!isset($file["tmp_name"]) || !isset($file["error"])) {
        $errorMessage = "Invalid file upload.";
        return null;
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $errorMessage = "Photo upload failed.";
        return null;
    }

    if (!isset($file["size"]) || (int) $file["size"] > 2 * 1024 * 1024) {
        $errorMessage = "Photo must be less than 2MB.";
        return null;
    }

    $imageInfo = @getimagesize($file["tmp_name"]);
    if ($imageInfo === false || !isset($imageInfo["mime"])) {
        $errorMessage = "Only image files are allowed.";
        return null;
    }

    $mimeToExtension = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/webp" => "webp"
    ];

    if (!isset($mimeToExtension[$imageInfo["mime"]])) {
        $errorMessage = "Allowed formats: JPG, PNG, WEBP.";
        return null;
    }

    if (!ensure_student_photo_dir()) {
        $errorMessage = "Could not create upload directory.";
        return null;
    }

    $extension = $mimeToExtension[$imageInfo["mime"]];
    $unique = str_replace(".", "", uniqid("student_", true));
    $fileName = $unique . "." . $extension;

    $targetAbsolute = student_photo_upload_dir() . DIRECTORY_SEPARATOR . $fileName;
    if (!move_uploaded_file($file["tmp_name"], $targetAbsolute)) {
        $errorMessage = "Failed to store uploaded photo.";
        return null;
    }

    return "uploads/students/" . $fileName;
}

