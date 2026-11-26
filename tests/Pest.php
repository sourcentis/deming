<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        \App\Models\RiskScoringConfig::clearCache();
        \App\Models\Configuration::clearCache();
        // No test may reach a real host (AI provider)
        \Illuminate\Support\Facades\Http::preventStrayRequests();
    })
    ->in('Feature');

// Disable rate limiting for all feature tests
uses()->beforeEach(function () {
    $this->withoutMiddleware(ThrottleRequests::class);
})->in('Feature/API', 'Feature/Web');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| AI agent helpers
|--------------------------------------------------------------------------
*/

/** A valid analysis, as returned by the model. */
function aiValidAnalysis(array $overrides = []): array
{
    return array_replace([
        'summary' => 'Test summary.',
        'assessed_status' => 'partially_compliant',
        'risk_level' => 'medium',
        'confidence' => 'medium',
        'findings' => [['type' => 'weakness', 'description' => 'Insufficient evidence.']],
        'recommendations' => [[
            'priority' => 'high',
            'action' => 'Document the evidence.',
            'rationale' => 'Finding 1.',
            'suggested_due_date' => '3 months',
            'reference' => 'ISO 27001:2022 9.1',
        ]],
        'open_questions' => [],
        'viewpoints' => ['ceo' => 'A.', 'ciso' => 'B.', 'dpo' => 'C.', 'auditor' => 'D.'],
    ], $overrides);
}

/**
 * Chat completion answered by the provider. The content is the given analysis
 * as JSON, or the given raw text.
 */
function aiAgentResponse(array|string|null $data = null, array $extra = [], int $status = 200)
{
    $content = is_string($data) ? $data : json_encode($data ?? aiValidAnalysis(), JSON_UNESCAPED_UNICODE);

    return \Illuminate\Support\Facades\Http::response(array_replace([
        'id' => 'chatcmpl-test',
        'object' => 'chat.completion',
        'model' => 'test-model',
        'choices' => [[
            'index' => 0,
            'message' => ['role' => 'assistant', 'content' => $content],
            'finish_reason' => 'stop',
        ]],
        'usage' => ['prompt_tokens' => 1000, 'completion_tokens' => 234, 'total_tokens' => 1234],
    ], $extra), $status);
}

/** Answer of GET {base_url}/models. */
function aiModelsResponse(array $ids = ['test-model', 'other-model'])
{
    return \Illuminate\Support\Facades\Http::response([
        'object' => 'list',
        'data' => array_map(fn (string $id) => ['id' => $id, 'object' => 'model'], $ids),
    ]);
}

/** Error of the provider, with a body that must never reach the browser. */
function aiGatewayError(int $status, string $code = 'error')
{
    return \Illuminate\Support\Facades\Http::response(['error' => [
        'message' => "secret provider body {$code}", 'type' => 'error', 'code' => $code,
    ]], $status);
}

/** Fakes the provider: POST /chat/completions and GET /models. */
function aiFakeGateway(mixed $run = null, mixed $models = null): void
{
    $run ??= aiAgentResponse();
    $models ??= aiModelsResponse();

    // A single dispatcher: with URL patterns, Laravel would also feed GET /models to a POST sequence
    \Illuminate\Support\Facades\Http::fake(function (\Illuminate\Http\Client\Request $request, array $options) use ($run, $models) {
        $stub = $request->method() === 'GET' ? $models : $run;

        return is_callable($stub) ? $stub($request, $options) : $stub;
    });
}

/** Number of chat completions sent to the provider. */
function aiRunCount(): int
{
    return \Illuminate\Support\Facades\Http::recorded(fn ($request) => $request->method() === 'POST')->count();
}

/** Creates an object of the given AI type, with a security measure linked to controls. */
function aiCreateSubject(string $type): \Illuminate\Database\Eloquent\Model
{
    // Reloaded, as the controllers do: default column values are then present
    $object = match ($type) {
        'control' => \App\Models\Control::factory()->create(),
        'measure' => tap(\App\Models\Measure::factory()->create(), function ($measure) {
            $measure->controls()->sync([\App\Models\Control::factory()->create()->id]);
        }),
        // Fixed values, so that tests changing them always make a difference
        'action' => \App\Models\Action::factory()->create(['progress' => 50]),
        'risk' => \App\Models\Risk::factory()->create(['probability' => 3]),
    };

    return $object->fresh();
}

// AI configured with a test provider (there is no default provider)
function aiConfigure(): void
{
    \App\Models\Configuration::set('ai.base_url', 'https://agent.example.com/v1');
    \App\Models\Configuration::set('ai.model', 'test-model');
}

// AI enabled with a test provider
function aiEnable(): void
{
    \App\Models\Configuration::set('ai.enabled', '1');
    aiConfigure();
}
