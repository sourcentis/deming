<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Configuration;
use App\Models\Control;
use App\Services\Ai\AiAgentException;
use App\Services\Ai\AiPrompt;
use App\Services\Ai\AiSubjects;
use App\Services\Ai\LlmClient;
use App\Services\AiAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Configuration of the AI agent (administrators only).
 * Values are stored in the "configuration" table through the Configuration model.
 */
class AiConfigController extends Controller
{
    // controls = security measures, measures = controls (UI naming is inverted)
    private const TABLES = ['controls', 'measures', 'actions', 'risks'];

    public const STRUCTURED_OUTPUTS = ['none', 'json_object', 'json_schema'];

    // Presets only fill the URL field
    public const PRESETS = [
        'sourcentis' => 'https://ai.sourcentis.lu/v1',
        'ovh' => 'https://oai.endpoints.kepler.ai.cloud.ovh.net/v1',
        'openai' => 'https://api.openai.com/v1',
        'mistral' => 'https://api.mistral.ai/v1',
        'ollama' => 'http://localhost:11434/v1',
    ];

    // Data of the "AI" tab of the settings page
    public function tabData(): array
    {
        $key = Configuration::get('ai.api_key');

        $prompts = [];
        foreach (AiSubjects::TYPES as $type => $subject) {
            $custom = AiPrompt::custom($type);
            $prompts[$type] = [
                'label' => trans($subject['label']),
                'value' => $custom ?? AiPrompt::default($type),
                'custom' => $custom !== null,
                'contract' => AiPrompt::contract($type),
            ];
        }

        return [
            'enabled' => Configuration::bool('ai.enabled'),
            'base_url' => Configuration::get('ai.base_url'),
            // The key itself never reaches the view
            'has_key' => $key !== null && $key !== '',
            'model' => Configuration::get('ai.model'),
            'temperature' => Configuration::get('ai.temperature'),
            'max_tokens' => Configuration::get('ai.max_tokens'),
            'timeout' => Configuration::get('ai.timeout'),
            'structured_output' => Configuration::get('ai.structured_output'),
            'output_language' => Configuration::get('ai.output_language'),
            'presets' => self::PRESETS,
            'prompts' => $prompts,
            'prompt_max_length' => AiPrompt::MAX_LENGTH,
        ];
    }

    public function save(Request $request): RedirectResponse
    {
        abort_if(! Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        // While AI is off, the settings fields are disabled in the form, hence not submitted: keep them.
        // Only validated values are written (an excluded field is never stored).
        $validated = $request->validate([
            'base_url' => ['exclude_unless:enabled,1', 'required', 'url', 'max:2048', function ($attribute, $value, $fail) {
                if (! LlmClient::isAllowedUrl($value)) {
                    $fail(trans('ai.config.invalid_url'));
                }
            }],
            'api_key' => 'nullable|string|max:4096',
            'model' => 'exclude_unless:enabled,1|required|string|max:191',
            'temperature' => 'sometimes|required|numeric|min:0|max:2',
            'max_tokens' => 'sometimes|required|integer|min:16|max:200000',
            'timeout' => 'sometimes|required|integer|min:5|max:600',
            'structured_output' => ['sometimes', 'required', Rule::in(self::STRUCTURED_OUTPUTS)],
            'output_language' => ['sometimes', 'required', Rule::in(AiAgentService::OUTPUT_LANGUAGES)],
            'prompts' => 'sometimes|array',
            'prompts.*' => 'nullable|string|max:' . AiPrompt::MAX_LENGTH,
        ]);

        // Only changed values are written, so that the audit log stays meaningful
        $this->update('ai.enabled', $request->boolean('enabled') ? '1' : '0');
        if (isset($validated['base_url'])) {
            $this->update('ai.base_url', rtrim(trim((string) $validated['base_url']), '/'));
        }
        foreach (['model', 'temperature', 'max_tokens', 'timeout', 'structured_output', 'output_language'] as $field) {
            if (isset($validated[$field])) {
                $this->update('ai.' . $field, trim((string) $validated[$field]));
            }
        }

        // An empty key field keeps the current key
        if ($request->boolean('delete_api_key')) {
            Configuration::forget('ai.api_key');
        } elseif (trim((string) $request->input('api_key')) !== '') {
            Configuration::set('ai.api_key', trim($request->input('api_key')));
        }

        // A prompt left empty or equal to the default is not stored: the default applies
        foreach (AiSubjects::types() as $type) {
            if (! array_key_exists($type, $validated['prompts'] ?? [])) {
                continue;
            }
            $value = AiPrompt::normalize((string) $validated['prompts'][$type]);
            if ($value === '' || $value === AiPrompt::default($type)) {
                $this->resetPrompt($type);
            } elseif ($value !== AiPrompt::custom($type)) {
                Configuration::set(AiPrompt::key($type), $value);
            }
        }

        return redirect('/config/parameters?tab=ai')->with('messages', [trans('ai.config.saved')]);
    }

    // Back to the prompt shipped with Deming
    public function reset(string $type): RedirectResponse
    {
        abort_if(! Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $this->resetPrompt($type);

        return redirect('/config/parameters?tab=ai')->with('messages', [trans('ai.config.prompt_reset_done')]);
    }

    /**
     * Minimal chat completion with the URL, key and model typed in the form
     * (saved settings otherwise). Returns the HTTP status, the latency and the
     * model that answered, never the key nor the response body.
     */
    public function test(Request $request, LlmClient $client): JsonResponse
    {
        abort_if(! Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');
        $client = $this->formClient($request, $client);

        $start = microtime(true);
        try {
            $answer = $client->chat([
                ['role' => 'user', 'content' => 'Reply with the single word OK.'],
            ], ['max_tokens' => 16]);
            $status = $answer['status'];
            $model = $answer['model'];
            $error = null;
        } catch (AiAgentException $e) {
            // A cut answer still proves that the provider, the key and the model work
            $status = $e->upstreamStatus;
            $model = null;
            $error = $e->reason === AiAgentException::TRUNCATED ? null : $e;
        }
        $latency = (int) round((microtime(true) - $start) * 1000);

        Log::info('AI connection test', ['status' => $status ?? $error?->reason, 'duration_ms' => $latency]);

        return response()->json([
            'ok' => $error === null,
            'status' => $status,
            'latency_ms' => $latency,
            'model' => $model ?? ($error === null ? Configuration::get('ai.model') : null),
            'error' => $error?->translatedMessage(),
        ]);
    }

    // Identifiers of the models offered by the provider (GET {base_url}/models), with the URL and key of the form
    public function models(Request $request, LlmClient $client): JsonResponse
    {
        abort_if(! Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        try {
            $models = $this->formClient($request, $client)->models();
        } catch (AiAgentException $e) {
            // Generic reason only, never the response body
            $error = $e->translatedMessage() . ' ' . trans('ai.config.models_unavailable');

            return response()->json(['ok' => false, 'models' => [], 'error' => $error]);
        }

        return response()->json(['ok' => true, 'models' => $models, 'error' => null]);
    }

    // Clears the stored analyses of the four tables
    public function purge(): RedirectResponse
    {
        abort_if(! Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $count = 0;
        foreach (self::TABLES as $table) {
            $count += DB::table($table)
                ->whereNotNull('ai_analysis')
                ->update(array_fill_keys(Control::AI_COLUMNS, null));
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

    /**
     * Client using the values typed in the form, not saved yet.
     * The saved key is only sent to the saved URL: changing the URL without
     * typing a key sends no key, so that it never leaves for another host.
     */
    private function formClient(Request $request, LlmClient $client): LlmClient
    {
        $request->validate([
            'base_url' => 'nullable|string|max:2048',
            'api_key' => 'nullable|string|max:4096',
            'model' => 'nullable|string|max:191',
        ]);

        $settings = [];
        $baseUrl = rtrim(trim((string) $request->input('base_url')), '/');
        if ($baseUrl !== '') {
            $typed = trim((string) $request->input('api_key'));
            $saved = rtrim(trim((string) Configuration::get('ai.base_url')), '/');
            $settings['base_url'] = $baseUrl;
            $settings['api_key'] = $typed !== '' ? $typed : ($baseUrl === $saved ? Configuration::get('ai.api_key') : null);
        } elseif (trim((string) $request->input('api_key')) !== '') {
            $settings['api_key'] = trim((string) $request->input('api_key'));
        }
        if (trim((string) $request->input('model')) !== '') {
            $settings['model'] = trim((string) $request->input('model'));
        }

        return $client->with($settings);
    }

    private function resetPrompt(string $type): void
    {
        if (Configuration::isStored(AiPrompt::key($type))) {
            Configuration::forget(AiPrompt::key($type));
        }
    }

    private function update(string $name, string $value): void
    {
        if (Configuration::get($name) !== $value) {
            Configuration::set($name, $value);
        }
    }
}
