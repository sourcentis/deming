<?php

use App\Exports\ActionsExport;
use App\Exports\ControlsExport;
use App\Exports\MeasuresExport;
use App\Exports\RiskExport;
use App\Models\Action;
use App\Models\AuditLog;
use App\Models\Control;
use App\Models\Measure;
use App\Models\Risk;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;

const AI_SENTINEL = 'AI-SENTINEL-ANALYSIS';

function storeFakeAnalysis(string $table, int $id): void
{
    DB::table($table)->where('id', $id)->update([
        'ai_analysis' => json_encode(['synthese' => AI_SENTINEL]),
        'ai_analysis_at' => now(),
        'ai_analysis_model' => 'gpt-oss-120b',
        'ai_analysis_hash' => str_repeat('a', 64),
    ]);
}

test('ai columns are hidden from serialization', function (string $class, string $table) {
    $object = $class::factory()->create();
    storeFakeAnalysis($table, $object->id);

    $array = $class::find($object->id)->toArray();
    foreach ($class::AI_COLUMNS as $column) {
        expect($array)->not->toHaveKey($column);
    }
})->with([
    'control' => [Control::class, 'controls'],
    'measure' => [Measure::class, 'measures'],
    'action' => [Action::class, 'actions'],
    'risk' => [Risk::class, 'risks'],
]);

test('ai columns are not fillable', function (string $class) {
    foreach ($class::AI_COLUMNS as $column) {
        expect((new $class())->isFillable($column))->toBeFalse();
    }
})->with([Control::class, Measure::class, Action::class, Risk::class]);

test('ai columns are absent from REST API responses', function (string $class, string $table, string $uri) {
    Passport::actingAs(User::factory()->apiUser()->create());
    $object = $class::factory()->create();
    storeFakeAnalysis($table, $object->id);

    expect($this->getJson("/api/{$uri}")->assertOk()->getContent())->not->toContain(AI_SENTINEL);
    expect($this->getJson("/api/{$uri}/{$object->id}")->assertOk()->getContent())->not->toContain(AI_SENTINEL)
        ->not->toContain('ai_analysis');
})->with([
    'control' => [Control::class, 'controls', 'controls'],
    'measure' => [Measure::class, 'measures', 'measures'],
    'risk' => [Risk::class, 'risks', 'risks'],
]);

test('ai columns are absent from exports', function (string $class, string $table, string $export) {
    $object = $class::factory()->create();
    storeFakeAnalysis($table, $object->id);

    $exporter = new $export();
    $rows = $exporter->query()->get()->map(fn ($row) => $exporter->map($row));

    expect(json_encode($rows))->not->toContain(AI_SENTINEL);
})->with([
    'control' => [Control::class, 'controls', ControlsExport::class],
    'measure' => [Measure::class, 'measures', MeasuresExport::class],
    'action' => [Action::class, 'actions', ActionsExport::class],
    'risk' => [Risk::class, 'risks', RiskExport::class],
]);

test('ai columns never reach the audit log', function () {
    $action = Action::factory()->create();
    storeFakeAnalysis('actions', $action->id);

    $action = Action::find($action->id);
    $action->progress = 42;
    $action->save();

    expect(AuditLog::where('subject_type', Action::class)->get()->toJson())->not->toContain(AI_SENTINEL);
});

test('the next control created when making a control does not copy the analysis', function () {
    $admin = User::factory()->admin()->create();
    $control = Control::factory()->create();
    $measure = Measure::factory()->create(['periodicity' => 12]);
    $measure->controls()->sync([$control->id]);
    storeFakeAnalysis('measures', $measure->id);

    $this->actingAs($admin)->post('/bob/make', [
        'id' => $measure->id,
        'score' => 3,
        'observations' => 'ok',
        'realisation_date' => now()->format('Y-m-d'),
        'plan_date' => now()->format('Y-m-d'),
        'next_date' => now()->addYear()->format('Y-m-d'),
    ])->assertRedirect();

    $next = Measure::find(Measure::find($measure->id)->next_id);
    expect($next)->not->toBeNull();
    expect(DB::table('measures')->where('id', $next->id)->value('ai_analysis'))->toBeNull();
    expect(DB::table('measures')->where('id', $measure->id)->value('ai_analysis'))->not->toBeNull();
});

test('the next control created on accept does not copy the analysis', function () {
    $admin = User::factory()->admin()->create();
    $control = Control::factory()->create();
    $measure = Measure::factory()->create(['periodicity' => 12, 'status' => 1]);
    $measure->controls()->sync([$control->id]);
    storeFakeAnalysis('measures', $measure->id);

    $this->actingAs($admin)->post('/bob/accept', [
        'id' => $measure->id,
        'score' => 3,
        'realisation_date' => now()->format('Y-m-d'),
        'plan_date' => now()->format('Y-m-d'),
        'next_date' => now()->addYear()->format('Y-m-d'),
    ])->assertRedirect();

    $next = Measure::find(Measure::find($measure->id)->next_id);
    expect($next)->not->toBeNull();
    expect(DB::table('measures')->where('id', $next->id)->value('ai_analysis'))->toBeNull();
});

test('deleting a risk drops its analysis', function () {
    $admin = User::factory()->admin()->create();
    $risk = Risk::factory()->create();
    storeFakeAnalysis('risks', $risk->id);

    $this->actingAs($admin)->get("/risk/delete/{$risk->id}")->assertRedirect();

    $row = DB::table('risks')->where('id', $risk->id)->first();
    expect($row->deleted_at)->not->toBeNull();
    expect($row->ai_analysis)->toBeNull();
    expect($row->ai_analysis_hash)->toBeNull();
});
