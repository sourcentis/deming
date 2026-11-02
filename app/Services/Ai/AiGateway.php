<?php

namespace App\Services\Ai;

use App\Models\Configuration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Client of the AI gateway (sourcentis-ai-gateway).
 *
 * The gateway owns the prompts, the response format, the model and the
 * generation parameters of each task. Deming only sends the data to analyse:
 *   GET  {base_url}/tasks              catalog of the tasks, no token used
 *   POST {base_url}/deming/{type}      { language, date, context }
 *
 * Redirections are never followed: data must not leave for an unconfigured host.
 */
class AiGateway
{
    public const APPLICATION = 'deming';

    // Catalog of the tasks, cached for an hour; forgotten when the settings are saved
    public const TASKS_CACHE_KEY = 'ai.tasks';
    private const TASKS_TTL = 3600;

    private const TIMEOUT = 180;
    private const TASKS_TIMEOUT = 30;
    private const CONNECT_TIMEOUT = 15;

    public static function baseUrl(): string
    {
        return rtrim((string) Configuration::get('ai.base_url'), '/');
    }

    /**
     * https is mandatory, except for a gateway on localhost / 127.0.0.1.
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

    /**
     * No default gateway: the URL must be configured by an administrator.
     *
     * @throws AiAgentException
     */
    public static function checkUrl(): string
    {
        $baseUrl = self::baseUrl();
        if ($baseUrl === '') {
            throw new AiAgentException(AiAgentException::NOT_CONFIGURED);
        }
        if (! self::isAllowedUrl($baseUrl)) {
            throw new AiAgentException(AiAgentException::INSECURE_URL);
        }

        return $baseUrl;
    }

    public static function forgetTasks(): void
    {
        Cache::forget(self::TASKS_CACHE_KEY);
    }

    /**
     * Uncached catalog of the gateway, for the connection test.
     *
     * @return array{response: Response, tasks: array<int, array<string, mixed>>}
     *
     * @throws AiAgentException
     */
    public function fetchTasks(): array
    {
        $baseUrl = self::checkUrl();
        $response = $this->send(fn () => $this->request(self::TASKS_TIMEOUT)->get($baseUrl . '/tasks'));

        $tasks = $response->json('data');
        if (! is_array($tasks) || ! array_is_list($tasks)) {
            throw new AiAgentException(AiAgentException::INVALID_RESPONSE);
        }
        $tasks = array_values(array_filter($tasks, fn ($task) => is_array($task) && is_string($task['id'] ?? null)));

        // A successful answer refreshes the cache used by the analyses
        Cache::put(self::TASKS_CACHE_KEY, $tasks, self::TASKS_TTL);

        return ['response' => $response, 'tasks' => $tasks];
    }

    /**
     * Cached catalog of the gateway; failures are not cached.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws AiAgentException
     */
    public function tasks(): array
    {
        return Cache::remember(self::TASKS_CACHE_KEY, self::TASKS_TTL, fn () => $this->fetchTasks()['tasks']);
    }

    /**
     * Deming tasks of the catalog, by type (control, measure, action, risk).
     *
     * @param  array<int, array<string, mixed>>  $tasks
     * @return array<string, array<string, mixed>>
     */
    public static function demingTasks(array $tasks): array
    {
        $result = [];
        foreach ($tasks as $task) {
            $id = (string) ($task['id'] ?? '');
            $type = substr($id, strlen(self::APPLICATION) + 1);
            if (str_starts_with($id, self::APPLICATION . '/') && in_array($type, AiSubjects::types(), true)) {
                $result[$type] = $task;
            }
        }

        return $result;
    }

    /**
     * Current version of the task of a type, or null if the gateway cannot be reached.
     */
    public function taskVersion(string $type): ?string
    {
        try {
            $version = self::demingTasks($this->tasks())[$type]['task_version'] ?? null;
        } catch (AiAgentException) {
            return null;
        }

        return is_string($version) && $version !== '' ? $version : null;
    }

    /**
     * A cached catalog older than the version just returned by the gateway is
     * forgotten: otherwise every opening of the panel would run a new analysis
     * until the cache expires.
     */
    public function noteVersion(string $type, string $version): void
    {
        $tasks = Cache::get(self::TASKS_CACHE_KEY);
        if (is_array($tasks) && (self::demingTasks($tasks)[$type]['task_version'] ?? null) !== $version) {
            self::forgetTasks();
        }
    }

    /**
     * Runs the task of a type.
     *
     * @param  array{language: string, date: string, context: array<string, mixed>}  $payload
     * @return array<string, mixed> the decoded answer: task, task_version, schema, model, data, usage
     *
     * @throws AiAgentException
     */
    public function run(string $type, array $payload): array
    {
        $baseUrl = self::checkUrl();
        $url = $baseUrl . '/' . self::APPLICATION . '/' . $type;

        // An empty context must stay a JSON object
        $payload['context'] = (object) $payload['context'];
        $response = $this->send(fn () => $this->request(self::TIMEOUT)->post($url, $payload));

        $body = $response->json();
        if (! is_array($body) || ! is_array($body['data'] ?? null)) {
            throw new AiAgentException(AiAgentException::INVALID_RESPONSE);
        }

        return $body;
    }

    private function request(int $timeout): PendingRequest
    {
        $request = Http::timeout($timeout)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->acceptJson()
            ->withoutRedirecting();

        $key = Configuration::get('ai.api_key');
        if ($key !== null && $key !== '') {
            $request = $request->withToken($key);
        }

        return $request;
    }

    /**
     * Sends the request and maps every failure to a generic reason:
     * the gateway's response body is never passed on.
     *
     * @param  callable(): Response  $send
     *
     * @throws AiAgentException
     */
    private function send(callable $send): Response
    {
        try {
            $response = $send();
        } catch (ConnectionException $e) {
            $timeout = str_contains($e->getMessage(), 'cURL error 28') || stripos($e->getMessage(), 'timed out') !== false;
            throw $timeout
                ? new AiAgentException(AiAgentException::TIMEOUT, 504)
                : new AiAgentException(AiAgentException::UNREACHABLE);
        }

        if ($response->redirect()) {
            throw new AiAgentException(AiAgentException::UNREACHABLE);
        }
        if ($response->successful()) {
            return $response;
        }

        throw match ($response->status()) {
            401, 403 => new AiAgentException(AiAgentException::INVALID_KEY),
            402 => new AiAgentException(AiAgentException::QUOTA_EXCEEDED),
            413 => new AiAgentException(AiAgentException::TOO_LARGE),
            429 => new AiAgentException(AiAgentException::RATE_LIMITED, 429),
            400, 404 => new AiAgentException(AiAgentException::INCOMPATIBLE),
            502 => new AiAgentException(AiAgentException::INVALID_RESPONSE),
            504 => new AiAgentException(AiAgentException::TIMEOUT, 504),
            default => new AiAgentException(AiAgentException::UNREACHABLE),
        };
    }
}
