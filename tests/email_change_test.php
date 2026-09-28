<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/include/email_change.php';

use MythiFox\Mailer\MailerInterface;
use MythiFox\Mailer\MailResult;

final class RecordingMailer implements MailerInterface
{
    /** @var array<int, string> */
    public array $recipients = [];

    public function send(
        string $recipient,
        string $subject,
        string $htmlBody,
        ?string $textBody = null
    ): MailResult {
        $this->recipients[] = $recipient;

        return MailResult::success();
    }
}

final class FailingMailer implements MailerInterface
{
    public function send(
        string $recipient,
        string $subject,
        string $htmlBody,
        ?string $textBody = null
    ): MailResult {
        return MailResult::failure('smtp_delivery_failed');
    }
}

$failures = [];

if (email_change_normalize('  Student@Example.Test ') !== 'student@example.test') {
    $failures[] = 'normalize';
}

if (!email_change_is_valid('student@example.test')) {
    $failures[] = 'valid_email';
}

foreach (['not-an-email', '', 'a@b', str_repeat('a', 250) . '@example.test'] as $candidate) {
    if (email_change_is_valid($candidate)) {
        $failures[] = 'invalid_email_' . bin2hex($candidate);
    }
}

if (strlen('a' . str_repeat('b', 240) . '@example.test') > 254) {
    $candidate = 'a' . str_repeat('b', 240) . '@example.test';
    if (email_change_is_valid($candidate)) {
        $failures[] = 'email_over_254_rejected';
    }
}

[$html, $text] = email_change_template('<Admin>', 'https://example.test/confirm?token=abc&next=1');

if (str_contains($html, '<Admin>')) {
    $failures[] = 'template_username_escape';
}

if (!str_contains($html, '&lt;Admin&gt;')) {
    $failures[] = 'template_username_escaped_value';
}

if (!str_contains($html, 'token=abc&amp;next=1')) {
    $failures[] = 'template_url_escape';
}

if (!str_contains($text, 'https://example.test/confirm?token=abc&next=1')) {
    $failures[] = 'template_plain_url';
}

require dirname(__DIR__) . '/include/db_config.php';
$conn = db_connect();

$testUsername = 'ec_' . bin2hex(random_bytes(4));
$testEmail = bin2hex(random_bytes(6)) . '@example.test';
$secondEmail = bin2hex(random_bytes(6)) . '@example.test';

$create = $conn->prepare(
    'INSERT INTO users (username, pwd, email, pending_email) VALUES (?, ?, ?, ?)'
);
$passwordHash = password_hash('Str0ng!pass', PASSWORD_DEFAULT);
$create->bind_param('ssss', $testUsername, $passwordHash, $testEmail, $secondEmail);

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

$mailer = new RecordingMailer();
$issue = email_change_issue(
    $conn,
    $userId,
    $secondEmail,
    $testUsername,
    'https://example.test',
    $mailer,
    false
);

if (!$issue->sent) {
    $failures[] = 'issue_succeeds';
}

if (count($mailer->recipients) !== 1 || $mailer->recipients[0] !== $secondEmail) {
    $failures[] = 'issue_delivers_to_new_address';
}

$tokenStmt = $conn->prepare('SELECT token_hash FROM email_change_tokens WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$tokenStmt->bind_param('i', $userId);
$tokenStmt->execute();
$tokenRow = $tokenStmt->get_result()->fetch_assoc();
$tokenStmt->close();

if ($tokenRow === null) {
    $failures[] = 'issue_persists_token';
} elseif (strlen((string) $tokenRow['token_hash']) !== 64) {
    $failures[] = 'issue_stores_sha256_hash';
}

if (email_change_consume($conn, 'not-a-token') !== 'invalid') {
    $failures[] = 'consume_rejects_malformed_token';
}

if (email_change_consume($conn, str_repeat('z', 64)) !== 'invalid') {
    $failures[] = 'consume_rejects_unknown_token';
}

if (email_change_consume($conn, bin2hex(random_bytes(32))) !== 'invalid') {
    $failures[] = 'consume_rejects_random_token';
}

$conn->query('DELETE FROM email_change_tokens WHERE user_id = ' . $userId);
$cleanupUser($conn, $userId);
$conn->close();

if ($failures !== []) {
    fwrite(STDERR, 'Email change tests failed: ' . implode(', ', $failures) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Email change tests passed\n");
