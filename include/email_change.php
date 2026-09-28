<?php

declare(strict_types=1);

use MythiFox\Mailer\MailerInterface;
use MythiFox\Mailer\MailResult;

require_once __DIR__ . '/email_templates/email_change.php';

const EMAIL_CHANGE_TOKEN_TTL_SECONDS = 86400;
const EMAIL_CHANGE_RESEND_COOLDOWN_SECONDS = 60;

function email_change_normalize(string $email): string
{
    return strtolower(trim($email));
}

function email_change_is_valid(string $email): bool
{
    return strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function email_change_can_issue(mysqli $conn, int $userId): bool
{
    $stmt = $conn->prepare(
        'SELECT created_at FROM email_change_tokens WHERE user_id = ? ORDER BY id DESC LIMIT 1'
    );

    if ($stmt === false) {
        return false;
    }

    $stmt->bind_param('i', $userId);

    if (!$stmt->execute()) {
        $stmt->close();
        return false;
    }

    $createdAt = null;
    $stmt->bind_result($createdAt);
    $hasToken = $stmt->fetch();
    $stmt->close();

    if (!$hasToken || $createdAt === null) {
        return true;
    }

    $createdTimestamp = strtotime((string) $createdAt);

    return $createdTimestamp === false
        || $createdTimestamp <= time() - EMAIL_CHANGE_RESEND_COOLDOWN_SECONDS;
}

function email_change_issue(
    mysqli $conn,
    int $userId,
    string $newEmail,
    string $username,
    string $appUrl,
    MailerInterface $mailer,
    bool $respectCooldown = false
): MailResult {
    $newEmail = email_change_normalize($newEmail);

    if (!email_change_is_valid($newEmail) || !filter_var($appUrl, FILTER_VALIDATE_URL)) {
        return MailResult::failure('invalid_email_change_request');
    }

    if ($respectCooldown && !email_change_can_issue($conn, $userId)) {
        return MailResult::failure('email_change_throttled');
    }

    $rawToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $rawToken);
    $expiresAt = date('Y-m-d H:i:s', time() + EMAIL_CHANGE_TOKEN_TTL_SECONDS);
    $transactionStarted = false;

    try {
        if (!$conn->begin_transaction()) {
            throw new RuntimeException('transaction_start_failed');
        }
        $transactionStarted = true;

        $insert = $conn->prepare(
            'INSERT INTO email_change_tokens (user_id, new_email, token_hash, expires_at) VALUES (?, ?, ?, ?)'
        );
        if ($insert === false) {
            throw new RuntimeException('token_prepare_failed');
        }
        $insert->bind_param('isss', $userId, $newEmail, $tokenHash, $expiresAt);
        if (!$insert->execute()) {
            $insert->close();
            throw new RuntimeException('token_execute_failed');
        }
        $insert->close();

        $tokenId = (int) $conn->insert_id;

        if (!$conn->commit()) {
            throw new RuntimeException('transaction_commit_failed');
        }
        $transactionStarted = false;
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        error_log('MythiFox email change token creation failed: ' . $exception::class);
        return MailResult::failure('token_creation_failed');
    }

    $verificationUrl = rtrim($appUrl, '/')
        . '/authentication/verify_email_change.php?token='
        . rawurlencode($rawToken);
    [$htmlBody, $textBody] = email_change_template($username, $verificationUrl);

    try {
        $result = $mailer->send($newEmail, 'Confirm your new MythiFox email', $htmlBody, $textBody);
    } catch (Throwable $exception) {
        error_log('MythiFox email change delivery failed: ' . $exception::class);
        $result = MailResult::failure('smtp_delivery_failed');
    }

    if (!$result->sent) {
        $markUsed = $conn->prepare(
            'UPDATE email_change_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = ? AND used_at IS NULL'
        );
        if ($markUsed !== false) {
            $markUsed->bind_param('i', $tokenId);
            $markUsed->execute();
            $markUsed->close();
        }
        return $result;
    }

    $invalidate = $conn->prepare(
        'UPDATE email_change_tokens SET used_at = CURRENT_TIMESTAMP WHERE user_id = ? AND id <> ? AND used_at IS NULL'
    );
    if ($invalidate !== false) {
        $invalidate->bind_param('ii', $userId, $tokenId);
        $invalidate->execute();
        $invalidate->close();
    }

    return MailResult::success();
}

function email_change_consume(mysqli $conn, string $rawToken): string
{
    if (!preg_match('/\A[a-f0-9]{64}\z/D', $rawToken)) {
        return 'invalid';
    }

    $tokenHash = hash('sha256', $rawToken);
    $transactionStarted = false;

    try {
        if (!$conn->begin_transaction()) {
            throw new RuntimeException('transaction_start_failed');
        }
        $transactionStarted = true;

        $find = $conn->prepare(
            'SELECT t.id, t.user_id, t.new_email, t.expires_at, t.used_at, u.email, u.pending_email '
            . 'FROM email_change_tokens t '
            . 'INNER JOIN users u ON u.id = t.user_id '
            . 'WHERE t.token_hash = ? LIMIT 1 FOR UPDATE'
        );
        if ($find === false) {
            throw new RuntimeException('token_lookup_prepare_failed');
        }
        $find->bind_param('s', $tokenHash);
        if (!$find->execute()) {
            $find->close();
            throw new RuntimeException('token_lookup_execute_failed');
        }

        $tokenId = null;
        $userId = null;
        $newEmail = null;
        $expiresAt = null;
        $usedAt = null;
        $currentEmail = null;
        $pendingEmail = null;
        $find->bind_result(
            $tokenId,
            $userId,
            $newEmail,
            $expiresAt,
            $usedAt,
            $currentEmail,
            $pendingEmail
        );
        $found = $find->fetch();
        $find->close();

        if (!$found) {
            $conn->rollback();
            $transactionStarted = false;
            return 'invalid';
        }

        if ($usedAt !== null) {
            $conn->rollback();
            $transactionStarted = false;
            return 'invalid';
        }

        $expiresTimestamp = $expiresAt === null ? false : strtotime((string) $expiresAt);
        if ($expiresTimestamp === false || $expiresTimestamp <= time()) {
            $conn->rollback();
            $transactionStarted = false;
            return 'expired';
        }

        $newEmail = email_change_normalize((string) $newEmail);
        $pendingEmail = email_change_normalize((string) ($pendingEmail ?? ''));

        if ($pendingEmail === '' || !hash_equals($pendingEmail, $newEmail)) {
            $conn->rollback();
            $transactionStarted = false;
            return 'invalid';
        }

        $availability = $conn->prepare(
            'SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1 FOR UPDATE'
        );
        if ($availability === false) {
            throw new RuntimeException('email_availability_prepare_failed');
        }
        $availability->bind_param('si', $newEmail, $userId);
        if (!$availability->execute()) {
            $availability->close();
            throw new RuntimeException('email_availability_execute_failed');
        }
        $unavailableId = null;
        $availability->bind_result($unavailableId);
        $emailUnavailable = $availability->fetch();
        $availability->close();

        if ($emailUnavailable) {
            $conn->rollback();
            $transactionStarted = false;
            return 'unavailable';
        }

        $consume = $conn->prepare(
            'UPDATE email_change_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = ? AND used_at IS NULL'
        );
        if ($consume === false) {
            throw new RuntimeException('token_consume_prepare_failed');
        }
        $consume->bind_param('i', $tokenId);
        if (!$consume->execute() || $consume->affected_rows !== 1) {
            $consume->close();
            throw new RuntimeException('token_consume_execute_failed');
        }
        $consume->close();

        $activate = $conn->prepare(
            'UPDATE users SET email = ?, pending_email = NULL, email_verified_at = CURRENT_TIMESTAMP '
            . 'WHERE id = ? AND pending_email = ?'
        );
        if ($activate === false) {
            throw new RuntimeException('email_activate_prepare_failed');
        }
        $activate->bind_param('sis', $newEmail, $userId, $newEmail);
        if (!$activate->execute() || $activate->affected_rows !== 1) {
            $activate->close();
            throw new RuntimeException('email_activate_execute_failed');
        }
        $activate->close();

        $invalidate = $conn->prepare(
            'UPDATE email_change_tokens SET used_at = CURRENT_TIMESTAMP WHERE user_id = ? AND used_at IS NULL'
        );
        if ($invalidate === false) {
            throw new RuntimeException('remaining_tokens_prepare_failed');
        }
        $invalidate->bind_param('i', $userId);
        if (!$invalidate->execute()) {
            $invalidate->close();
            throw new RuntimeException('remaining_tokens_execute_failed');
        }
        $invalidate->close();

        if (!$conn->commit()) {
            throw new RuntimeException('transaction_commit_failed');
        }
        $transactionStarted = false;

        return 'success';
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        error_log('MythiFox email change consumption failed: ' . $exception::class);
        return 'error';
    }
}
