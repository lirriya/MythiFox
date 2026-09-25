<?php

declare(strict_types=1);

namespace MythiFox\Mailer;

interface MailerInterface
{
    public function send(
        string $recipient,
        string $subject,
        string $htmlBody,
        ?string $textBody = null
    ): MailResult;
}
