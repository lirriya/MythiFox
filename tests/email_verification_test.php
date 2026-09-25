<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/include/email_verification.php';

$failures = [];

if (email_verification_normalize('  Student@Example.Test ') !== 'student@example.test') {
    $failures[] = 'normalization';
}

if (!email_verification_is_valid('student@example.test')) {
    $failures[] = 'valid_email';
}

if (email_verification_is_valid('not-an-email')) {
    $failures[] = 'invalid_email';
}

[$html, $text] = email_verification_template(
    '<Admin>',
    'https://example.test/verify?token=abc&next=1'
);

if (str_contains($html, '<Admin>')) {
    $failures[] = 'html_username_escape';
}

if (!str_contains($html, '&lt;Admin&gt;')) {
    $failures[] = 'html_username_escaped_value';
}

if (!str_contains($html, 'token=abc&amp;next=1')) {
    $failures[] = 'html_url_escape';
}

if (!str_contains($text, 'https://example.test/verify?token=abc&next=1')) {
    $failures[] = 'plain_text_url';
}

if ($failures !== []) {
    fwrite(STDERR, 'Email verification tests failed: ' . implode(', ', $failures) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Email verification tests passed\n");
