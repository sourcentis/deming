<?php

use App\Models\Control;
use App\Models\Measure;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

/** Security measure with completed controls, one per [scope, realisation date, note]. */
function radarControl(array $done): Control
{
    $control = Control::factory()->create();
    foreach ($done as [$scope, $date, $note]) {
        $measure = Measure::factory()->create([
            'scope' => $scope, 'status' => 2, 'realisation_date' => $date, 'note' => $note,
        ]);
        $control->measures()->attach($measure->id);
    }

    return $control;
}

test('one chart and one table per scope, in alphabetical order', function () {
    $control = radarControl([
        ['Zeta', '2026-01-10', 7],
        ['alpha', '2026-02-10', 8],
        ['Zeta', '2026-03-10', 9],
        [null, '2026-04-10', 6],
    ]);

    $html = $this->actingAs($this->admin)->get('/radar/bob?clause=' . urlencode($control->clause))
        ->assertOk()->getContent();

    // Natural order without case, controls without scope last
    expect($html)->toContain('id="scoreChart-' . $control->id . '-0"')
        ->toContain('id="scoreChart-' . $control->id . '-1"')
        ->toContain('id="scoreChart-' . $control->id . '-2"')
        ->not->toContain('id="scoreChart-' . $control->id . '-3"');

    $alpha = strpos($html, '>alpha</h6>');
    $zeta = strpos($html, '>Zeta</h6>');
    $none = strpos($html, '>—</h6>');
    expect($alpha)->toBeInt()->toBeLessThan($zeta)
        ->and($zeta)->toBeLessThan($none);

    // Each table lists only the dates of its scope
    $zetaBlock = substr($html, $zeta, $none - $zeta);
    expect($zetaBlock)->toContain('2026-01-10')->toContain('2026-03-10')
        ->not->toContain('2026-02-10')->not->toContain('2026-04-10');
});

test('a single scope keeps a single chart without scope title', function () {
    $control = radarControl([
        ['alpha', '2026-01-10', 7],
        ['alpha', '2026-02-10', 8],
    ]);

    $html = $this->actingAs($this->admin)->get('/radar/bob?clause=' . urlencode($control->clause))
        ->assertOk()->getContent();

    expect($html)->toContain('id="scoreChart-' . $control->id . '-0"')
        ->not->toContain('id="scoreChart-' . $control->id . '-1"')
        ->not->toContain('</h6>');
});

test('filtering on a scope shows only its chart', function () {
    $control = radarControl([
        ['alpha', '2026-01-10', 7],
        ['beta', '2026-02-10', 8],
    ]);

    $html = $this->actingAs($this->admin)
        ->get('/radar/bob?clause=' . urlencode($control->clause) . '&scope=beta')
        ->assertOk()->getContent();

    expect($html)->toContain('id="scoreChart-' . $control->id . '-0"')
        ->not->toContain('id="scoreChart-' . $control->id . '-1"')
        ->toContain('2026-02-10')->not->toContain('2026-01-10');
});
