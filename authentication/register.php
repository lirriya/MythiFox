<?php

declare(strict_types=1);

require_once __DIR__ . '/../include/session_boot.php';
require_once __DIR__ . '/../include/db_config.php';
require_once __DIR__ . '/../include/csrf.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/../include/email_verification.php';

$conn = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? '')) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'origin' => 'signup',
            'message' => 'Invalid form token. Please reload the page and try again.',
        ];
        header('Location: ../index.php');
        exit();
    }

    $username = trim(is_string($_POST['username'] ?? null) ? $_POST['username'] : '');
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $email = email_verification_normalize(
        is_string($_POST['email'] ?? null) ? $_POST['email'] : ''
    );

    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'origin' => 'signup',
            'message' => 'Username must be 3-20 characters: letters, numbers, or underscores only.',
        ];
        header('Location: ../index.php');
        exit();
    }

    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'origin' => 'signup',
            'message' => 'Password must be at least 8 characters and include at least one letter and one number.',
        ];
        header('Location: ../index.php');
        exit();
    }

    if (!email_verification_is_valid($email)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'origin' => 'signup',
            'message' => 'Enter a valid email address.',
        ];
        header('Location: ../index.php');
        exit();
    }

    $check = $conn->prepare('SELECT id FROM users WHERE username = ?');
    if ($check === false) {
        error_log('MythiFox registration username lookup failed.');
        $_SESSION['flash'] = [
            'type' => 'error',
            'origin' => 'signup',
            'message' => 'We could not create your account right now. Please try again.',
        ];
        header('Location: ../index.php');
        exit();
    }
    $check->bind_param('s', $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->close();
        $_SESSION['flash'] = [
            'type' => 'error',
            'origin' => 'signup',
            'message' => 'This username is already taken. Please choose another.',
        ];
        header('Location: ../index.php');
        exit();
    }
    $check->close();

    $emailCheck = $conn->prepare('SELECT id FROM users WHERE email = ?');
    if ($emailCheck === false) {
        error_log('MythiFox registration email lookup failed.');
        $_SESSION['flash'] = [
            'type' => 'error',
            'origin' => 'signup',
            'message' => 'We could not create your account right now. Please try again.',
        ];
        header('Location: ../index.php');
        exit();
    }
    $emailCheck->bind_param('s', $email);
    $emailCheck->execute();
    $emailCheck->store_result();

    if ($emailCheck->num_rows > 0) {
        $emailCheck->close();
        $_SESSION['flash'] = [
            'type' => 'error',
            'origin' => 'signup',
            'message' => 'This email address is already registered.',
        ];
        header('Location: ../index.php');
        exit();
    }
    $emailCheck->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    if (!is_string($hash) || $hash === '') {
        error_log('MythiFox registration password hashing failed.');
        $_SESSION['flash'] = [
            'type' => 'error',
            'origin' => 'signup',
            'message' => 'We could not create your account right now. Please try again.',
        ];
        header('Location: ../index.php');
        exit();
    }

    $stmt = $conn->prepare('INSERT INTO users (username, pwd, email) VALUES (?, ?, ?)');
    if ($stmt === false) {
        error_log('MythiFox registration insert preparation failed.');
        $_SESSION['flash'] = [
            'type' => 'error',
            'origin' => 'signup',
            'message' => 'We could not create your account right now. Please try again.',
        ];
        header('Location: ../index.php');
        exit();
    }
    $stmt->bind_param('sss', $username, $hash, $email);
    if (!$stmt->execute()) {
        $stmt->close();
        error_log('MythiFox registration insert failed.');
        $_SESSION['flash'] = [
            'type' => 'error',
            'origin' => 'signup',
            'message' => 'This account could not be created. Please try again.',
        ];
        header('Location: ../index.php');
        exit();
    }
    $stmt->close();
    $userId = (int) $conn->insert_id;

    $verificationSent = false;
    try {
        $mailConfig = \MythiFox\Mailer\mail_config();
        $mailer = \MythiFox\Mailer\MailerFactory::fromEnvironment();
        $mailResult = email_verification_issue(
            $conn,
            $userId,
            $email,
            $username,
            $mailConfig['app_url'],
            $mailer
        );
        $verificationSent = $mailResult->sent;
    } catch (Throwable $exception) {
        error_log('MythiFox account verification setup failed: ' . $exception::class);
    }

    $conn->close();
    session_regenerate_id(true);
    $_SESSION['user'] = $username;
    $_SESSION['flash'] = [
        'type' => $verificationSent ? 'success' : 'error',
        'origin' => 'signup',
        'message' => $verificationSent
            ? 'Your account was created. Check your email for a verification link.'
            : 'Your account was created, but the verification email could not be sent. Use the resend page to try again.',
    ];
    header('Location: ../index.php');
    exit();
}

$conn->close();
