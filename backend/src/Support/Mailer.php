<?php

declare(strict_types=1);

namespace App\Support;

final class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $to = filter_var($to, FILTER_VALIDATE_EMAIL);

        if ($to === false) {
            return false;
        }

        $from = Env::get('MAIL_FROM', 'no-reply@mapapsique.orbisconect.com');
        $fromName = self::sanitizeHeader(Env::get('MAIL_FROM_NAME', 'Mapa da Psique'));
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . $fromName . ' <' . self::sanitizeHeader($from) . '>',
            'Reply-To: ' . self::sanitizeHeader($from),
        ];

        return mail((string) $to, self::sanitizeHeader($subject), $body, implode("\r\n", $headers));
    }

    private static function sanitizeHeader(?string $value): string
    {
        return trim(str_replace(["\r", "\n"], '', (string) $value));
    }
}
