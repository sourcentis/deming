<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Configuration;
use App\Models\Control;
use App\Services\Ai\AiAgentException;
use App\Services\Ai\AiGateway;
use App\Services\Ai\AiSubjects;
use App\Services\AiAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Configuration of the AI agent (administrators only).
 * Values are stored in the "configuration" table through the Configuration model.
 */
class AiConfigController extends Controller
{
    // controls = security measures, measures = controls (UI naming is inverted)
    private const TABLES = ['controls', 'measures', 'actions', 'risks'];

    // Data of the "AI" tab of the settings page
    public function tabData(): array
    {
        $key = Configuration::get('ai.api_key');
        $enabled = Configuration::bool('ai.enabled');

        return [
            'enabled' => $enabled,
            'base_url' => Configuration::get('ai.base_url'),
            // The key itself never reaches the view
            'has_key' => $key !== null && $key !== '',
            // Read-only: the prompts, formats and models are managed by the gateway
            'tasks' => $enabled ? $this->tasks() : null,
        ];
    }

    public function save(Request $request): RedirectResponse
    {
        abort_if(! Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! Configuration::aiActivated(), Response::HTTP_NOT_FOUND, '404 Not Found');

        // While AI is off, the settings fields are disabled in the form, hence not submitted: keep them
        $request->validate([
            'base_url' => ['exclude_unless:enabled,1', 'required', 'url', 'max:2048', function ($attribute, $value, $fail) {
                if (! AiGateway::isAllowedUrl($value)) {
                    $fail(trans('ai.config.invalid_url'));
                }
            }],
            'api_key' => 'nullable|string|max:4096',
        ]);

        // Only changed values are written, so that the audit log stays meaningful
        $this->update('ai.enabled', $request->boolean('enabled') ? '1' : '0');
        if ($request->has('base_url')) {
            $this->update('ai.base_url', rtrim(trim($request->input('base_url')), '/'));
        }

        // An empty key field keeps the current key
        if ($request->boolean('delete_api_key')) {
            Configuration::forget('ai.api_key');
        } elseif (trim((string) $request->input('api_key')) !== '') {
            Configuration::set('ai.api_key', trim($request->input('api_key')));
        }

        // Another gateway or key may expose other tasks
        AiGateway::forgetTasks();

        return redirect('/config/parameters?tab=ai')->with('messages', [trans('ai.config.saved')]);
    }

    /**
     * Free request to the gateway (GET /tasks): checks the key and the presence
     * of the Deming tasks. Returns the HTTP status, the latency and the number of
     * Deming tasks found, never the key nor the response body.
     */
    public function test(AiGateway $gateway): JsonResponse
    {
        abort_if(! Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! Configuration::aiActivated(), Response::HTTP_NOT_FOUND, '404 Not Found');

        $expected = count(AiSubjects::types());
        $start = microtime(true);
        try {
            $result = $gateway->fetchTasks();
        } catch (AiAgentException $e) {
            $latency = (int) round((microtime(true) - $start) * 1000);
            Log::info('AI connection test', ['status' => $e->upstreamStatus ?? $e->reason, 'duration_ms' => $latency]);

            return response()->json([
                'ok' => false,
                'status' => $e->upstreamStatus,
                'latency_ms' => $latency,
                'tasks_found' => null,
                'tasks_expected' => $expected,
                'error' => $e->translatedMessage(),
            ]);
        }
        $latency = (int) round((microtime(true) - $start) * 1000);
        $found = count(AiGateway::demingTasks($result['tasks']));
        $ok = $found === $expected;

        Log::info('AI connection test', ['status' => $result['response']->status(), 'duration_ms' => $latency, 'tasks' => $found]);

        return response()->json([
            'ok' => $ok,
            'status' => $result['response']->status(),
            'latency_ms' => $latency,
            'tasks_found' => $found,
            'tasks_expected' => $expected,
            'error' => $ok ? null : trans('ai.errors.incompatible'),
        ]);
    }

    /**
     * Deming tasks of the gateway, in the order of AiSubjects, with their
     * title in the user's language; null when the gateway cannot be reached.
     *
     * @return array<int, array{type: string, title: string, version: string}>|null
     */
    private function tasks(): ?array
    {
        try {
            AiGateway::checkUrl();
            $tasks = AiGateway::demingTasks(app(AiGateway::class)->tasks());
        } catch (AiAgentException) {
            return null;
        }

        $language = AiAgentService::language();
        $result = [];
        foreach (AiSubjects::TYPES as $type => $subject) {
            if (! isset($tasks[$type])) {
                continue;
            }
            $titles = $tasks[$type]['title'] ?? null;
            $title = is_array($titles) ? ($titles[$language] ?? $titles['en'] ?? $titles['fr'] ?? null) : $titles;
            $result[] = [
                'type' => $type,
                'title' => is_string($title) && $title !== '' ? $title : trans($subject['label']),
                'version' => is_string($tasks[$type]['task_version'] ?? null) ? $tasks[$type]['task_version'] : '',
            ];
        }

        return $result;
    }

    // Clears the stored analyses of the four tables
    public function purge(): RedirectResponse
    {
        abort_if(! Auth::user()->isAdmin(), Response::HTTP_FORBIDDEN, '403 Forbidden');
        abort_if(! Configuration::aiActivated(), Response::HTTP_NOT_FOUND, '404 Not Found');

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

    private function update(string $name, string $value): void
    {
        if (Configuration::get($name) !== $value) {
            Configuration::set($name, $value);
        }
    }
}
