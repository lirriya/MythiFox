<?php
require_once __DIR__ . '/../include/session_boot.php';
require_once __DIR__ . '/../include/db_config.php';
require_once __DIR__ . '/../include/csrf.php';
require_once __DIR__ . '/../include/auth_guard.php';
$conn = db_connect();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_verify($_POST['csrf'] ?? '')) {
        $_SESSION['flash'] = ['type' => 'error', 'origin' => 'login', 'message' => 'Invalid form token. Please reload the page and try again.'];
        header("Location: ../index.php");
        exit();
    }

    $username = trim($_POST['username']);
    $password_input = $_POST['password'];

    $blockedUntil = auth_guard_blocked_until();
    if ($blockedUntil !== null) {
        $minutes = max(1, (int) ceil(($blockedUntil - time()) / 60));
        $message = "Too many failed attempts. Try again in " . $minutes . " minute" . ($minutes > 1 ? 's' : '') . '.';
        $_SESSION['flash'] = ['type' => 'error', 'origin' => 'login', 'message' => $message];
        header("Location: ../index.php");
        exit();
    }

    if (auth_guard_needs_captcha() && !auth_guard_captcha_verify($_POST['captcha'] ?? '')) {
        auth_guard_record_failure();
        $_SESSION['flash'] = ['type' => 'error', 'origin' => 'login', 'message' => 'Wrong answer to the security check. Please try again.'];
        header("Location: ../index.php");
        exit();
    }

    $stmt = $conn->prepare("SELECT pwd FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($password_hashed);

    if ($stmt->fetch()) {
        if (password_verify($password_input, $password_hashed)) {
            session_regenerate_id(true);
            $_SESSION['user'] = $username;
            auth_guard_reset();
            header("Location: ../index.php");
            exit();
        }
        auth_guard_record_failure();
        $message = 'Incorrect password. Try again.';
    } else {
        auth_guard_record_failure();
        $message = 'Username not found. Please check your spelling or sign up first.';
    }

    $stmt->close();

    $blockedUntil = auth_guard_blocked_until();
    if ($blockedUntil !== null) {
        $minutes = max(1, (int) ceil(($blockedUntil - time()) / 60));
        $message = "Too many failed attempts. Try again in " . $minutes . " minute" . ($minutes > 1 ? 's' : '') . '.';
    }

    $_SESSION['flash'] = ['type' => 'error', 'origin' => 'login', 'message' => $message];
    header("Location: ../index.php");
    exit();
}

$conn->close();