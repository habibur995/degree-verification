<?php
require __DIR__ . "/backend/db.php";

$student = null;
$error = "";
$searchValue = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["search"])) {
    $searchValue = trim((string) ($_POST["student_id"] ?? ""));

    if ($searchValue === "") {
        $error = "Please enter a Student ID / Registration No.";
    } else {
        $stmt = $conn->prepare(
            "SELECT student_id, registration_no, name, date_of_birth, department, degree, cgpa, passing_year, certificate_no
             FROM students
             WHERE student_id = ? OR registration_no = ?
             LIMIT 1"
        );

        if (!$stmt) {
            $error = "Could not prepare query.";
        } else {
            $stmt->bind_param("ss", $searchValue, $searchValue);

            if (!$stmt->execute()) {
                $error = "Query execution failed.";
            } else {
                $stmt->store_result();
                if ($stmt->num_rows === 0) {
                    $error = "No matching student record found.";
                } else {
                    $stmt->bind_result(
                        $studentIdValue,
                        $regNoValue,
                        $nameValue,
                        $dateOfBirthValue,
                        $departmentValue,
                        $degreeValue,
                        $cgpaValue,
                        $passingYearValue,
                        $certNoValue
                    );

                    if ($stmt->fetch()) {
                        $student = [
                            "student_id" => (string) $studentIdValue,
                            "registration_no" => (string) $regNoValue,
                            "name" => (string) $nameValue,
                            "date_of_birth" => (string) ($dateOfBirthValue ?? ""),
                            "department" => (string) $departmentValue,
                            "degree" => (string) $degreeValue,
                            "cgpa" => (string) $cgpaValue,
                            "passing_year" => (string) $passingYearValue,
                            "certificate_no" => (string) $certNoValue
                        ];
                    } else {
                        $error = "Failed to read student record.";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>UAP Verification Portal</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7f9; display: flex; justify-content: center; padding: 40px; }
        .card { width: 100%; max-width: 700px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: #1e3c72; color: white; padding: 20px; text-align: center; }
        .search-area { padding: 30px; text-align: center; border-bottom: 1px solid #eee; }
        input { padding: 12px; width: 70%; border: 2px solid #ddd; border-radius: 8px; margin-bottom: 15px; }
        .btn-search { background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; }
        
        /* ওয়াটারমার্ক সহ রেজাল্ট সেকশন */
        .info-section { position: relative; padding: 40px; background: white; }
        .info-section::before {
            content: ""; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            width: 300px; height: 300px; background-image: url('assets/uap.png'); /* লোগো ফাইল */
            background-repeat: no-repeat; background-position: center; background-size: contain;
            opacity: 0.08; z-index: 0;
        }
        .info-table { width: 100%; position: relative; z-index: 1; border-collapse: collapse; }
        .info-table td { padding: 15px; border-bottom: 1px solid #f0f0f0; }
        .label { font-weight: bold; color: #555; width: 40%; }
        .actions { padding: 20px; text-align: center; background: #fafafa; }
        .btn-dl { background: #17a2b8; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; }
    </style>
</head>
<body>

<div class="card">
    <div class="header">
        <img src="assets/uap.png" width="60" style="margin-bottom:10px;">
        <h2 style="margin:0;">Official Verification Portal</h2>
    </div>

    <form method="POST" class="search-area">
        <input type="text" name="student_id" value="<?php echo htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter Student ID / Registration No (e.g. 2181011017)" required><br>
        <button type="submit" name="search" class="btn-search">VERIFY NOW</button>
    </form>

    <?php if ($error !== ""): ?>
    <div style="padding: 0 30px 20px; color: #b00020; font-weight: 600;">
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
    </div>
    <?php endif; ?>

    <?php if($student): ?>
    <div id="download-area">
        <div class="info-section">
            <h3 style="color:#1e3c72; border-bottom: 2px solid #1e3c72; padding-bottom:10px;">Verification Result</h3>
            <table class="info-table">
                <tr><td class="label">Student Name</td><td><?php echo htmlspecialchars($student['name'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <tr><td class="label">Student ID</td><td><?php echo htmlspecialchars($student['student_id'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <tr><td class="label">Registration No</td><td><?php echo htmlspecialchars($student['registration_no'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <tr><td class="label">Date of Birth</td><td><?php echo htmlspecialchars($student['date_of_birth'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <tr><td class="label">Department</td><td><?php echo htmlspecialchars($student['department'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <tr><td class="label">Degree</td><td><?php echo htmlspecialchars($student['degree'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <tr><td class="label">CGPA</td><td style="font-weight:bold; color:green;"><?php echo htmlspecialchars($student['cgpa'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <tr><td class="label">Passing Year</td><td><?php echo htmlspecialchars($student['passing_year'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
                <tr><td class="label">Certificate No</td><td><?php echo htmlspecialchars($student['certificate_no'], ENT_QUOTES, 'UTF-8'); ?></td></tr>
            </table>
        </div>
    </div>
    <div class="actions">
        <button onclick="downloadPDF()" class="btn-dl">Download Official PDF</button>
    </div>
    <?php endif; ?>
</div>

<script>
function downloadPDF() {
    const element = document.getElementById('download-area');
    html2pdf().from(element).set({
        margin: 10,
        filename: 'UAP_Verification.pdf',
        html2canvas: { scale: 2 },
        jsPDF: { orientation: 'portrait' }
    }).save();
}
</script>

</body>
</html>
