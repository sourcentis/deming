<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        \App\Models\RiskScoringConfig::clearCache();
        \App\Models\Configuration::clearCache();
        // No test may reach a real host (the AI settings page reads the gateway catalog)
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

/** A valid analysis, as returned by the agent. */
function aiValidAnalysis(array $overrides = []): array
{
    return array_replace([
        'synthese' => 'Synthèse de test.',
        'statut_evalue' => 'partiellement_conforme',
        'niveau_risque' => 'moyen',
        'confiance' => 'moyenne',
        'constats' => [['type' => 'faiblesse', 'description' => 'Preuves insuffisantes.']],
        'recommandations' => [[
            'priorite' => 'haute',
            'action' => 'Documenter les preuves.',
            'justification' => 'Constat 1.',
            'echeance_suggeree' => '3 mois',
            'reference' => 'ISO 27001:2022 9.1',
        ]],
        'questions_ouvertes' => [],
        'points_de_vue' => ['ceo' => 'A.', 'rssi' => 'B.', 'dpo' => 'C.', 'auditeur' => 'D.'],
    ], $overrides);
}

const AI_GATEWAY_TYPES = ['control', 'measure', 'action', 'risk'];

/** Answer of POST {base_url}/deming/{type}, wrapping the given analysis. */
function aiAgentResponse(?array $data = null, array $extra = [], int $status = 200)
{
    return \Illuminate\Support\Facades\Http::response(array_replace([
        'task' => 'deming/control',
        'task_version' => 'v1',
        'schema' => 'analysis-v1',
        'model' => 'sourcentis-standard',
        'data' => $data ?? aiValidAnalysis(),
        'usage' => ['prompt_tokens' => 1000, 'completion_tokens' => 234, 'total_tokens' => 1234],
    ], $extra), $status);
}

/** Answer of GET {base_url}/tasks: the Deming tasks of the given types, and a task of another application. */
function aiTasksResponse(string $version = 'v1', array $types = AI_GATEWAY_TYPES)
{
    $tasks = array_map(fn (string $type) => [
        'id' => "deming/{$type}",
        'application' => 'deming',
        'task' => $type,
        'title' => ['fr' => "Analyse {$type}", 'en' => "Analysis {$type}", 'de' => "Analyse {$type} (de)"],
        'description' => ['fr' => '…'],
        'languages' => ['fr', 'en', 'de'],
        'schema' => ['name' => 'analysis-v1', 'content' => ['type' => 'object']],
        'task_version' => $version,
    ], $types);
    $tasks[] = ['id' => 'mercator/map', 'title' => ['fr' => 'Autre application'], 'task_version' => 'other'];

    return \Illuminate\Support\Facades\Http::response(['object' => 'list', 'data' => $tasks]);
}

/** Error of the gateway, with a body that must never reach the browser. */
function aiGatewayError(int $status, string $code = 'error')
{
    return \Illuminate\Support\Facades\Http::response(['error' => [
        'message' => "secret gateway body {$code}", 'type' => 'error', 'code' => $code,
    ]], $status);
}

/** Fakes the gateway: GET /tasks and POST /deming/{type}. */
function aiFakeGateway(mixed $run = null, mixed $tasks = null): void
{
    $run ??= aiAgentResponse();
    $tasks ??= aiTasksResponse();

    // A single dispatcher: with URL patterns, Laravel would also feed GET /tasks to a POST sequence
    \Illuminate\Support\Facades\Http::fake(function (\Illuminate\Http\Client\Request $request, array $options) use ($run, $tasks) {
        $stub = $request->method() === 'GET' && str_ends_with($request->url(), '/tasks') ? $tasks : $run;

        return is_callable($stub) ? $stub($request, $options) : $stub;
    });
}

/** Number of analyses requested from the gateway (POST), the catalog excluded. */
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

// AI enabled with a test gateway (there is no default gateway URL)
function aiEnable(): void
{
    \App\Models\Configuration::set('ai.enabled', '1');
    \App\Models\Configuration::set('ai.base_url', 'https://agent.example.com/v1');
}
