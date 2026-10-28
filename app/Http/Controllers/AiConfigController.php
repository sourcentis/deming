<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Configuration;
use App\Services\Ai\AiResponseValidator;
use App\Services\Ai\AiSubjects;
use App\Services\AiAgentService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Configuration of the AI agent (administrators only).
 * Values are stored in the "configuration" table through the Configuration model.
 */
class AiConfigController extends Controller
{
    // controls = security measures, measures = controls (UI naming is inverted)
    private const TABLES = ['controls', 'measures', 'actions', 'risks'];

    /**
     * Text generation models of OVHcloud AI Endpoints, as returned by /v1/models on 2026-10-28.
     * Speech, text-to-speech, embedding, image and moderation models are left out, as well as
     * Mistral-Small-3.2-24B-Instruct-2506 and Mistral-Nemo-Instruct-2407 (end of life 2026-11-04).
     * Any other model name can still be typed in the field.
     */
    public const OVH_MODELS = [
        'gpt-oss-120b',
        'gpt-oss-20b',
        'Qwen3.5-397B-A17B',
        'Qwen3.8-27B',
        'Qwen3.6-27B',
        'Qwen3.5-9B',
        'Qwen2.5-VL-72B-Instruct',
        'Meta-Llama-3_3-70B-Instruct',
    ];

    // Data of the "AI" tab of the settings page
    public function tabData(): array
    {
        $prompts = [];
        foreach (AiSubjects::TYPES as $type => $subject) {
            $value = (string) Configuration::get($subject['prompt']);
            $prompts[$type] = [
                'name' => $subject['prompt'],
                'value' => $value,
                'custom' => trim($value) !== trim((string) Configuration::defaultValue($subject['prompt'])),
            ];
        }

        $key = Configuration::get('ai.api_key');

        return [
            'enabled' => Configuration::bool('ai.enabled'),
            'base_url' => Configuration::get('ai.base_url'),
            'model' => Configuration::get('ai.model'),
            'models' => self::OVH_MODELS,
            // The key itself never reaches the view
            'has_key' => $key !== null && $key !== '',
            'prompts' => $prompts,
            'schema' => AiResponseValidator::schemaJson(),
        ];
    }

    public function save(Request $request): RedirectResponse
    {
        abort_if(! Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // "Restore the default prompt" button
        $restore = $request->input('restore');
        if ($restore !== null) {
            abort_if(! array_key_exists($restore, AiSubjects::TYPES), Response::HTTP_UNPROCESSABLE_ENTITY);
            Configuration::forget(AiSubjects::TYPES[$restore]['prompt']);

            return redirect('/config/parameters?tab=ai')->with('messages', [trans('ai.config.restored')]);
        }

        $request->validate([
            'base_url' => ['required', 'url', 'max:2048', function ($attribute, $value, $fail) {
                if (! AiAgentService::isAllowedUrl($value)) {
                    $fail(trans('ai.config.invalid_url'));
                }
            }],
            'model' => 'required|string|max:191',
            'api_key' => 'nullable|string|max:4096',
            'prompts' => 'array',
            'prompts.*' => 'nullable|string|max:100000',
        ]);

        // Only changed values are written, so that the audit log stays meaningful
        $this->update('ai.enabled', $request->boolean('enabled') ? '1' : '0');
        $this->update('ai.base_url', rtrim(trim($request->input('base_url')), '/'));
        $this->update('ai.model', trim($request->input('model')));

        // An empty key field keeps the current key
        if ($request->boolean('delete_api_key')) {
            Configuration::forget('ai.api_key');
        } elseif (trim((string) $request->input('api_key')) !== '') {
            Configuration::set('ai.api_key', trim($request->input('api_key')));
        }

        foreach (AiSubjects::TYPES as $type => $subject) {
            $value = $request->input("prompts.{$type}");
            if ($value === null) {
                continue;
            }
            $value = str_replace("\r\n", "\n", $value);
            // Request strings are trimmed by the TrimStrings middleware
            if (trim($value) === '' || trim($value) === trim((string) Configuration::defaultValue($subject['prompt']))) {
                Configuration::forget($subject['prompt']);
            } else {
                $this->update($subject['prompt'], $value);
            }
        }

        return redirect('/config/parameters?tab=ai')->with('messages', [trans('ai.config.saved')]);
    }

    /**
     * Minimal request to the configured agent; returns the HTTP status, the
     * latency and the model, never the key nor the response body.
     */
    public function test(): JsonResponse
    {
        abort_if(! Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $baseUrl = rtrim((string) Configuration::get('ai.base_url'), '/');
        $model = (string) Configuration::get('ai.model');

        if ($baseUrl === '') {
            return response()->json(['ok' => false, 'error' => trans('ai.errors.not_configured')]);
        }
        if (! AiAgentService::isAllowedUrl($baseUrl)) {
            return response()->json(['ok' => false, 'error' => trans('ai.errors.insecure_url')]);
        }

        $payload = [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => 'Réponds OK']],
            'max_tokens' => 200,
        ];
        if (str_starts_with(strtolower($model), 'gpt-oss')) {
            $payload['reasoning_effort'] = 'low';
        }

        $request = Http::timeout(30)->connectTimeout(15)->acceptJson()->withoutRedirecting();
        $key = Configuration::get('ai.api_key');
        if ($key !== null && $key !== '') {
            $request = $request->withToken($key);
        }

        $start = microtime(true);
        try {
            $response = $request->post($baseUrl . '/chat/completions', $payload);
        } catch (ConnectionException) {
            Log::info('AI connection test', ['status' => 'unreachable']);

            return response()->json([
                'ok' => false,
                'status' => null,
                'latency_ms' => (int) round((microtime(true) - $start) * 1000),
                'model' => $model,
                'error' => trans('ai.errors.unreachable'),
            ]);
        }
        $latency = (int) round((microtime(true) - $start) * 1000);
        $ok = $response->successful() && ! $response->redirect();

        Log::info('AI connection test', ['status' => $response->status(), 'duration_ms' => $latency]);

        return response()->json([
            'ok' => $ok,
            'status' => $response->status(),
            'latency_ms' => $latency,
            'model' => is_string($response->json('model')) ? $response->json('model') : $model,
            'error' => $ok ? null : trans('ai.errors.unreachable'),
        ]);
    }

    // Clears the stored analyses of the four tables
    public function purge(): RedirectResponse
    {
        abort_if(! Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $count = 0;
        foreach (self::TABLES as $table) {
            $count += DB::table($table)
                ->whereNotNull('ai_analysis')
                ->update(array_fill_keys(['ai_analysis', 'ai_analysis_at', 'ai_analysis_model', 'ai_analysis_hash'], null));
        }

        // Who and when, without any content
        AuditLog::query()->create([
            'description' => 'purge AI analyses',
            'subject_id' => null,
            'subject_type' => Configuration::class,
            'user_id' => Auth::id(),
            'properties' => ['count' => $count],
            'host' => request()->ip(),
        ]);

        return redirect('/config/parameters?tab=ai')->with('messages', [trans('ai.config.purged', ['count' => $count])]);
    }

    private function update(string $name, string $value): void
    {
        if (Configuration::get($name) !== $value) {
            Configuration::set($name, $value);
        }
    }
}
