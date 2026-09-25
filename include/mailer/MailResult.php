<?php

declare(strict_types=1);

namespace MythiFox\Mailer;

final class MailResult
{
    private function __construct(
        public readonly bool $sent,
        public readonly ?string $errorCode
    ) {
    }

    public static function success(): self
    {
        return new self(true, null);
    }

    public static function failure(string $errorCode): self
    {
        return new self(false, $errorCode);
    }
}
