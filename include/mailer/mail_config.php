<?php

declare(strict_types=1);

namespace MythiFox\Mailer;

function mail_config(): array
{
    return [
        'transport' => getenv('MAIL_TRANSPORT') ?: 'smtp',
        'host' => getenv('MAIL_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('MAIL_PORT') ?: '1025'),
        'encryption' => getenv('MAIL_ENCRYPTION') ?: '',
        'username' => getenv('MAIL_USERNAME') ?: '',
        'password' => getenv('MAIL_PASSWORD') ?: '',
        'from_address' => getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@mythifox.local',
        'from_name' => getenv('MAIL_FROM_NAME') ?: 'MythiFox',
        'timeout' => (int) (getenv('MAIL_TIMEOUT') ?: '10'),
        'app_url' => rtrim(getenv('APP_URL') ?: 'http://localhost/MythiFox', '/'),
    ];
}
