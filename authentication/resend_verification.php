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
            'origin' => 'resend',
            'message' => 'Your form expired. Reload the page and try again.',
        ];
        header('Location: resend_verification.php');
        exit();
    }

    $email = email_verification_normalize(
        is_string($_POST['email'] ?? null) ? $_POST['email'] : ''
    );

    if (!email_verification_is_valid($email)) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'origin' => 'resend',
            'message' => 'Enter a valid email address.',
        ];
        header('Location: resend_verification.php');
        exit();
    }

    $stmt = $conn->prepare(
        'SELECT id, username, email, email_verified_at FROM users WHERE email = ? LIMIT 1'
    );
    if ($stmt !== false) {
        $stmt->bind_param('s', $email);
        if ($stmt->execute()) {
            $stmt->bind_result($userId, $username, $storedEmail, $emailVerifiedAt);
            if ($stmt->fetch() && $emailVerifiedAt === null) {
                try {
                    $mailConfig = \MythiFox\Mailer\mail_config();
                    $mailer = \MythiFox\Mailer\MailerFactory::fromEnvironment();
                    email_verification_issue(
                        $conn,
                        (int) $userId,
                        (string) $storedEmail,
                        (string) $username,
                        $mailConfig['app_url'],
                        $mailer,
                        true
                    );
                } catch (Throwable $exception) {
                    error_log('MythiFox verification resend setup failed: ' . $exception::class);
                }
            }
        }
        $stmt->close();
    }

    $_SESSION['flash'] = [
        'type' => 'success',
        'origin' => 'resend',
        'message' => 'If an unverified MythiFox account matches that address, a new verification link has been sent.',
    ];
    header('Location: resend_verification.php');
    exit();
}

$conn->close();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resend verification · MythiFox</title>
  <link rel="stylesheet" href="../assets/styles.css?v=20">
</head>
<body class="auth-page">
  <main class="auth-card">
    <p class="auth-eyebrow">MythiFox account</p>
    <h1>Resend verification</h1>
    <p class="auth-copy">Enter the email address used for your MythiFox account. If it needs verification, we will send a fresh link.</p>
    <?php if (is_array($flash) && ($flash['origin'] ?? null) === 'resend'): ?>
      <p class="form-msg <?php echo ($flash['type'] ?? '') === 'error' ? 'error' : 'success'; ?>"><?php echo htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
    <?php endif; ?>
    <form method="POST" action="resend_verification.php" class="auth-form">
      <?php echo csrf_field(); ?>
      <label for="verification-email">Email address</label>
      <input id="verification-email" type="email" name="email" maxlength="254" autocomplete="email" required>
      <button class="btn btn-primary btn-block" type="submit">Send verification link</button>
    </form>
    <a class="auth-back" href="../index.php">Back to MythiFox</a>
  </main>
</body>
</html>
