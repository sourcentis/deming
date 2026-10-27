<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Configuration key => config/mail.php path (same list as App\Services\MailSettings::KEYS)
    private const KEYS = [
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

    private const ENCRYPTED = ['mail.password', 'mail.dkim_passphrase'];

    /**
     * Copy the SMTP settings currently in effect (from .env) into the configuration table.
     */
    public function up(): void
    {
        foreach (self::KEYS as $name => $path) {
            if (DB::table('configuration')->where('name', $name)->exists()) {
                continue;
            }

            $value = config($path);
            $value = is_bool($value) ? ($value ? '1' : '0') : ($value === null ? null : (string) $value);
            if ($value !== null && $value !== '' && in_array($name, self::ENCRYPTED, true)) {
                $value = Crypt::encryptString($value);
            }

            DB::table('configuration')->insert([
                'name' => $name,
                'value' => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations: the settings come from .env again.
     */
    public function down(): void
    {
        DB::table('configuration')->whereIn('name', array_keys(self::KEYS))->delete();
    }
};
