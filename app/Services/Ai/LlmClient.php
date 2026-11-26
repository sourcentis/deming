<?php

namespace App\Services\Ai;

use App\Models\Configuration;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Client of any language model exposing the OpenAI Chat Completions API:
 * Sourcentis AI gateway, OVHcloud AI Endpoints, OpenAI, Mistral, Ollama, vLLM, LM Studio…
 *
 *   POST {base_url}/chat/completions
 *   GET  {base_url}/models              (when the provider offers it)
 *
 * Redirections are never followed: data must not leave for an unconfigured host.
 * Failures are mapped to a generic reason: the provider's response body, the URL
 * and the key never reach the browser nor the log.
 */
class LlmClient
{
    private const CONNECT_TIMEOUT = 15;
    private const MODELS_TIMEOUT = 10;

    // Plain http is allowed for a model hosted on the local machine or network
    private const LOCAL_NETWORKS = ['127.0.0.0/8', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16', '::1/128', 'fc00::/7'];

    /**
     * Values replacing the saved settings (base_url, api_key, model), used by the
     * settings page to test what is typed in the form before saving it.
     *
     * @var array<string, string|null>
     */
    private array $overrides = [];

    /**
     * Client using the given settings instead of the saved ones.
     *
     * @param  array{base_url?: string|null, api_key?: string|null, model?: string|null}  $settings
     */
    public function with(array $settings): static
    {
        $client = clone $this;
        $client->overrides = array_intersect_key($settings, array_flip(['base_url', 'api_key', 'model']));

        return $client;
    }

    private function setting(string $name): ?string
    {
        return array_key_exists($name, $this->overrides)
            ? $this->overrides[$name]
            : Configuration::get('ai.' . $name);
    }

    private function baseUrl(): string
    {
        return rtrim(trim((string) $this->setting('base_url')), '/');
    }

    /**
     * https is mandatory, except for localhost, a private address
     * or a single-label host name (Docker service).
     */
    public static function isAllowedUrl(?string $url): bool
    {
        $parts = parse_url((string) $url);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower(trim($parts['host'], '[]'));

        if ($scheme === 'https') {
            return true;
        }
        if ($scheme !== 'http') {
            return false;
        }
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return IpUtils::checkIp($host, self::LOCAL_NETWORKS);
        }

        return $host === 'localhost' || preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $host) === 1;
    }

    /**
     * No default provider: the URL and the model are set by an administrator.
     *
     * @throws AiAgentException
     */
    private function checkConfiguration(): string
    {
        $baseUrl = $this->baseUrl();
        if ($baseUrl === '' || trim((string) $this->setting('model')) === '') {
            throw new AiAgentException(AiAgentException::NOT_CONFIGURED);
        }
        if (! self::isAllowedUrl($baseUrl)) {
            throw new AiAgentException(AiAgentException::INSECURE_URL);
        }

        return $baseUrl;
    }

    /**
     * Chat completion with the configured model and parameters.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array{max_tokens?: int, response_format?: array<string, mixed>|null}  $options
     * @return array{content: string, model: string, usage: array<string, mixed>|null, status: int, finish_reason: string|null}
     *
     * @throws AiAgentException
     */
    public function chat(array $messages, array $options = []): array
    {
        $baseUrl = $this->checkConfiguration();
        $model = trim((string) $this->setting('model'));

        $payload = [
            'model' => $model,
            'messages' => $messages,
            'temperature' => (float) Configuration::get('ai.temperature'),
            'max_tokens' => $options['max_tokens'] ?? (int) Configuration::get('ai.max_tokens'),
        ];
        if (! empty($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        }

        $timeout = max(5, (int) Configuration::get('ai.timeout'));
        $response = $this->send(fn () => $this->request($timeout)->post($baseUrl . '/chat/completions', $payload));

        $choice = $response->json('choices.0');
        if (! is_array($choice)) {
            throw new AiAgentException(AiAgentException::INVALID_RESPONSE, 502, $response->status());
        }
        $content = $choice['message']['content'] ?? null;
        if (! is_string($content) || trim($content) === '') {
            // Answer cut by max_tokens (a reasoning model may spend them all before answering)
            $reason = ($choice['finish_reason'] ?? null) === 'length' ? AiAgentException::TRUNCATED : AiAgentException::INVALID_RESPONSE;
            throw new AiAgentException($reason, 502, $response->status());
        }

        $usage = $response->json('usage');
        $answered = $response->json('model');

        return [
            'content' => $content,
            'model' => is_string($answered) && $answered !== '' ? $answered : $model,
            'usage' => is_array($usage) ? $usage : null,
            'status' => $response->status(),
            'finish_reason' => is_string($choice['finish_reason'] ?? null) ? $choice['finish_reason'] : null,
        ];
    }

    /**
     * Identifiers of the models offered by the provider, sorted.
     *
     * @return array<int, string>
     *
     * @throws AiAgentException
     */
    public function models(): array
    {
        $baseUrl = $this->baseUrl();
        if ($baseUrl === '') {
            throw new AiAgentException(AiAgentException::NOT_CONFIGURED);
        }
        if (! self::isAllowedUrl($baseUrl)) {
            throw new AiAgentException(AiAgentException::INSECURE_URL);
        }

        $response = $this->send(fn () => $this->request(self::MODELS_TIMEOUT)->get($baseUrl . '/models'));

        $data = $response->json('data');
        if (! is_array($data)) {
            throw new AiAgentException(AiAgentException::INVALID_RESPONSE, 502, $response->status());
        }

        $models = [];
        foreach ($data as $model) {
            if (is_array($model) && is_string($model['id'] ?? null) && $model['id'] !== '') {
                $models[] = mb_substr($model['id'], 0, 191);
            }
        }
        $models = array_values(array_unique($models));
        sort($models, SORT_NATURAL | SORT_FLAG_CASE);

        return $models;
    }

    public static function clientHeader(): string
    {
        $file = base_path('version.txt');
        $version = is_readable($file) ? trim((string) file_get_contents($file)) : '';

        return 'deming/' . ($version !== '' ? $version : 'unknown');
    }

    private function request(int $timeout): PendingRequest
    {
        $request = Http::timeout($timeout)
            ->connectTimeout(min(self::CONNECT_TIMEOUT, $timeout))
            ->acceptJson()
            ->withHeaders(['X-Client-App' => self::clientHeader()])
            ->withoutRedirecting();

        // No Authorization header without a key (local model)
        $key = $this->setting('api_key');
        if ($key !== null && $key !== '') {
            $request = $request->withToken($key);
        }

        return $request;
    }

    /**
     * Sends the request and maps every failure to a generic reason.
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
            throw new AiAgentException(AiAgentException::UNREACHABLE, 502, $response->status());
        }
        if ($response->successful()) {
            return $response;
        }

        $status = $response->status();
        [$reason, $browserStatus] = match (true) {
            in_array($status, [401, 403], true) => [AiAgentException::INVALID_KEY, 502],
            $status === 402 => [AiAgentException::QUOTA_EXCEEDED, 502],
            $status === 413 => [AiAgentException::TOO_LARGE, 502],
            $status === 429 => [AiAgentException::RATE_LIMITED, 429],
            in_array($status, [400, 404, 405, 422], true) => [AiAgentException::INCOMPATIBLE, 502],
            in_array($status, [408, 504], true) => [AiAgentException::TIMEOUT, 504],
            default => [AiAgentException::PROVIDER_ERROR, 502],
        };

        throw new AiAgentException($reason, $browserStatus, $status);
    }
}
