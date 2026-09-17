<?php

use App\Models\Control;
use App\Models\ControlMapping;
use App\Models\Domain;
use App\Models\User;

function crosswalkControl(string $framework, string $clause): Control
{
    $domain = Domain::factory()->create(['framework' => $framework]);

    return Control::factory()->create([
        'domain_id' => $domain->id,
        'clause' => $clause,
        'name' => "Control {$clause}",
    ]);
}

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->user()->create();
    $this->source = crosswalkControl('REF-SOURCE', 'S-1');
    $this->target = crosswalkControl('REF-TARGET', 'T-1');
});

test('guest is redirected from crosswalk pages', function () {
    $this->get('/crosswalk')->assertRedirect('/login');
});

test('authenticated users can read mappings and matrix', function () {
    $mapping = ControlMapping::factory()->create([
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->target->id,
        'mapping_type' => 'related',
    ]);

    $this->actingAs($this->user)
        ->get('/crosswalk')
        ->assertOk()
        ->assertSee('S-1')
        ->assertSee('T-1');

    $this->actingAs($this->user)
        ->get(route('crosswalk.show', $mapping))
        ->assertOk()
        ->assertSee('Control S-1')
        ->assertSee('Control T-1');

    $this->actingAs($this->user)
        ->get(route('crosswalk.matrix', [
            'source_framework' => 'REF-SOURCE',
            'target_framework' => 'REF-TARGET',
        ]))
        ->assertOk();
});

test('non administrators cannot mutate mappings', function (string $method, string $uri, array $data = []) {
    $mapping = ControlMapping::factory()->create([
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->target->id,
    ]);

    $uri = str_replace('{mapping}', (string) $mapping->id, $uri);
    $this->actingAs($this->user)->{$method}($uri, $data)->assertForbidden();
})->with([
    'create form' => ['get', '/crosswalk/create'],
    'store' => ['post', '/crosswalk', [
        'source_control_id' => 1,
        'target_control_id' => 2,
        'mapping_type' => 'related',
    ]],
    'edit form' => ['get', '/crosswalk/{mapping}/edit'],
    'update' => ['put', '/crosswalk/{mapping}', [
        'source_control_id' => 1,
        'target_control_id' => 2,
        'mapping_type' => 'related',
    ]],
    'delete' => ['delete', '/crosswalk/{mapping}'],
]);

test('administrator can create update validate and delete a mapping', function () {
    $this->actingAs($this->admin)
        ->get('/crosswalk/create')
        ->assertOk();

    $response = $this->actingAs($this->admin)->post('/crosswalk', [
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->target->id,
        'mapping_type' => 'partial',
        'coverage' => 'medium',
        'confidence' => 80,
        'rationale' => 'Documented overlap.',
        'source_reference' => 'Official table, row 4',
        'source_url' => 'https://example.test/source',
        'validated' => 1,
    ]);

    $mapping = ControlMapping::query()->firstOrFail();
    $response->assertRedirect(route('crosswalk.show', $mapping));
    $this->assertDatabaseHas('control_mappings', [
        'id' => $mapping->id,
        'mapping_type' => 'partial',
        'coverage' => 'medium',
        'validated' => true,
        'validated_by' => $this->admin->id,
    ]);

    $this->actingAs($this->admin)
        ->get(route('crosswalk.edit', $mapping))
        ->assertOk();

    $this->actingAs($this->admin)
        ->put(route('crosswalk.update', $mapping), [
            'source_control_id' => $this->source->id,
            'target_control_id' => $this->target->id,
            'mapping_type' => 'supports',
            'coverage' => 'low',
            'validated' => 0,
        ])
        ->assertRedirect(route('crosswalk.show', $mapping));

    $this->assertDatabaseHas('control_mappings', [
        'id' => $mapping->id,
        'mapping_type' => 'supports',
        'coverage' => 'low',
        'validated' => false,
        'validated_by' => null,
        'validated_at' => null,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('crosswalk.destroy', $mapping))
        ->assertRedirect(route('crosswalk.index'));
    $this->assertDatabaseMissing('control_mappings', ['id' => $mapping->id]);
});

test('crud validates enumerations distinct controls and duplicate pairs', function () {
    ControlMapping::factory()->create([
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->target->id,
    ]);

    $this->actingAs($this->admin)->post('/crosswalk', [
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->target->id,
        'mapping_type' => 'invented',
        'coverage' => 'maximum',
    ])->assertSessionHasErrors(['target_control_id', 'mapping_type', 'coverage']);

    $this->actingAs($this->admin)->post('/crosswalk', [
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->source->id,
        'mapping_type' => 'related',
    ])->assertSessionHasErrors('source_control_id');
});

test('index searches clauses and applies framework type and coverage filters', function () {
    ControlMapping::factory()->create([
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->target->id,
        'mapping_type' => 'supports',
        'coverage' => 'low',
    ]);
    $otherSource = crosswalkControl('REF-OTHER', 'O-99');
    $otherTarget = crosswalkControl('REF-TARGET-2', 'X-99');
    ControlMapping::factory()->create([
        'source_control_id' => $otherSource->id,
        'target_control_id' => $otherTarget->id,
        'mapping_type' => 'equivalent',
        'coverage' => 'full',
    ]);

    $this->actingAs($this->user)
        ->get(route('crosswalk.index', [
            'search' => 'S-1',
            'source_framework' => 'REF-SOURCE',
            'target_framework' => 'REF-TARGET',
            'mapping_type' => 'supports',
            'coverage' => 'low',
        ]))
        ->assertOk()
        ->assertSee('S-1')
        ->assertDontSee('O-99');
});

test('control detail displays outgoing and incoming mappings', function () {
    $incomingSource = crosswalkControl('REF-INCOMING', 'I-1');
    ControlMapping::factory()->create([
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->target->id,
        'mapping_type' => 'covers',
    ]);
    ControlMapping::factory()->create([
        'source_control_id' => $incomingSource->id,
        'target_control_id' => $this->source->id,
        'mapping_type' => 'supports',
    ]);

    $this->actingAs($this->admin)
        ->get("/alice/show/{$this->source->id}")
        ->assertOk()
        ->assertSee('REF-TARGET')
        ->assertSee('REF-INCOMING');
});

test('control detail labels an incoming supports relation as supported by', function () {
    ControlMapping::factory()->create([
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->target->id,
        'mapping_type' => 'supports',
    ]);

    $this->actingAs($this->admin)
        ->get("/alice/show/{$this->target->id}")
        ->assertOk()
        ->assertSee('Supported by');
});
