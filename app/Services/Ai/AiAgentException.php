<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Business error raised by the AI agent.
 *
 * The reason is a translation key suffix (ai.errors.{reason}); the message
 * shown to the browser is always generic and never contains the gateway's
 * response body, URL or headers.
 */
class AiAgentException extends RuntimeException
{
    public const UNREACHABLE = 'unreachable';
    public const TIMEOUT = 'timeout';
    public const INVALID_RESPONSE = 'invalid_response';
    public const INSECURE_URL = 'insecure_url';
    public const NOT_CONFIGURED = 'not_configured';
    // 401 or 403: key invalid, suspended, terminated, or not allowed for the task
    public const INVALID_KEY = 'invalid_key';
    // 402: monthly budget exhausted
    public const QUOTA_EXCEEDED = 'quota_exceeded';
    // 429: rate limit of the key or of the provider
    public const RATE_LIMITED = 'rate_limited';
    // 413: context too large for the task
    public const TOO_LARGE = 'too_large';
    // 400 or 404: request refused or task unknown, i.e. Deming and the gateway disagree
    public const INCOMPATIBLE = 'incompatible';

    public function __construct(public readonly string $reason, public readonly int $status = 502)
    {
        parent::__construct('AI agent error: ' . $reason);
    }

    public function translatedMessage(): string
    {
        return trans('ai.errors.' . $this->reason);
    }
}
