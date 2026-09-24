<?php
require_once __DIR__ . '/../include/session_boot.php';
require_once __DIR__ . '/../include/db_config.php';
require_once __DIR__ . '/../include/csrf.php';
$conn = db_connect();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_verify($_POST['csrf'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'origin' => 'signup', 'message' => 'Invalid form token. Please reload the page and try again.'];
        header("Location: ../index.php");
        exit();
    }

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $_SESSION['flash'] = ['type' => 'error', 'origin' => 'signup', 'message' => 'Username must be 3-20 characters: letters, numbers, or underscores only.'];
        header("Location: ../index.php");
        exit();
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $_SESSION['flash'] = ['type' => 'error', 'origin' => 'signup', 'message' => 'Password must be at least 8 characters and include at least one letter and one number.'];
        header("Location: ../index.php");
        exit();
    }

    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $_SESSION['flash'] = ['type' => 'error', 'origin' => 'signup', 'message' => 'This username is already taken. Please choose another.'];
        header("Location: ../index.php");
        exit();
    }

    $check->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, pwd) VALUES (?, ?)");
    $stmt->bind_param("ss", $username, $hash);
    $stmt->execute();
    $stmt->close();

    session_regenerate_id(true);
    $_SESSION['user'] = $username;
    header("Location: ../index.php");
    exit();
}