<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;

/**
 * Masks personal data in free-text fields before they are sent to the AI agent.
 *
 * This is a best-effort filter, NOT an anonymization: a name typed in free
 * text that does not belong to a Deming user (patient, customer...) is kept.
 */
class PersonalDataRedactor
{
    // Deming user names, logins and e-mails shorter than this are ignored (false positives)
    private const MIN_TOKEN_LENGTH = 3;

    // Keeps each compiled pattern well below the PCRE size limit
    private const TOKENS_PER_PATTERN = 500;

    // [pattern, replacement], applied in this order
    private const PATTERNS = [
        // E-mail addresses
        ['/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[email]'],
        // IBAN (grouped by 4 or compact)
        ['/\b[A-Z]{2}\d{2}(?: ?[A-Z0-9]{4}){2,7}(?: ?[A-Z0-9]{1,3})?\b/', '[iban]'],
        // IPv4
        ['/(?<![\d.])(?:(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)\.){3}(?:25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(?:\/\d{1,2})?(?![\d.])/', '[ip]'],
        // IPv6 (full form, or compressed with "::")
        ['/(?<![\w:])(?:(?:[0-9a-f]{1,4}:){7}[0-9a-f]{1,4}|(?:[0-9a-f]{1,4}:){1,6}:(?:[0-9a-f]{1,4}(?::[0-9a-f]{1,4}){0,5})?|::(?:[0-9a-f]{1,4}(?::[0-9a-f]{1,4}){0,6})?)(?:\/\d{1,3})?(?![\w:])/i', '[ip]'],
        // Phone numbers: international, then national FR, BE / DE / LU formats
        ['/(?<![\w+])(?:'
            . '(?:\+|00)\d{1,3}[\s.\-\/]?(?:\(0\)[\s.\-\/]?)?\d{1,4}(?:[\s.\-\/]?\d{2,4}){1,5}'
            . '|0[1-9](?:[\s.\-]?\d{2}){4}'
            . '|0\d{1,3}[\s.\-\/]?\d{2,3}(?:[\s.\-]?\d{2}){2}'
            . '|0\d{2,4}[\s\/\-]?\d{4,8}'
            . '|6\d{2}[\s.]?\d{3}[\s.]?\d{3}'
            . ')(?!\w)/u', '[téléphone]'],
    ];

    /** @var array<int, string>|null compiled patterns for Deming users, built once */
    private ?array $userPatterns = null;

    public function redact(string $text): string
    {
        foreach (self::PATTERNS as [$pattern, $replacement]) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        foreach ($this->userPatterns() as $pattern) {
            $text = preg_replace($pattern, '[utilisateur]', $text) ?? $text;
        }

        return $text;
    }

    /**
     * Recursively masks every string of a context array.
     *
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    public function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->redact($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->redactArray($value);
            }
        }

        return $data;
    }

    /** @return array<int, string> */
    private function userPatterns(): array
    {
        if ($this->userPatterns !== null) {
            return $this->userPatterns;
        }

        $tokens = [];
        foreach (DB::table('users')->select('name', 'login', 'email')->get() as $user) {
            foreach ([$user->name, $user->login, $user->email] as $token) {
                $token = trim((string) $token);
                if (mb_strlen($token) >= self::MIN_TOKEN_LENGTH) {
                    $tokens[mb_strtolower($token)] = $token;
                }
            }
        }

        // Longest first, so that "Jean Dupont" wins over "Jean"
        $tokens = array_values($tokens);
        usort($tokens, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        $this->userPatterns = [];
        foreach (array_chunk($tokens, self::TOKENS_PER_PATTERN) as $chunk) {
            $quoted = array_map(fn ($t) => preg_quote($t, '/'), $chunk);
            // Whole words only, case-insensitive
            $this->userPatterns[] = '/(?<![\p{L}\p{N}_])(?:' . implode('|', $quoted) . ')(?![\p{L}\p{N}_])/iu';
        }

        return $this->userPatterns;
    }
}
