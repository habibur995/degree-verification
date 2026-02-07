<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require "../backend/db.php";

$data = $conn->query("SELECT * FROM students");
$loadError = "";
if (!$data) {
    $loadError = "Unable to load student records.";
}
$exportStatus = $_GET['export'] ?? "";
$exportCount = isset($_GET['count']) ? (int) $_GET['count'] : 0;
?>
<h2>Admin Dashboard</h2>
<a href="add-student.php">➕ Add Student</a> |
<a href="export_students_json.php">⬇ Export JSON</a> |
<a href="logout.php">Logout</a>
<hr>
<?php if ($exportStatus === "ok"): ?>
<p style="color:green;">Export completed. <?= $exportCount ?> record(s) written to data/students.json.</p>
<?php elseif ($exportStatus === "error"): ?>
<p style="color:red;">Export failed. Check DB connection and folder write permission.</p>
<?php endif; ?>

<?php if ($loadError !== ""): ?>
<p style="color:red;"><?= htmlspecialchars($loadError, ENT_QUOTES, "UTF-8") ?></p>
<?php endif; ?>

<table border="1" cellpadding="8">
<tr>
<th>ID</th><th>Name</th><th>Student ID</th><th>Action</th>
</tr>
<?php if ($data): ?>
<?php while($row=$data->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars((string) $row['id'], ENT_QUOTES, "UTF-8") ?></td>
<td><?= htmlspecialchars((string) $row['name'], ENT_QUOTES, "UTF-8") ?></td>
<td><?= htmlspecialchars((string) $row['student_id'], ENT_QUOTES, "UTF-8") ?></td>
<td>
<a href="edit-student.php?id=<?= urlencode((string) $row['id']) ?>">Edit</a> |
<a href="delete-student.php?id=<?= urlencode((string) $row['id']) ?>" onclick="return confirm('Delete?')">Delete</a>
</td>
</tr>
<?php endwhile; ?>
<?php endif; ?>
</table>
