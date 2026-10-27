<?php

namespace App\Services;

use App\Models\Configuration;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * SMTP settings used to send mails (notifications and test mails).
 *
 * They are stored in the "configuration" table; a key absent from the table
 * falls back to config/mail.php (.env), as before.
 */
class MailSettings
{
    // Configuration key => config/mail.php path
    public const KEYS = [
        'mail.host' => 'mail.mailers.smtp.host',
        'mail.port' => 'mail.mailers.smtp.port',
        'mail.encryption' => 'mail.mailers.smtp.encryption',
        'mail.auth' => 'mail.mailers.smtp.auth',
        'mail.username' => 'mail.mailers.smtp.username',
        'mail.password' => 'mail.mailers.smtp.password',
        'mail.auto_tls' => 'mail.mailers.smtp.auto_tls',
        'mail.dkim_domain' => 'mail.mailers.dkim.domain',
        'mail.dkim_private' => 'mail.mailers.dkim.private',
        'mail.dkim_selector' => 'mail.mailers.dkim.selector',
        'mail.dkim_passphrase' => 'mail.mailers.dkim.passphrase',
    ];

    public const ENCRYPTIONS = ['', 'ssl', 'tls'];

    /** @return array<string, string|null> */
    public static function all(): array
    {
        $settings = [];
        foreach (self::KEYS as $name => $path) {
            $settings[$name] = Configuration::isStored($name)
                ? Configuration::get($name)
                : self::toString(config($path));
        }

        return $settings;
    }

    // String form of a config value, as stored in the configuration table
    public static function toString(mixed $value): ?string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return $value === null ? null : (string) $value;
    }

    /**
     * PHPMailer ready to send through the SMTP server.
     *
     * @param  array<string, string|null>|null  $settings  defaults to the saved settings
     */
    public static function mailer(?array $settings = null): PHPMailer
    {
        $settings ??= self::all();

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = (string) $settings['mail.host'];
        $mail->Port = (int) $settings['mail.port'];
        $mail->SMTPAuth = filter_var($settings['mail.auth'], FILTER_VALIDATE_BOOLEAN);
        $mail->Username = (string) $settings['mail.username'];
        $mail->Password = (string) $settings['mail.password'];
        // '', 'ssl' or 'tls'
        $mail->SMTPSecure = (string) $settings['mail.encryption'];
        $mail->SMTPAutoTLS = filter_var($settings['mail.auto_tls'], FILTER_VALIDATE_BOOLEAN);
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';

        // Optional DKIM signing
        if (($settings['mail.dkim_domain'] ?? '') !== '') {
            $mail->DKIM_domain = (string) $settings['mail.dkim_domain'];
            $mail->DKIM_private = (string) $settings['mail.dkim_private'];
            $mail->DKIM_selector = (string) $settings['mail.dkim_selector'];
            $mail->DKIM_passphrase = (string) $settings['mail.dkim_passphrase'];
        }

        return $mail;
    }
}
