<?php

declare(strict_types=1);

namespace MythiFox\Mailer;

use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

final class SmtpMailer implements MailerInterface
{
    public function __construct(private readonly array $config)
    {
    }

    public function send(
        string $recipient,
        string $subject,
        string $htmlBody,
        ?string $textBody = null
    ): MailResult {
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return MailResult::failure('invalid_recipient');
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $this->config['host'];
        $mail->Port = $this->config['port'];
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->Timeout = $this->config['timeout'];

        $username = $this->config['username'];
        $mail->SMTPAuth = $username !== '';
        $mail->Username = $username;
        $mail->Password = $this->config['password'];
        $mail->SMTPSecure = $this->config['encryption'];
        $mail->SMTPAutoTLS = $this->config['encryption'] === 'tls';

        $mail->setFrom(
            $this->config['from_address'],
            $this->config['from_name']
        );
        $mail->addAddress($recipient);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $htmlBody;
        $mail->AltBody = $textBody ?? strip_tags($htmlBody);

        try {
            if (!$mail->send()) {
                error_log('MythiFox mail delivery failed: smtp_send_returned_false');

                return MailResult::failure('smtp_delivery_failed');
            }
        } catch (Throwable $exception) {
            error_log('MythiFox mail delivery failed: ' . $exception::class);

            return MailResult::failure('smtp_delivery_failed');
        }

        return MailResult::success();
    }
}
