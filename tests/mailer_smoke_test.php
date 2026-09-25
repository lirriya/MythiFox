<?php

declare(strict_types=1);

use MythiFox\Mailer\MailerFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$recipient = getenv('MAIL_TEST_RECIPIENT') ?: 'test@mythifox.local';
$result = MailerFactory::fromEnvironment()->send(
    $recipient,
    'MythiFox mail transport test',
    '<h1>MythiFox email is working</h1><p>The SMTP transport delivered this message.</p>',
    'MythiFox email is working. The SMTP transport delivered this message.'
);

if (!$result->sent) {
    fwrite(STDERR, "Mail delivery failed: {$result->errorCode}\n");
    exit(1);
}

fwrite(STDOUT, "Mail delivered to {$recipient}\n");
