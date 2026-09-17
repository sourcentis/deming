<?php

use App\Models\Control;
use App\Models\ControlMapping;
use App\Models\Domain;
use App\Models\Framework;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

test('crosswalk migrations expose the expected schema', function () {
    expect(Schema::hasColumns('frameworks', [
        'id',
        'code',
        'name',
        'version',
        'publisher',
        'jurisdiction',
        'source_url',
        'status',
        'publication_date',
        'effective_date',
        'notes',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('control_mappings', [
        'id',
        'source_control_id',
        'target_control_id',
        'mapping_type',
        'coverage',
        'confidence',
        'rationale',
        'source_reference',
        'source_url',
        'validated',
        'validated_by',
        'validated_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});

test('an existing domain framework code is preserved as framework metadata', function () {
    $domain = Domain::factory()->create(['framework' => 'NIS2-ReCyF-2.5-FR']);

    $this->assertDatabaseHas('domains', [
        'id' => $domain->id,
        'framework' => 'NIS2-ReCyF-2.5-FR',
    ]);
    $this->assertDatabaseHas('frameworks', [
        'code' => 'NIS2-ReCyF-2.5-FR',
        'name' => 'NIS2-ReCyF-2.5-FR',
    ]);
});

test('framework metadata keeps the exact legacy domain code', function () {
    $domain = Domain::factory()->create(['framework' => 'LEGACY-REF ']);

    expect($domain->framework)->toBe('LEGACY-REF ');
    $this->assertDatabaseHas('frameworks', [
        'code' => 'LEGACY-REF ',
        'name' => 'LEGACY-REF ',
    ]);
});

test('framework domain and control relationships use domain framework without replacing it', function () {
    $domain = Domain::factory()->create(['framework' => 'REF-A']);
    $control = Control::factory()->create(['domain_id' => $domain->id]);
    $framework = Framework::query()->where('code', 'REF-A')->firstOrFail();

    expect($domain->framework)->toBe('REF-A')
        ->and($domain->frameworkMetadata->is($framework))->toBeTrue()
        ->and($framework->domains->contains($domain))->toBeTrue()
        ->and($framework->controls->contains($control))->toBeTrue();
});

test('control exposes outgoing and incoming mappings with their validator', function () {
    $source = Control::factory()->create();
    $target = Control::factory()->create();
    $validator = User::factory()->admin()->create();

    $mapping = ControlMapping::factory()->create([
        'source_control_id' => $source->id,
        'target_control_id' => $target->id,
        'mapping_type' => 'covers',
        'validated' => true,
        'validated_by' => $validator->id,
        'validated_at' => now(),
    ]);

    expect($source->outgoingMappings->first()->is($mapping))->toBeTrue()
        ->and($target->incomingMappings->first()->is($mapping))->toBeTrue()
        ->and($mapping->sourceControl->is($source))->toBeTrue()
        ->and($mapping->targetControl->is($target))->toBeTrue()
        ->and($mapping->validator->is($validator))->toBeTrue()
        ->and(ControlMapping::inverseMappingType('covers'))->toBe('covered_by')
        ->and(ControlMapping::inverseMappingType('partial'))->toBe('partial');
});

test('source and target pair is unique', function () {
    $source = Control::factory()->create();
    $target = Control::factory()->create();

    ControlMapping::factory()->create([
        'source_control_id' => $source->id,
        'target_control_id' => $target->id,
    ]);

    expect(fn () => ControlMapping::factory()->create([
        'source_control_id' => $source->id,
        'target_control_id' => $target->id,
    ]))->toThrow(QueryException::class);
});
