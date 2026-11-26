<?php

namespace App\Services;

use App\Models\Configuration;
use App\Services\Ai\AiAgentException;
use App\Services\Ai\AiPrompt;
use App\Services\Ai\AiResponseGuard;
use App\Services\Ai\AiSubjects;
use App\Services\Ai\LlmClient;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Analyses one Deming object with the configured language model.
 *
 * System prompt = editable instructions + locked output contract (AiPrompt);
 * user message = the minimized and masked context of the object.
 *
 * Read-only towards Deming: the only write is its own result, stored in the
 * ai_* columns of the analysed object without touching updated_at nor the audit log.
 */
class AiAgentService
{
    public const OUTPUT_LANGUAGES = ['auto', 'fr', 'en', 'de'];

    public function __construct(private readonly LlmClient $client)
    {
    }

    /**
     * @return array{data: array<string, mixed>, cached: bool, analyzed_at: string, model: string}
     *
     * @throws AiAgentException
     */
    public function analyze(string $type, Model $object, bool $force = false): array
    {
        AiSubjects::get($type);
        $language = self::language();

        $json = AiPrompt::encode(AiSubjects::context($type, $object));
        $system = AiPrompt::system($type, $language);
        $model = trim((string) Configuration::get('ai.model'));
        $hash = self::hash($json, $system, $model, $language);

        $table = $object->getTable();
        $id = (int) $object->getKey();
        $stored = DB::table($table)->where('id', $id)
            ->first(['ai_analysis', 'ai_analysis_at', 'ai_analysis_model', 'ai_analysis_hash']);

        if (! $force && $stored?->ai_analysis !== null && hash_equals((string) $stored->ai_analysis_hash, $hash)) {
            $this->log($type, $id, $stored->ai_analysis_model, 0, null, true, 'ok');

            return [
                'data' => json_decode($stored->ai_analysis, true),
                'cached' => true,
                'analyzed_at' => Carbon::parse($stored->ai_analysis_at)->toIso8601String(),
                'model' => (string) $stored->ai_analysis_model,
            ];
        }

        $start = microtime(true);
        $answer = null;
        try {
            $answer = $this->client->chat([
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => AiPrompt::user($json, Carbon::today()->toDateString())],
            ], [
                'response_format' => AiPrompt::responseFormat($type, (string) Configuration::get('ai.structured_output')),
            ]);
            $guard = new AiResponseGuard();
            $data = $guard->check($guard->extract($answer['content']));
        } catch (AiAgentException $e) {
            $this->log($type, $id, $answer['model'] ?? null, $this->elapsed($start), null, false, $e->reason, $e->upstreamStatus ?? $answer['status'] ?? null);
            throw $e;
        }

        // Saved only once checked; a failure above leaves the previous analysis untouched
        $now = now();
        DB::table($table)->where('id', $id)->update([
            'ai_analysis' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ai_analysis_at' => $now,
            'ai_analysis_model' => mb_substr($answer['model'], 0, 191),
            'ai_analysis_hash' => $hash,
            'ai_analysis_version' => AiPrompt::CONTRACT_VERSION,
        ]);

        $tokens = $answer['usage']['total_tokens'] ?? null;
        $this->log($type, $id, $answer['model'], $this->elapsed($start), is_numeric($tokens) ? (int) $tokens : null, false, 'ok');

        return [
            'data' => $data,
            'cached' => false,
            'analyzed_at' => $now->toIso8601String(),
            'model' => $answer['model'],
        ];
    }

    /**
     * Language of the analysis texts: the configured one, or with "auto"
     * the user's, English by default.
     */
    public static function language(): string
    {
        $configured = (string) Configuration::get('ai.output_language');
        if (array_key_exists($configured, AiPrompt::LANGUAGES)) {
            return $configured;
        }

        $language = Auth::user()?->language ?: app()->getLocale();

        return array_key_exists($language, AiPrompt::LANGUAGES) ? $language : 'en';
    }

    /**
     * Fingerprint of the inputs of an analysis: the object, the effective
     * system prompt (instructions and contract), the model and the language.
     * Today's date is sent but kept out of the hash.
     */
    private static function hash(string $context, string $system, string $model, string $language): string
    {
        return hash('sha256', implode("\n\0", [$context, $system, $model, $language]));
    }

    private function elapsed(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }

    /**
     * Metadata only: never the prompt, the context, the answer, the URL nor the key.
     * upstream_status is the HTTP status returned by the provider, if any.
     */
    private function log(string $type, int $id, ?string $model, int $durationMs, ?int $tokens, bool $cached, string $status, ?int $upstreamStatus = null): void
    {
        Log::info('AI analysis', [
            'type' => $type,
            'id' => $id,
            'model' => $model,
            'duration_ms' => $durationMs,
            'tokens' => $tokens,
            'cached' => $cached,
            'status' => $status,
            'upstream_status' => $upstreamStatus,
        ]);
    }
}
