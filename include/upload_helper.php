<?php

function validate_uploaded_image(array $file, int $max_bytes = 5242880): array
{
    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if ($file['error'] === UPLOAD_ERR_NO_FILE || empty($file['name'])) {
        return [false, 'No file selected.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [false, 'Upload failed with error code ' . $file['error'] . '.'];
    }
    if ($file['size'] <= 0) {
        return [false, 'The uploaded file is empty.'];
    }
    if ($file['size'] > $max_bytes) {
        return [false, 'Image too large. Maximum size is ' . round($max_bytes / 1048576) . 'MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_exts, true)) {
        return [false, 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp.'];
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !in_array($info['mime'], $allowed_mimes, true)) {
        return [false, 'The file is not a valid image.'];
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

    return [true, null];
}