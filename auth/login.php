
<?php
session_start();

require_once __DIR__ . "/../config/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../public/login.php");
    exit;
}

$username = trim($_POST["username"] ?? "");
$password = $_POST["password"] ?? "";

if ($username === "" || $password === "") {
    header("Location: ../public/login.php?error=required");
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT id, username, password, full_name, role, status
         FROM users
         WHERE username = ?
         LIMIT 1"
    );

    $stmt->execute([$username]);
    $user = $stmt->fetch();

} catch (PDOException $e) {
    error_log("Login test database error: " . $e->getMessage());
    header("Location: ../public/login.php?error=database");
    exit;
}

if (!$user) {
    header("Location: ../public/login.php?error=invalid");
    exit;
}

if (strtolower(trim((string)$user["status"])) !== "active") {
    header("Location: ../public/login.php?error=disabled");
    exit;
}

$storedPassword = (string)$user["password"];

if (password_verify($password, $storedPassword)) {
    // Password is already hashed.
} elseif (hash_equals($storedPassword, $password)) {
    // Upgrade a legacy plain-text password.
    $newHash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $update = $pdo->prepare(
            "UPDATE users SET password = ? WHERE id = ?"
        );
        $update->execute([$newHash, $user["id"]]);
    } catch (PDOException $e) {
        error_log("Password upgrade failed: " . $e->getMessage());
        header("Location: ../public/login.php?error=database");
        exit;
    }
} else {
    header("Location: ../public/login.php?error=invalid");
    exit;
}

session_regenerate_id(true);

$_SESSION["user_id"] = $user["id"];
$_SESSION["username"] = $user["username"];
$_SESSION["full_name"] = $user["full_name"];
$_SESSION["role"] = $user["role"];
$_SESSION["login_time"] = time();
$_SESSION["last_activity"] = time();

header("Location: ../public/dashboard.php");
exit;
