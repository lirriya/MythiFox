<?php

declare(strict_types=1);

require dirname(__DIR__) . '/include/profile_validation.php';

$failures = [];

$validUsernames = ['abc', 'a_b', 'User_123', str_repeat('a', 20), '___'];
foreach ($validUsernames as $username) {
    if (!profile_username_is_valid($username)) {
        $failures[] = 'should_accept_' . $username;
    }
}

$invalidUsernames = [
    'ab',
    str_repeat('a', 21),
    'has space',
    'hyphen-name',
    'dot.name',
    'emoji😀name',
    "null\0byte",
    'quote"name',
    "semi;colon",
    "back\\slash",
    'slas/h',
    'at@sign',
    'percent%',
    '',
    '   ',
];
foreach ($invalidUsernames as $username) {
    if (profile_username_is_valid($username)) {
        $failures[] = 'should_reject_' . bin2hex($username);
    }
}

if (profile_username_normalize("  spaced  ") !== 'spaced') {
    $failures[] = 'username_normalize';
}

if (profile_username_normalize("  a b  ") !== 'a b') {
    $failures[] = 'username_normalize_preserves_inner_spaces';
}

if (profile_bio_normalize("line1\r\nline2\rline3") !== "line1\nline2\nline3") {
    $failures[] = 'bio_newline_normalize';
}

if (profile_bio_normalize("   padded   ") !== 'padded') {
    $failures[] = 'bio_trim';
}

if (profile_bio_is_valid(str_repeat('a', PROFILE_BIO_MAX_LENGTH))) {
    // expected valid, asserted below for clarity
} else {
    $failures[] = 'bio_accepts_exact_limit';
}

if (profile_bio_is_valid(str_repeat('a', PROFILE_BIO_MAX_LENGTH + 1))) {
    $failures[] = 'bio_rejects_over_limit';
}

if (profile_bio_is_valid('')) {
    // empty bio is valid
} else {
    $failures[] = 'bio_accepts_empty';
}

if (PROFILE_BIO_MAX_LENGTH !== 500) {
    $failures[] = 'bio_limit_is_500';
}

if (profile_request_string(['name' => 'value'], 'name') !== 'value') {
    $failures[] = 'request_string_reads_string';
}

if (profile_request_string(['name' => ['a', 'b']], 'name') !== '') {
    $failures[] = 'request_string_rejects_array';
}

if (profile_request_string(['name' => 42], 'name') !== '') {
    $failures[] = 'request_string_rejects_int';
}

if (profile_request_string(['name' => ['x' => 'y']], 'name') !== '') {
    $failures[] = 'request_string_rejects_associative_array';
}

if (profile_request_string([], 'missing') !== '') {
    $failures[] = 'request_string_missing_key';
}

if ($failures !== []) {
    fwrite(STDERR, 'Profile validation tests failed: ' . implode(', ', $failures) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Profile validation tests passed\n");
