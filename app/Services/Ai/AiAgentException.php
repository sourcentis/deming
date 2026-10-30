<?php

namespace App\Services\Ai;

use RuntimeException;

/**
 * Business error raised by the AI agent.
 *
 * The reason is a translation key suffix (ai.errors.{reason}); the message
 * shown to the browser is always generic and never contains the agent's
 * response body, URL or headers.
 */
class AiAgentException extends RuntimeException
{
    public const UNREACHABLE = 'unreachable';
    public const TIMEOUT = 'timeout';
    public const TRUNCATED = 'truncated';
    public const INVALID_RESPONSE = 'invalid_response';
    public const INSECURE_URL = 'insecure_url';
    public const NOT_CONFIGURED = 'not_configured';

    public function __construct(public readonly string $reason, public readonly int $status = 502)
    {
        parent::__construct('AI agent error: ' . $reason);
    }

    public function translatedMessage(): string
    {
        return trans('ai.errors.' . $this->reason);
    }
}
