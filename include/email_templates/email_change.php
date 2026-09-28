<?php

declare(strict_types=1);

function email_change_template(string $username, string $verificationUrl): array
{
    $safeUsername = htmlspecialchars($username, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeUrl = htmlspecialchars($verificationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $plainUsername = trim($username);

    $html = '<!doctype html><html><body style="background:#f4eaf0;color:#2e1114;font-family:Arial,sans-serif;padding:32px;">'
        . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px;">'
        . '<h1 style="margin-top:0;color:#501b1d;">Confirm your new MythiFox email</h1>'
        . '<p>Hello ' . $safeUsername . ',</p>'
        . '<p>Use the link below to confirm the new email address for your MythiFox profile.</p>'
        . '<p><a href="' . $safeUrl . '" style="display:inline-block;background:#7e3754;color:#ffffff;padding:12px 20px;border-radius:999px;text-decoration:none;">Confirm new email</a></p>'
        . '<p>If the button does not work, copy this address into your browser:</p>'
        . '<p><a href="' . $safeUrl . '">' . $safeUrl . '</a></p>'
        . '<p style="color:#6b4a55;">Your current email stays active until this link is confirmed. This link expires soon.</p>'
        . '</div></body></html>';

    $text = "Confirm your new MythiFox email\n\n"
        . "Hello {$plainUsername},\n\n"
        . "Confirm the new email address for your MythiFox profile using this link:\n"
        . $verificationUrl . "\n\n"
        . 'Your current email stays active until this link is confirmed. This link expires soon.';

    return [$html, $text];
}
