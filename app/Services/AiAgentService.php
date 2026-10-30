<?php

namespace App\Services;

use App\Models\Configuration;
use App\Services\Ai\AiAgentException;
use App\Services\Ai\AiResponseValidator;
use App\Services\Ai\AiSubjects;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Calls an OpenAI-compatible agent (/chat/completions) to analyse one Deming object.
 *
 * Read-only towards Deming: the only write is its own result, stored in the
 * ai_* columns of the analysed object without touching updated_at nor the audit log.
 */
class AiAgentService
{
    private const LANGUAGES = ['fr' => 'français', 'en' => 'English', 'de' => 'Deutsch'];

    private const TIMEOUT = 180;
    private const CONNECT_TIMEOUT = 15;
    // Includes the reasoning tokens of gpt-oss models
    private const MAX_TOKENS = 8000;
    private const TEMPERATURE = 0.2;

    /**
     * @return array{data: array<string, mixed>, cached: bool, analyzed_at: string, model: string}
     *
     * @throws AiAgentException
     */
    public function analyze(string $type, Model $object, bool $force = false): array
    {
        $subject = AiSubjects::get($type);
        $model = (string) Configuration::get('ai.model');
        $baseUrl = rtrim((string) Configuration::get('ai.base_url'), '/');

        // Inputs of the analysis; today's date is sent but kept out of the hash
        $context = json_encode(AiSubjects::context($type, $object), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $system = $this->systemPrompt($subject['prompt']);
        $hash = hash('sha256', $context . "\n" . $system . "\n" . $model);

        $table = $object->getTable();
        $id = (int) $object->getKey();
        $stored = DB::table($table)->where('id', $id)->first(['ai_analysis', 'ai_analysis_at', 'ai_analysis_model', 'ai_analysis_hash']);

        if (! $force && $stored?->ai_analysis !== null && hash_equals((string) $stored->ai_analysis_hash, $hash)) {
            $this->log($type, $id, $stored->ai_analysis_model, 0, null, true, 'ok');

            return [
                'data' => json_decode($stored->ai_analysis, true),
                'cached' => true,
                'analyzed_at' => Carbon::parse($stored->ai_analysis_at)->toIso8601String(),
                'model' => (string) $stored->ai_analysis_model,
            ];
        }

        // No default agent: the URL must be configured by an administrator
        $error = $baseUrl === '' ? AiAgentException::NOT_CONFIGURED
            : (self::isAllowedUrl($baseUrl) ? null : AiAgentException::INSECURE_URL);
        if ($error !== null) {
            $this->log($type, $id, $model, 0, null, false, $error);
            throw new AiAgentException($error);
        }

        $start = microtime(true);
        try {
            [$data, $tokens] = $this->call($baseUrl, $model, $system, $subject['tag'], $context);
        } catch (AiAgentException $e) {
            $this->log($type, $id, $model, $this->elapsed($start), null, false, $e->reason);
            throw $e;
        }

        // Saved only once validated; a failure above leaves the previous analysis untouched
        $now = now();
        DB::table($table)->where('id', $id)->update([
            'ai_analysis' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ai_analysis_at' => $now,
            'ai_analysis_model' => $model,
            'ai_analysis_hash' => $hash,
        ]);

        $this->log($type, $id, $model, $this->elapsed($start), $tokens, false, 'ok');

        return [
            'data' => $data,
            'cached' => false,
            'analyzed_at' => $now->toIso8601String(),
            'model' => $model,
        ];
    }

    /**
     * https is mandatory, except for a local model on localhost / 127.0.0.1.
     */
    public static function isAllowedUrl(?string $url): bool
    {
        $parts = parse_url((string) $url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower($parts['host']);

        return $scheme === 'https'
            || ($scheme === 'http' && in_array($host, ['localhost', '127.0.0.1'], true));
    }

    // Prompt of the type, in the user's language, followed by the fixed output schema
    private function systemPrompt(string $promptKey): string
    {
        $language = Auth::user()?->language ?: app()->getLocale();
        $prompt = str_replace('{{langue}}', self::LANGUAGES[$language] ?? self::LANGUAGES['en'], (string) Configuration::get($promptKey));

        return rtrim($prompt) . "\n\n" . AiResponseValidator::schemaJson();
    }

    /**
     * @return array{0: array<string, mixed>, 1: int|null} validated data and total tokens
     *
     * @throws AiAgentException
     */
    private function call(string $baseUrl, string $model, string $system, string $tag, string $context): array
    {
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => 'Date du jour : ' . Carbon::today()->toDateString()
                    . "\n\n<{$tag}>\n{$context}\n</{$tag}>"],
            ],
            // Not a strict json_schema: some providers reject it with gpt-oss-120b
            'response_format' => ['type' => 'json_object'],
            'max_tokens' => self::MAX_TOKENS,
            'temperature' => self::TEMPERATURE,
        ];
        if (str_starts_with(strtolower($model), 'gpt-oss')) {
            $payload['reasoning_effort'] = 'low';
        }

        // Redirections are never followed: data must not leave for an unconfigured host
        $request = Http::timeout(self::TIMEOUT)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->acceptJson()
            ->withoutRedirecting();

        $key = Configuration::get('ai.api_key');
        if ($key !== null && $key !== '') {
            $request = $request->withToken($key);
        }

        try {
            $response = $request->post($baseUrl . '/chat/completions', $payload);
        } catch (ConnectionException $e) {
            $timeout = str_contains($e->getMessage(), 'cURL error 28') || stripos($e->getMessage(), 'timed out') !== false;
            throw $timeout
                ? new AiAgentException(AiAgentException::TIMEOUT, 504)
                : new AiAgentException(AiAgentException::UNREACHABLE);
        }

        if ($response->redirect() || ! $response->successful()) {
            throw new AiAgentException(AiAgentException::UNREACHABLE);
        }

        $choice = $response->json('choices.0');
        $content = $choice['message']['content'] ?? null;
        if (($choice['finish_reason'] ?? null) !== 'stop' || ! is_string($content) || trim($content) === '') {
            throw new AiAgentException(AiAgentException::TRUNCATED);
        }

        $data = (new AiResponseValidator())->validate($this->decode($content));
        $tokens = $response->json('usage.total_tokens');

        return [$data, is_numeric($tokens) ? (int) $tokens : null];
    }

    // JSON answer, possibly surrounded by text: keep the outermost {...}
    private function decode(string $content): mixed
    {
        $data = json_decode($content, true);
        if (is_array($data)) {
            return $data;
        }

        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start === false || $end === false || $end < $start) {
            throw new AiAgentException(AiAgentException::INVALID_RESPONSE);
        }

        $data = json_decode(substr($content, $start, $end - $start + 1), true);
        if (! is_array($data)) {
            throw new AiAgentException(AiAgentException::INVALID_RESPONSE);
        }

        return $data;
    }

    private function elapsed(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }

    // Metadata only: never the prompt, the context, the answer nor the key
    private function log(string $type, int $id, ?string $model, int $durationMs, ?int $tokens, bool $cached, string $status): void
    {
        Log::info('AI analysis', [
            'type' => $type,
            'id' => $id,
            'model' => $model,
            'duration_ms' => $durationMs,
            'tokens' => $tokens,
            'cached' => $cached,
            'status' => $status,
        ]);
    }
}
