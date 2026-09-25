<?php

declare(strict_types=1);

namespace MythiFox\Mailer;

use InvalidArgumentException;

final class MailerFactory
{
    public static function fromEnvironment(): MailerInterface
    {
        require_once __DIR__ . '/mail_config.php';

        $config = mail_config();

        if ($config['transport'] !== 'smtp') {
            throw new InvalidArgumentException('Unsupported mail transport.');
        }

        if ($config['host'] === '') {
            throw new InvalidArgumentException('Mail host is required.');
        }

        if ($config['port'] < 1 || $config['port'] > 65535) {
            throw new InvalidArgumentException('Mail port is invalid.');
        }

        if (!filter_var($config['from_address'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Mail sender address is invalid.');
        }

        return new SmtpMailer($config);
    }
}
