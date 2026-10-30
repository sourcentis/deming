<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Generic key/value configuration stored in the "configuration" table.
 *
 * The static methods below are the only way to read or write this table.
 * Not to be confused with ConfigurationController, which manages the
 * notification settings stored in config/deming.php.
 */
class Configuration extends Model
{
    public $table = 'configuration';

    protected $fillable = ['name', 'value'];

    // Keys encrypted at rest with the application key
    public const ENCRYPTED = ['ai.api_key', 'mail.password', 'mail.dkim_passphrase'];

    // Defaults used when the row is absent
    public const DEFAULTS = [
        'ai.enabled' => '0',
        'ai.model' => 'sourcentis-standard',
        'ai.api_key' => null,
    ];

    // Defaults read from versioned files (relative to resources/)
    public const DEFAULT_FILES = [
        'ai.prompt_control' => 'ai/prompt_control.md',
        'ai.prompt_measure' => 'ai/prompt_measure.md',
        'ai.prompt_action' => 'ai/prompt_action.md',
        'ai.prompt_risk' => 'ai/prompt_risk.md',
    ];

    // Values loaded once per request
    private static ?array $cache = null;

    public static function get(string $name, mixed $default = null): ?string
    {
        $values = self::values();

        if (array_key_exists($name, $values)) {
            $value = $values[$name];
            if ($value !== null && in_array($name, self::ENCRYPTED, true)) {
                return self::decrypt($name, $value);
            }
            return $value;
        }

        $fallback = self::defaultValue($name);
        if ($fallback !== null) {
            return $fallback;
        }

        return $default === null ? null : (string) $default;
    }

    // Whether the key is stored in the table (even with a null value)
    public static function isStored(string $name): bool
    {
        return array_key_exists($name, self::values());
    }

    // AI agent available (AI_ACTIVATED in .env) and enabled in the settings
    public static function aiEnabled(): bool
    {
        return self::aiActivated() && self::bool('ai.enabled');
    }

    // AI agent available in this installation (AI_ACTIVATED in .env)
    public static function aiActivated(): bool
    {
        return (bool) config('app.ai_activated');
    }

    public static function bool(string $name, bool $default = false): bool
    {
        $value = self::get($name);
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public static function set(string $name, ?string $value): void
    {
        $stored = ($value !== null && in_array($name, self::ENCRYPTED, true))
            ? Crypt::encryptString($value)
            : $value;

        $exists = DB::table('configuration')->where('name', $name)->exists();
        if ($exists) {
            DB::table('configuration')->where('name', $name)
                ->update(['value' => $stored, 'updated_at' => now()]);
        } else {
            DB::table('configuration')->insert([
                'name' => $name,
                'value' => $stored,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        self::clearCache();
        self::audit($exists ? 'updated' : 'created', $name, $value);
    }

    public static function forget(string $name): void
    {
        $deleted = DB::table('configuration')->where('name', $name)->delete();

        self::clearCache();
        if ($deleted > 0) {
            self::audit('deleted', $name, null);
        }
    }

    // Default value of a key, from DEFAULTS or from its versioned file
    public static function defaultValue(string $name): ?string
    {
        if (array_key_exists($name, self::DEFAULT_FILES)) {
            $path = resource_path(self::DEFAULT_FILES[$name]);
            return is_readable($path) ? file_get_contents($path) : null;
        }

        return self::DEFAULTS[$name] ?? null;
    }

    public static function clearCache(): void
    {
        self::$cache = null;
    }

    private static function values(): array
    {
        if (self::$cache === null) {
            self::$cache = DB::table('configuration')->pluck('value', 'name')->all();
        }

        return self::$cache;
    }

    private static function decrypt(string $name, string $value): ?string
    {
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            // Never log the value itself
            Log::warning('Configuration: unable to decrypt value', ['name' => $name]);
            return null;
        }
    }

    // Audit trail entry, with secret values masked
    private static function audit(string $description, string $name, ?string $value): void
    {
        if ($value !== null && in_array($name, self::ENCRYPTED, true)) {
            $value = '***';
        }

        AuditLog::query()->create([
            'description' => $description,
            'subject_id' => null,
            'subject_type' => self::class,
            'user_id' => Auth::id(),
            'properties' => [
                'name' => $name,
                // Bounded to fit the audit "properties" text column once JSON-encoded
                'value' => $value === null ? null : mb_strcut($value, 0, 10000),
            ],
            'host' => request()->ip(),
        ]);
    }
}
