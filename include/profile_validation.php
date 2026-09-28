<?php

declare(strict_types=1);

const PROFILE_BIO_MAX_LENGTH = 500;

function profile_request_string(array $source, string $key): string
{
    $value = $source[$key] ?? '';

    return is_string($value) ? $value : '';
}

function profile_username_normalize(string $username): string
{
    return trim($username);
}

function profile_username_is_valid(string $username): bool
{
    return preg_match('/\A[A-Za-z0-9_]{3,20}\z/D', $username) === 1;
}

function profile_bio_normalize(string $bio): string
{
    return trim(str_replace(["\r\n", "\r"], "\n", $bio));
}

function profile_bio_length(string $bio): int
{
    return function_exists('mb_strlen') ? mb_strlen($bio) : strlen($bio);
}

function profile_bio_is_valid(string $bio): bool
{
    return profile_bio_length($bio) <= PROFILE_BIO_MAX_LENGTH;
}
