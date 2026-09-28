<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/include/email_change.php';

use MythiFox\Mailer\MailerInterface;
use MythiFox\Mailer\MailResult;

final class CapturingMailer implements MailerInterface
{
    public ?string $htmlBody = null;

    public function send(
        string $recipient,
        string $subject,
        string $htmlBody,
        ?string $textBody = null
    ): MailResult {
        $this->htmlBody = $htmlBody;

        return MailResult::success();
    }
}

$failures = [];

require dirname(__DIR__) . '/include/db_config.php';
$conn = db_connect();

$suffix = bin2hex(random_bytes(6));
$username = 'ec_' . $suffix;
$currentEmail = $suffix . '-current@example.test';
$newEmail = $suffix . '-new@example.test';
$passwordHash = password_hash('Str0ng!pass', PASSWORD_DEFAULT);

$create = $conn->prepare(
    'INSERT INTO users (username, pwd, email, email_verified_at) VALUES (?, ?, ?, NULL)'
);
$create->bind_param('sss', $username, $passwordHash, $currentEmail);

if (!$create->execute()) {
    fwrite(STDERR, "Could not create test user: {$conn->error}\n");
    exit(1);
}
$create->close();
$userId = (int) $conn->insert_id;

$cleanupUser = static function (mysqli $conn, int $userId): void {
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->close();
};

$setPending = $conn->prepare('UPDATE users SET pending_email = ? WHERE id = ?');
$setPending->bind_param('si', $newEmail, $userId);
$setPending->execute();
$setPending->close();

$mailer = new CapturingMailer();
$issue = email_change_issue($conn, $userId, $newEmail, $username, 'https://example.test', $mailer, false);

if (!$issue->sent) {
    $failures[] = 'issue_succeeds';
}

if (!preg_match('/verify_email_change\.php\?token=([a-f0-9]{64})/', (string) $mailer->htmlBody, $matches)) {
    $failures[] = 'issue_link_contains_raw_token';

    $cleanupUser($conn, $userId);
    $conn->close();
    fwrite(STDERR, 'Email change round-trip tests failed: ' . implode(', ', $failures) . PHP_EOL);
    exit(1);
}

$rawToken = $matches[1];

$tokenStmt = $conn->prepare('SELECT token_hash FROM email_change_tokens WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$tokenStmt->bind_param('i', $userId);
$tokenStmt->execute();
$storedHash = (string) ($tokenStmt->get_result()->fetch_assoc()['token_hash'] ?? '');
$tokenStmt->close();

if ($storedHash === '') {
    $failures[] = 'token_persisted';
} elseif (!hash_equals(hash('sha256', $rawToken), $storedHash)) {
    $failures[] = 'raw_token_never_stored_in_plaintext';
}

if (email_change_consume($conn, $rawToken) !== 'success') {
    $failures[] = 'consume_succeeds';
}

$afterStmt = $conn->prepare('SELECT email, pending_email, email_verified_at FROM users WHERE id = ?');
$afterStmt->bind_param('i', $userId);
$afterStmt->execute();
$after = $afterStmt->get_result()->fetch_assoc();
$afterStmt->close();

if (($after['email'] ?? null) !== $newEmail) {
    $failures[] = 'new_email_becomes_active';
}

if (($after['pending_email'] ?? null) !== null) {
    $failures[] = 'pending_email_cleared';
}

if (($after['email_verified_at'] ?? null) === null) {
    $failures[] = 'email_marked_verified';
}

if (email_change_consume($conn, $rawToken) !== 'invalid') {
    $failures[] = 'token_is_single_use';
}

$setPendingAgain = $conn->prepare('UPDATE users SET pending_email = ? WHERE id = ?');
$setPendingAgain->bind_param('si', $newEmail, $userId);
$setPendingAgain->execute();
$setPendingAgain->close();

$setPendingAgain = $conn->prepare('UPDATE users SET pending_email = ? WHERE id = ?');
$setPendingAgain->bind_param('si', $newEmail, $userId);
$setPendingAgain->execute();
$setPendingAgain->close();

$mailerTwo = new CapturingMailer();
email_change_issue($conn, $userId, $newEmail, $username, 'https://example.test', $mailerTwo, false);
preg_match('/verify_email_change\.php\?token=([a-f0-9]{64})/', (string) $mailerTwo->htmlBody, $matchesTwo);
$secondToken = $matchesTwo[1] ?? '';

if ($secondToken !== '' && email_change_consume($conn, $secondToken) !== 'success') {
    $failures[] = 'reissued_token_is_usable';
}

$cleanupUser($conn, $userId);
$conn->close();

if ($failures !== []) {
    fwrite(STDERR, 'Email change round-trip tests failed: ' . implode(', ', $failures) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Email change round-trip tests passed\n");
