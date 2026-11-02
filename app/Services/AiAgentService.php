<?php

namespace App\Services;

use App\Services\Ai\AiAgentException;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiResponseGuard;
use App\Services\Ai\AiSubjects;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Analyses one Deming object through the task of its type on the AI gateway.
 *
 * Deming only sends the data: the minimized and masked context, the user's
 * language and today's date. The prompt, the response format and the model
 * belong to the gateway.
 *
 * Read-only towards Deming: the only write is its own result, stored in the
 * ai_* columns of the analysed object without touching updated_at nor the audit log.
 */
class AiAgentService
{
    private const LANGUAGES = ['fr', 'en', 'de'];

    public function __construct(private readonly AiGateway $gateway)
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

        $context = AiSubjects::context($type, $object);
        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $table = $object->getTable();
        $id = (int) $object->getKey();
        $stored = DB::table($table)->where('id', $id)
            ->first(['ai_analysis', 'ai_analysis_at', 'ai_analysis_model', 'ai_analysis_hash', 'ai_analysis_version']);

        if (! $force && $stored?->ai_analysis !== null) {
            // Gateway unreachable: the version of the last analysis keeps it displayable
            $version = $this->gateway->taskVersion($type) ?? (string) $stored->ai_analysis_version;
            if (hash_equals((string) $stored->ai_analysis_hash, self::hash($json, $language, $version))) {
                $this->log($type, $id, $stored->ai_analysis_model, $stored->ai_analysis_version, 0, null, true, 'ok');

                return [
                    'data' => json_decode($stored->ai_analysis, true),
                    'cached' => true,
                    'analyzed_at' => Carbon::parse($stored->ai_analysis_at)->toIso8601String(),
                    'model' => (string) $stored->ai_analysis_model,
                ];
            }
        }

        $start = microtime(true);
        try {
            $answer = $this->gateway->run($type, [
                'language' => $language,
                'date' => Carbon::today()->toDateString(),
                'context' => $context,
            ]);
            $data = (new AiResponseGuard())->check($answer['data']);
        } catch (AiAgentException $e) {
            $this->log($type, $id, null, null, $this->elapsed($start), null, false, $e->reason);
            throw $e;
        }

        $model = is_string($answer['model'] ?? null) ? $answer['model'] : '';
        $version = is_string($answer['task_version'] ?? null) ? $answer['task_version'] : '';
        $this->gateway->noteVersion($type, $version);
        if (($answer['schema'] ?? AiResponseGuard::SCHEMA) !== AiResponseGuard::SCHEMA) {
            Log::warning('AI analysis: unexpected response format', [
                'type' => $type,
                'schema' => is_string($answer['schema']) ? mb_substr($answer['schema'], 0, 64) : gettype($answer['schema']),
                'expected' => AiResponseGuard::SCHEMA,
            ]);
        }

        // Saved only once checked; a failure above leaves the previous analysis untouched
        $now = now();
        DB::table($table)->where('id', $id)->update([
            'ai_analysis' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'ai_analysis_at' => $now,
            'ai_analysis_model' => mb_substr($model, 0, 191),
            'ai_analysis_hash' => self::hash($json, $language, $version),
            'ai_analysis_version' => mb_substr($version, 0, 32),
        ]);

        $tokens = $answer['usage']['total_tokens'] ?? null;
        $this->log($type, $id, $model, $version, $this->elapsed($start), is_numeric($tokens) ? (int) $tokens : null, false, 'ok');

        return [
            'data' => $data,
            'cached' => false,
            'analyzed_at' => $now->toIso8601String(),
            'model' => $model,
        ];
    }

    // Language of the analysis texts: the user's, English by default
    public static function language(): string
    {
        $language = Auth::user()?->language ?: app()->getLocale();

        return in_array($language, self::LANGUAGES, true) ? $language : 'en';
    }

    /**
     * Fingerprint of the inputs of an analysis. The prompt, the format and the
     * model belong to the gateway and are covered by the task version.
     * Today's date is sent but kept out of the hash.
     */
    private static function hash(string $context, string $language, string $taskVersion): string
    {
        return hash('sha256', $context . "\n" . $language . "\n" . $taskVersion);
    }

    private function elapsed(float $start): int
    {
        return (int) round((microtime(true) - $start) * 1000);
    }

    // Metadata only: never the context, the answer nor the key
    private function log(string $type, int $id, ?string $model, ?string $taskVersion, int $durationMs, ?int $tokens, bool $cached, string $status): void
    {
        Log::info('AI analysis', [
            'type' => $type,
            'id' => $id,
            'model' => $model,
            'task_version' => $taskVersion,
            'duration_ms' => $durationMs,
            'tokens' => $tokens,
            'cached' => $cached,
            'status' => $status,
        ]);
    }
}
