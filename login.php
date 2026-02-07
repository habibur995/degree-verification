<?php
session_start();
require "../backend/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = trim((string) ($_POST["username"] ?? ""));
    $password = (string) ($_POST["password"] ?? "");
    $passHash = hash("sha256", $password);

    if ($user === "" || $password === "") {
        $error = "Username and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM admins WHERE username = ? AND password = ? LIMIT 1");
        if (!$stmt) {
            $error = "Login service unavailable.";
        } else {
            $stmt->bind_param("ss", $user, $passHash);
            if (!$stmt->execute()) {
                $error = "Login service unavailable.";
            } else {
                $stmt->store_result();
                if ($stmt->num_rows === 1) {
                    $_SESSION["admin"] = $user;
                    header("Location: dashboard.php");
                    exit;
                }
                $error = "Invalid login";
            }
        }
    }
}
?>
<form method="POST">
<h2>Admin Login</h2>
<input name="username" placeholder="Username" required><br><br>
<input name="password" type="password" placeholder="Password" required><br><br>
<button>Login</button>
<p style="color:red"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
</form>
