<?php

declare(strict_types=1);

function validate_uploaded_image(array $file, int $max_bytes = 5242880, int $max_dimension = 6000): array
{
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!isset($file['error'], $file['name'], $file['size'], $file['tmp_name'])) {
        return [false, 'The uploaded file is invalid.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE || $file['name'] === '') {
        return [false, 'No file selected.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Upload failed with error code ' . (int) $file['error'] . '.'];
    }

    if (!is_uploaded_file((string) $file['tmp_name'])) {
        return [false, 'The uploaded file could not be verified.'];
    }

    if ((int) $file['size'] <= 0) {
        return [false, 'The uploaded file is empty.'];
    }

    if ((int) $file['size'] > $max_bytes) {
        return [false, 'Image too large. Maximum size is ' . round($max_bytes / 1048576) . 'MB.'];
    }

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_exts, true)) {
        return [false, 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp.'];
    }

    $info = @getimagesize((string) $file['tmp_name']);

    if ($info === false || !isset($info['mime'], $info[0], $info[1])) {
        return [false, 'The file is not a valid image.'];
    }

    if (!in_array((string) $info['mime'], $allowed_mimes, true)) {
        return [false, 'The file is not a supported image.'];
    }

    $ext_to_mime = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    if (($ext_to_mime[$ext] ?? null) !== $info['mime']) {
        return [false, 'The file extension does not match its real image type.'];
    }

    if ((int) $info[0] < 1 || (int) $info[1] < 1) {
        return [false, 'The image has invalid dimensions.'];
    }

    if ((int) $info[0] > $max_dimension || (int) $info[1] > $max_dimension) {
        return [false, 'Image dimensions are too large.'];
    }

    return [true, null];
}

function uploaded_image_extension(array $file): ?string
{
    $info = @getimagesize((string) ($file['tmp_name'] ?? ''));

    if ($info === false || !isset($info['mime'])) {
        return null;
    }

    return match ((string) $info['mime']) {
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        default => null,
    };
}
