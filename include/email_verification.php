<?php

declare(strict_types=1);

use MythiFox\Mailer\MailerInterface;
use MythiFox\Mailer\MailResult;

require_once __DIR__ . '/email_templates/verification.php';

const EMAIL_VERIFICATION_TOKEN_TTL_SECONDS = 86400;
const EMAIL_VERIFICATION_RESEND_COOLDOWN_SECONDS = 60;

function email_verification_normalize(string $email): string
{
    return strtolower(trim($email));
}

function email_verification_is_valid(string $email): bool
{
    return strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function email_verification_can_issue(mysqli $conn, int $userId): bool
{
    $stmt = $conn->prepare(
        'SELECT created_at FROM email_verification_tokens WHERE user_id = ? ORDER BY id DESC LIMIT 1'
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

    $createdTimestamp = strtotime($createdAt);

    return $createdTimestamp === false
        || $createdTimestamp <= time() - EMAIL_VERIFICATION_RESEND_COOLDOWN_SECONDS;
}

function email_verification_issue(
    mysqli $conn,
    int $userId,
    string $email,
    string $username,
    string $appUrl,
    MailerInterface $mailer,
    bool $respectCooldown = false
): MailResult {
    if (!email_verification_is_valid($email) || !filter_var($appUrl, FILTER_VALIDATE_URL)) {
        return MailResult::failure('invalid_verification_request');
    }

    if ($respectCooldown && !email_verification_can_issue($conn, $userId)) {
        return MailResult::failure('resend_throttled');
    }

    $transactionStarted = false;

    try {
        $rawToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $rawToken);
        $expiresAt = date('Y-m-d H:i:s', time() + EMAIL_VERIFICATION_TOKEN_TTL_SECONDS);

        if (!$conn->begin_transaction()) {
            throw new RuntimeException('transaction_start_failed');
        }
        $transactionStarted = true;

        $invalidate = $conn->prepare(
            'UPDATE email_verification_tokens SET used_at = CURRENT_TIMESTAMP WHERE user_id = ? AND used_at IS NULL'
        );
        if ($invalidate === false) {
            throw new RuntimeException('invalidate_prepare_failed');
        }
        $invalidate->bind_param('i', $userId);
        if (!$invalidate->execute()) {
            $invalidate->close();
            throw new RuntimeException('invalidate_execute_failed');
        }
        $invalidate->close();

        $insert = $conn->prepare(
            'INSERT INTO email_verification_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)'
        );
        if ($insert === false) {
            throw new RuntimeException('token_prepare_failed');
        }
        $insert->bind_param('iss', $userId, $tokenHash, $expiresAt);
        if (!$insert->execute()) {
            $insert->close();
            throw new RuntimeException('token_execute_failed');
        }
        $insert->close();

        if (!$conn->commit()) {
            throw new RuntimeException('transaction_commit_failed');
        }
        $transactionStarted = false;
    } catch (Throwable $exception) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        error_log('MythiFox email verification token creation failed: ' . $exception::class);
        return MailResult::failure('token_creation_failed');
    }

    $verificationUrl = rtrim($appUrl, '/')
        . '/authentication/verify_email.php?token='
        . rawurlencode($rawToken);
    [$htmlBody, $textBody] = email_verification_template($username, $verificationUrl);

    return $mailer->send($email, 'Verify your MythiFox email', $htmlBody, $textBody);
}

function email_verification_consume(mysqli $conn, string $rawToken): string
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
            'SELECT t.id, t.user_id, t.expires_at, t.used_at, u.email_verified_at '
            . 'FROM email_verification_tokens t '
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
        $expiresAt = null;
        $usedAt = null;
        $emailVerifiedAt = null;
        $find->bind_result($tokenId, $userId, $expiresAt, $usedAt, $emailVerifiedAt);
        $found = $find->fetch();
        $find->close();

        if (!$found) {
            $conn->rollback();
            $transactionStarted = false;
            return 'invalid';
        }

        if ($emailVerifiedAt !== null) {
            $conn->rollback();
            $transactionStarted = false;
            return 'already-verified';
        }

        if ($usedAt !== null) {
            $conn->rollback();
            $transactionStarted = false;
            return 'invalid';
        }

        $expiresTimestamp = $expiresAt === null ? false : strtotime($expiresAt);
        if ($expiresTimestamp === false || $expiresTimestamp <= time()) {
            $conn->rollback();
            $transactionStarted = false;
            return 'expired';
        }

        $consume = $conn->prepare(
            'UPDATE email_verification_tokens SET used_at = CURRENT_TIMESTAMP WHERE id = ? AND used_at IS NULL'
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

        $verify = $conn->prepare(
            'UPDATE users SET email_verified_at = CURRENT_TIMESTAMP WHERE id = ? AND email_verified_at IS NULL'
        );
        if ($verify === false) {
            throw new RuntimeException('user_verify_prepare_failed');
        }
        $verify->bind_param('i', $userId);
        if (!$verify->execute() || $verify->affected_rows !== 1) {
            $verify->close();
            throw new RuntimeException('user_verify_execute_failed');
        }
        $verify->close();

        $invalidate = $conn->prepare(
            'UPDATE email_verification_tokens SET used_at = CURRENT_TIMESTAMP WHERE user_id = ? AND used_at IS NULL'
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
        error_log('MythiFox email verification consumption failed: ' . $exception::class);
        return 'error';
    }
}
