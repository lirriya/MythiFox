<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/../include/db_config.php';
require_once __DIR__ . '/../include/email_verification.php';

$conn = db_connect();
header('Cache-Control: no-store, max-age=0');
header('Referrer-Policy: no-referrer');
$status = '';

if (isset($_GET['token'])) {
    $rawToken = is_string($_GET['token']) ? $_GET['token'] : '';
    $status = email_verification_consume($conn, $rawToken);
    $conn->close();
    header('Location: verify_email.php?status=' . rawurlencode($status), true, 303);
    exit();
}

$conn->close();
$status = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$messages = [
    'success' => 'Your email address is verified. You can return to MythiFox.',
    'already-verified' => 'This email address is already verified.',
    'expired' => 'This verification link has expired. Request a new one below.',
    'invalid' => 'This verification link is invalid or has already been used.',
    'error' => 'We could not process this verification link. Request a new one below.',
];
$message = $messages[$status] ?? 'Use the verification link from your email to verify your address.';
$isSuccess = in_array($status, ['success', 'already-verified'], true);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Email verification · MythiFox</title>
  <link rel="stylesheet" href="../assets/styles.css?v=20">
</head>
<body class="auth-page">
  <main class="auth-card">
    <p class="auth-eyebrow">MythiFox account</p>
    <h1>Email verification</h1>
    <p class="form-msg <?php echo $isSuccess ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <a class="btn btn-primary auth-action" href="resend_verification.php">Send another link</a>
    <a class="auth-back" href="../index.php">Back to MythiFox</a>
  </main>
</body>
</html>
