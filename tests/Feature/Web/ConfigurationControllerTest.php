<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user  = User::factory()->user()->create();
});

test('guest is redirected to login', function () {
    $this->get('/config')->assertRedirect('/login');
});

test('the former URL opens the notifications tab of the settings page', function () {
    $this->actingAs($this->admin)->get('/config')->assertRedirect('/config/parameters?tab=notifications');
});

test('admin can view configuration', function () {
    $this->actingAs($this->admin)->get('/config/parameters?tab=notifications')
        ->assertStatus(200)
        ->assertSee('action="/config/save"', false);
});

test('non-admin cannot view configuration', function () {
    $this->actingAs($this->user)->get('/config/parameters?tab=notifications')->assertStatus(403);
});

test('admin can save configuration and goes back to the notifications tab', function () {
    $this->actingAs($this->admin)
        ->post('/config/save', ['action' => 'save'])
        ->assertRedirect('/config/parameters?tab=notifications');
});

test('admin config save with cancel redirects to home', function () {
    $this->actingAs($this->admin)
        ->post('/config/save', ['action' => 'cancel'])
        ->assertRedirect('/');
});

test('non-admin cannot save configuration', function () {
    $this->actingAs($this->user)
        ->post('/config/save', [])
        ->assertStatus(403);
});

// -----------------------------------------------------------------------------
// SMTP server settings, stored in the configuration table
// -----------------------------------------------------------------------------

function smtpPayload(array $overrides = []): array
{
    return array_replace([
        'action' => 'save',
        'mail_from' => 'deming@example.org',
        'mail_subject' => 'Deming',
        'mail_content' => 'Hello',
        'frequency' => '0',
        'expire_delay' => '7',
        'smtp_host' => 'smtp.example.org',
        'smtp_port' => '465',
        'smtp_encryption' => 'ssl',
        'smtp_auth' => '1',
        'smtp_auto_tls' => '0',
        'smtp_username' => 'deming',
        'smtp_password' => '',
        'dkim_domain' => '',
        'dkim_private' => '',
        'dkim_selector' => '',
        'dkim_passphrase' => '',
    ], $overrides);
}

test('the migration copies the SMTP settings of .env into the configuration table', function () {
    foreach (\App\Services\MailSettings::KEYS as $name => $path) {
        expect(\App\Models\Configuration::isStored($name))->toBeTrue();
    }
    expect(\App\Models\Configuration::get('mail.host'))->toBe(\App\Services\MailSettings::toString(config('mail.mailers.smtp.host')));
});

test('a setting absent from the table falls back to config/mail.php', function () {
    \Illuminate\Support\Facades\DB::table('configuration')->where('name', 'mail.host')->delete();
    \App\Models\Configuration::clearCache();
    config(['mail.mailers.smtp.host' => 'from-env.example.org']);

    expect(\App\Services\MailSettings::all()['mail.host'])->toBe('from-env.example.org');
});

test('the notifications tab shows the SMTP settings but never the password', function () {
    \App\Models\Configuration::set('mail.host', 'smtp.example.org');
    \App\Models\Configuration::set('mail.password', 'smtp-secret-pw');

    $this->actingAs($this->admin)->get('/config/parameters?tab=notifications')
        ->assertOk()
        ->assertSee('name="smtp_host"', false)
        ->assertSee('value="smtp.example.org"', false)
        ->assertSee('name="smtp_password"', false)
        ->assertSee(trans('cruds.config.notifications.smtp.secret_saved'))
        ->assertDontSee('smtp-secret-pw');
});

test('admin saves the SMTP settings in the configuration table', function () {
    $this->actingAs($this->admin)
        ->post('/config/save', smtpPayload(['smtp_password' => 'smtp-secret-pw']))
        ->assertRedirect('/config/parameters?tab=notifications');

    \App\Models\Configuration::clearCache();
    $settings = \App\Services\MailSettings::all();
    expect($settings['mail.host'])->toBe('smtp.example.org')
        ->and($settings['mail.port'])->toBe('465')
        ->and($settings['mail.encryption'])->toBe('ssl')
        ->and($settings['mail.auth'])->toBe('1')
        ->and($settings['mail.auto_tls'])->toBe('0')
        ->and($settings['mail.username'])->toBe('deming')
        ->and($settings['mail.password'])->toBe('smtp-secret-pw');

    $raw = \Illuminate\Support\Facades\DB::table('configuration')->where('name', 'mail.password')->value('value');
    expect($raw)->not->toBe('smtp-secret-pw');
});

test('an empty password keeps the stored one and the checkbox deletes it', function () {
    \App\Models\Configuration::set('mail.password', 'smtp-secret-pw');

    $this->actingAs($this->admin)->post('/config/save', smtpPayload());
    \App\Models\Configuration::clearCache();
    expect(\App\Models\Configuration::get('mail.password'))->toBe('smtp-secret-pw');

    $this->actingAs($this->admin)->post('/config/save', smtpPayload(['smtp_password_delete' => '1']));
    \App\Models\Configuration::clearCache();
    expect(\App\Models\Configuration::get('mail.password'))->toBeNull();
});

test('the audit log masks the SMTP password', function () {
    $this->actingAs($this->admin)->post('/config/save', smtpPayload(['smtp_password' => 'smtp-secret-pw']));

    $logs = \App\Models\AuditLog::where('subject_type', \App\Models\Configuration::class)->get()->toJson();
    expect($logs)->toContain('mail.password')->toContain('***')->not->toContain('smtp-secret-pw');
});

test('a save without the SMTP section keeps the SMTP settings', function () {
    \App\Models\Configuration::set('mail.host', 'smtp.example.org');

    $this->actingAs($this->admin)->post('/config/save', ['action' => 'save']);
    \App\Models\Configuration::clearCache();

    expect(\App\Models\Configuration::get('mail.host'))->toBe('smtp.example.org');
});

test('invalid SMTP values are refused', function (array $values) {
    $this->actingAs($this->admin)->from('/config/parameters?tab=notifications')
        ->post('/config/save', smtpPayload($values))
        ->assertSessionHasErrors(array_keys($values));
})->with([
    [['smtp_port' => '70000']],
    [['smtp_encryption' => 'starttls']],
]);

test('a test mail uses the values of the form and never flashes the password', function () {
    // Connection refused at once: nothing listens on port 1
    $this->actingAs($this->admin)
        ->post('/config/save', smtpPayload(['action' => 'test', 'smtp_host' => '127.0.0.1', 'smtp_port' => '1', 'smtp_encryption' => '', 'smtp_password' => 'smtp-secret-pw']))
        ->assertRedirect('/config/parameters?tab=notifications')
        ->assertSessionHasErrors();

    expect(session()->getOldInput('smtp_password'))->toBeNull()
        ->and(session()->getOldInput('smtp_host'))->toBe('127.0.0.1');
    \App\Models\Configuration::clearCache();
    expect(\App\Models\Configuration::get('mail.host'))->not->toBe('127.0.0.1');
});

test('the mailer is configured from the settings', function () {
    $mail = \App\Services\MailSettings::mailer([
        'mail.host' => 'smtp.example.org', 'mail.port' => '465', 'mail.encryption' => 'ssl', 'mail.auth' => '1',
        'mail.username' => 'deming', 'mail.password' => 'pw', 'mail.auto_tls' => '0',
        'mail.dkim_domain' => 'example.org', 'mail.dkim_private' => '/keys/dkim.pem', 'mail.dkim_selector' => 'default', 'mail.dkim_passphrase' => null,
    ]);

    expect($mail->Host)->toBe('smtp.example.org')
        ->and($mail->Port)->toBe(465)
        ->and($mail->SMTPSecure)->toBe('ssl')
        ->and($mail->SMTPAuth)->toBeTrue()
        ->and($mail->SMTPAutoTLS)->toBeFalse()
        ->and($mail->Username)->toBe('deming')
        ->and($mail->Password)->toBe('pw')
        ->and($mail->DKIM_domain)->toBe('example.org')
        ->and($mail->DKIM_selector)->toBe('default');
});
