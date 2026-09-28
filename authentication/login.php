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

    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password_input = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

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

    $stmt = $conn->prepare("SELECT id, pwd FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user_id = null;
    $password_hashed = null;
    $stmt->bind_result($user_id, $password_hashed);

    if ($stmt->fetch()) {
        if (password_verify($password_input, (string) $password_hashed)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user_id;
            $_SESSION['user'] = $username;
            auth_guard_reset();
            header("Location: ../index.php");
            exit();
        }
    } else {
        password_verify($password_input, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
    }

    auth_guard_record_failure();
    $message = 'Invalid username or password. Try again.';

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