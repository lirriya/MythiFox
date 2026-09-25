<?php

const AUTH_MAX_ATTEMPTS = 5;
const AUTH_LOCKOUT_SECONDS = 900;
const AUTH_CAPTCHA_AFTER = 3;

function auth_guard_attempts(): int
{
    return (int) ($_SESSION['auth']['attempts'] ?? 0);
}

function auth_guard_blocked_until(): ?int
{
    if (isset($_SESSION['auth']['blocked_until']) && $_SESSION['auth']['blocked_until'] > time()) {
        return (int) $_SESSION['auth']['blocked_until'];
    }
    return null;
}

function auth_guard_record_failure(): void
{
    $_SESSION['auth']['attempts'] = auth_guard_attempts() + 1;
    if ($_SESSION['auth']['attempts'] >= AUTH_MAX_ATTEMPTS) {
        $_SESSION['auth']['blocked_until'] = time() + AUTH_LOCKOUT_SECONDS;
    }
}

function auth_guard_reset(): void
{
    unset($_SESSION['auth']);
}

function auth_guard_needs_captcha(): bool
{
    return auth_guard_blocked_until() === null && auth_guard_attempts() >= AUTH_CAPTCHA_AFTER;
}

function auth_guard_captcha_prompt(): string
{
    if (empty($_SESSION['auth']['captcha'])) {
        $_SESSION['auth']['captcha'] = ['a' => random_int(2, 9), 'b' => random_int(2, 9)];
    }
    $c = $_SESSION['auth']['captcha'];
    return $c['a'] . ' + ' . $c['b'];
}

function auth_guard_captcha_verify($answer): bool
{
    if (empty($_SESSION['auth']['captcha'])) {
        return false;
    }
    $c = $_SESSION['auth']['captcha'];
    unset($_SESSION['auth']['captcha']);
    return ctype_digit((string) $answer) && (int) $answer === $c['a'] + $c['b'];
}