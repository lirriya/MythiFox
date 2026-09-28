<?php

declare(strict_types=1);

function account_user_by_id(mysqli $conn, int $userId): ?array
{
    $stmt = $conn->prepare(
        'SELECT id, username, pwd, bio, profile_pic, created_at, email, email_verified_at, pending_email '
        . 'FROM users WHERE id = ? LIMIT 1'
    );

    if ($stmt === false) {
        return null;
    }

    $stmt->bind_param('i', $userId);

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $id = null;
    $username = null;
    $passwordHash = null;
    $bio = null;
    $profilePic = null;
    $createdAt = null;
    $email = null;
    $emailVerifiedAt = null;
    $pendingEmail = null;
    $stmt->bind_result(
        $id,
        $username,
        $passwordHash,
        $bio,
        $profilePic,
        $createdAt,
        $email,
        $emailVerifiedAt,
        $pendingEmail
    );

    if (!$stmt->fetch()) {
        $stmt->close();
        return null;
    }

    $stmt->close();

    return [
        'id' => (int) $id,
        'username' => (string) $username,
        'pwd' => (string) $passwordHash,
        'bio' => $bio === null ? '' : (string) $bio,
        'profile_pic' => $profilePic === null || $profilePic === '' ? 'assets/default.jpg' : (string) $profilePic,
        'created_at' => (string) $createdAt,
        'email' => $email === null ? '' : (string) $email,
        'email_verified_at' => $emailVerifiedAt === null ? null : (string) $emailVerifiedAt,
        'pending_email' => $pendingEmail === null ? '' : (string) $pendingEmail,
    ];
}

function account_user_by_username(mysqli $conn, string $username): ?array
{
    $stmt = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');

    if ($stmt === false) {
        return null;
    }

    $stmt->bind_param('s', $username);

    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }

    $id = null;

    if (!$stmt->bind_result($id) || !$stmt->fetch()) {
        $stmt->close();
        return null;
    }

    $stmt->close();

    return account_user_by_id($conn, (int) $id);
}

function account_current_user_id(mysqli $conn): ?int
{
    $sessionId = $_SESSION['user_id'] ?? null;

    if (is_int($sessionId) || (is_string($sessionId) && ctype_digit($sessionId))) {
        $sessionId = (int) $sessionId;
        if ($sessionId > 0) {
            return $sessionId;
        }
    }

    $sessionUsername = $_SESSION['user'] ?? null;

    if (!is_string($sessionUsername) || $sessionUsername === '') {
        return null;
    }

    $user = account_user_by_username($conn, $sessionUsername);

    if ($user === null) {
        return null;
    }

    $_SESSION['user_id'] = $user['id'];

    return $user['id'];
}
