<?php
require __DIR__ . "/db.php";

$fpdfPath = __DIR__ . "/fpdf/fpdf.php";
if (!is_file($fpdfPath)) {
    http_response_code(500);
    echo "PDF library missing. Install FPDF under backend/fpdf/ to enable this feature.";
    exit;
}
require $fpdfPath;

$id = trim((string) ($_GET["id"] ?? ""));
if ($id === "") {
    http_response_code(400);
    echo "Missing student id.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ? LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    echo "Failed to prepare query.";
    exit;
}

$stmt->bind_param("s", $id);
if (!$stmt->execute()) {
    http_response_code(500);
    echo "Query execution failed.";
    exit;
}

$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
    http_response_code(404);
    echo "Student record not found.";
    exit;
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont("Arial", "B", 16);

$logoPath = realpath(__DIR__ . "/../assets/uap.png");
if ($logoPath) {
    $pdf->Image($logoPath, 80, 10, 50);
}
$pdf->Ln(40);

$pdf->Cell(0, 10, "OFFICIAL DEGREE VERIFICATION", 0, 1, "C");
$pdf->Ln(10);

$pdf->SetFont("Arial", "", 12);
foreach ($row as $k => $v) {
    $pdf->Cell(50, 8, ucwords(str_replace("_", " ", (string) $k)), 1);
    $pdf->Cell(0, 8, (string) $v, 1, 1);
}

$pdf->Output("I", "UAP_Verification.pdf");
