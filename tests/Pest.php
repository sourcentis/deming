<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        \App\Models\RiskScoringConfig::clearCache();
        \App\Models\Configuration::clearCache();
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

/** Fake /chat/completions answer wrapping the given content. */
function aiAgentResponse(array|string|null $content = null, string $finishReason = 'stop', int $status = 200)
{
    $content ??= aiValidAnalysis();

    return \Illuminate\Support\Facades\Http::response([
        'choices' => [[
            'finish_reason' => $finishReason,
            'message' => ['role' => 'assistant', 'content' => is_array($content) ? json_encode($content) : $content],
        ]],
        'usage' => ['total_tokens' => 1234],
    ], $status);
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
        'action' => \App\Models\Action::factory()->create(),
        'risk' => \App\Models\Risk::factory()->create(),
    };

    return $object->fresh();
}

function aiEnable(): void
{
    \App\Models\Configuration::set('ai.enabled', '1');
}
