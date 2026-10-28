<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Services\MailSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use PHPMailer\PHPMailer\Exception;

class ConfigurationController extends Controller
{
    // SMTP form field => configuration key
    private const SMTP_FIELDS = [
        'smtp_host' => 'mail.host',
        'smtp_port' => 'mail.port',
        'smtp_encryption' => 'mail.encryption',
        'smtp_username' => 'mail.username',
        'dkim_domain' => 'mail.dkim_domain',
        'dkim_private' => 'mail.dkim_private',
        'dkim_selector' => 'mail.dkim_selector',
    ];

    private const SMTP_BOOLEANS = [
        'smtp_auth' => 'mail.auth',
        'smtp_auto_tls' => 'mail.auto_tls',
    ];

    // Write-only fields: never displayed, an empty field keeps the stored value
    private const SMTP_SECRETS = [
        'smtp_password' => 'mail.password',
        'dkim_passphrase' => 'mail.dkim_passphrase',
    ];

    /*
    * Data of the "Notifications" tab of the settings page
    */
    public function tabData(): array
    {
        return [
            // Defaults until the notifications are saved for the first time
            'mail_from' => config('deming.notification.mail-from') ?: $this->defaultMailFrom(),
            'mail_subject' => config('deming.notification.mail-subject') ?: trans('cruds.config.notifications.default_subject'),
            'mail_content' => config('deming.notification.mail-content'),
            'frequency' => config('deming.notification.frequency'),
            'expire_delay' => config('deming.notification.expire-delay'),
            'reminder' => config('deming.notification.reminder'),
            'smtp' => $this->smtpView(MailSettings::all()),
        ];
    }

    // MAIL_FROM_ADDRESS when set, otherwise a sample address to adapt
    private function defaultMailFrom(): string
    {
        $address = config('mail.from.address');
        if (is_string($address) && $address !== '' && $address !== 'hello@example.com') {
            return $address;
        }

        return 'deming@domain.local';
    }

    /**
     * SMTP settings as shown in the form: secrets are replaced by a "stored" flag.
     *
     * @param  array<string, string|null>  $settings
     * @return array<string, mixed>
     */
    private function smtpView(array $settings): array
    {
        $view = [];
        foreach (self::SMTP_FIELDS + self::SMTP_BOOLEANS as $field => $name) {
            $view[$field] = $settings[$name];
        }
        foreach (self::SMTP_SECRETS as $field => $name) {
            $view["has_{$field}"] = ($settings[$name] ?? '') !== '';
        }

        return $view;
    }

    /**
     * Saved SMTP settings overridden by the values of the form.
     *
     * @return array<string, string|null>
     */
    private function smtpSettings(Request $request): array
    {
        $settings = MailSettings::all();
        // Request without the SMTP section of the form: nothing to override
        if (! $request->has('smtp_host')) {
            return $settings;
        }
        foreach (self::SMTP_FIELDS as $field => $name) {
            $value = $request->input($field);
            $settings[$name] = ($value === null || $value === '') ? null : (string) $value;
        }
        foreach (self::SMTP_BOOLEANS as $field => $name) {
            $settings[$name] = $request->boolean($field) ? '1' : '0';
        }
        foreach (self::SMTP_SECRETS as $field => $name) {
            if ($request->boolean("{$field}_delete")) {
                $settings[$name] = null;
            } elseif ((string) $request->input($field) !== '') {
                $settings[$name] = (string) $request->input($field);
            }
        }

        return $settings;
    }

    /*
    * Save the configuration
    */
    public function save(Request $request)
    {
        abort_if(Auth::user()->role !== 1, Response::HTTP_FORBIDDEN, '403 Forbidden');

        // Error and message variables
        $errors = Collect();
        $messages = Collect();

        if (in_array($request->input('action'), ['save', 'test'], true)) {
            $request->validate([
                'smtp_host' => 'nullable|string|max:255',
                'smtp_port' => 'nullable|integer|between:1,65535',
                'smtp_encryption' => ['nullable', Rule::in(MailSettings::ENCRYPTIONS)],
                'smtp_username' => 'nullable|string|max:255',
                'smtp_password' => 'nullable|string|max:1024',
                'dkim_domain' => 'nullable|string|max:255',
                'dkim_private' => 'nullable|string|max:1024',
                'dkim_selector' => 'nullable|string|max:255',
                'dkim_passphrase' => 'nullable|string|max:1024',
            ]);
        }

        // read request
        $mail_from = request('mail_from');
        $mail_subject = request('mail_subject');
        $mail_content = request('mail_content');
        $frequency = request('frequency');
        $expire_delay = request('expire_delay');
        $reminder = request('reminder');

        switch ($request->input('action')) {
            case 'save':
                // put in config file
                config(['deming.notification.mail-from' => $mail_from]);
                config(['deming.notification.mail-subject' => $mail_subject]);
                config(['deming.notification.mail-content' => $mail_content]);
                config(['deming.notification.frequency' => $frequency]);

                config(['deming.notification.expire-delay' => $expire_delay]);
                config(['deming.notification.reminder' => $reminder]);

                // Save configuration
                $text = '<?php return ' . var_export(config('deming'), true) . ';';
                file_put_contents(config_path('deming.php'), $text);

                // SMTP server: only changed values are written, so that the audit log stays meaningful
                foreach ($this->smtpSettings($request) as $name => $value) {
                    if (! Configuration::isStored($name) || Configuration::get($name) !== $value) {
                        Configuration::set($name, $value);
                    }
                }

                // Return
                $messages->push('Configuration saved !');
                break;

            case 'test':
                // Test with the SMTP values of the form, without saving them
                $mail = MailSettings::mailer($this->smtpSettings($request));

                try {
                    // Recipients
                    MailSettings::setFrom($mail, (string) $mail_from);
                    $mail->addAddress(Auth::user()->email);         // Add a recipient

                    // Content
                    $mail->isHTML(true);                            // Set email format to HTML
                    $mail->Subject = $mail_subject;
                    $mail->Body = $mail_content;
                    // $mail->AltBody = 'This is the plain text version of the email body';

                    // Send email
                    $mail->send();

                    $messages->push('Message has been sent.');
                } catch (Exception $e) {
                    $errors->push('Message could not be sent.');
                    $errors->push("Mailer Error: {$mail->ErrorInfo}");
                }

                break;

            case 'cancel':
                return redirect('/');

            default:
                $messages->push('No actions made.');
        }

        // Back to the "Notifications" tab, keeping the values entered (e.g. after a test)
        return redirect('/config/parameters?tab=notifications')
            ->withInput($request->except(array_keys(self::SMTP_SECRETS)))
            ->with('messages', $messages->all())
            ->withErrors($errors->all());
    }
}
