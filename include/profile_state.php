<?php

declare(strict_types=1);

function profile_form_state_save(array $values, array $errors): void
{
    $_SESSION['profile_form'] = [
        'values' => $values,
        'errors' => $errors,
    ];
}

function profile_form_state_take(): array
{
    $state = $_SESSION['profile_form'] ?? [];
    unset($_SESSION['profile_form']);

    return is_array($state) ? $state : [];
}

function profile_flash_set(string $type, string $message): void
{
    $_SESSION['profile_flash'] = [
        'type' => $type === 'success' ? 'success' : 'error',
        'message' => $message,
    ];
}

function profile_flash_take(): ?array
{
    $flash = $_SESSION['profile_flash'] ?? null;
    unset($_SESSION['profile_flash']);

    return is_array($flash) ? $flash : null;
}
