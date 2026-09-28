<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/../include/db_config.php';
require_once __DIR__ . '/../include/email_change.php';

header('Cache-Control: no-store, max-age=0');
header('Referrer-Policy: no-referrer');

$conn = db_connect();

if (isset($_GET['token'])) {
    $rawToken = is_string($_GET['token']) ? $_GET['token'] : '';
    $status = email_change_consume($conn, $rawToken);
    $conn->close();
    header('Location: verify_email_change.php?status=' . rawurlencode($status), true, 303);
    exit();
}

$conn->close();
$status = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$messages = [
    'success' => 'Your new email address is now active.',
    'expired' => 'This confirmation link has expired. Request a new one from your profile.',
    'unavailable' => 'That email address is no longer available. Request another address from your profile.',
    'invalid' => 'This confirmation link is invalid or has already been used.',
    'error' => 'We could not process this confirmation link. Request a new one from your profile.',
];
$message = $messages[$status] ?? 'Use the confirmation link from your email to activate the new address.';
$isSuccess = $status === 'success';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirm email change · MythiFox</title>
  <link rel="stylesheet" href="../assets/styles.css?v=20">
</head>
<body class="auth-page">
  <main class="auth-card">
    <p class="auth-eyebrow">MythiFox account</p>
    <h1>Confirm email change</h1>
    <p class="form-msg <?php echo $isSuccess ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <a class="btn btn-primary auth-action" href="../profile.php">Return to profile</a>
    <a class="auth-back" href="../index.php">Back to MythiFox</a>
  </main>
</body>
</html>
