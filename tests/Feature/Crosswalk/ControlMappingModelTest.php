<?php

use App\Models\Control;
use App\Models\ControlMapping;
use App\Models\Domain;
use App\Models\Framework;
use App\Models\User;
use Database\Seeders\DomainSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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

    $indexes = collect(Schema::getIndexes('control_mappings'));
    expect($indexes->contains(
        fn (array $index): bool => $index['columns'] === ['target_control_id']
    ))->toBeTrue();
});

test('domain seeder synchronizes framework metadata after bulk inserts', function () {
    $this->seed(DomainSeeder::class);

    expect(Domain::query()->whereKey(25)->value('framework'))->toBe('27001:2022')
        ->and(Domain::query()->distinct()->pluck('framework')->all())
        ->toBe(['27001:2022']);

    $frameworkCodes = Domain::query()
        ->whereNotNull('framework')
        ->where('framework', '<>', '')
        ->distinct()
        ->pluck('framework')
        ->sort()
        ->values();

    expect(Framework::query()->orderBy('code')->pluck('code')->values())
        ->toEqual($frameworkCodes);

    $domainCreatedAfterSeed = Domain::factory()->create([
        'framework' => 'CREATED-AFTER-SEED',
    ]);
    expect($domainCreatedAfterSeed->id)->toBeGreaterThan(29);
});

test('domain seeder preserves existing domains referenced by controls', function () {
    $domain = Domain::factory()->create([
        'framework' => 'CUSTOM-REFERENCED-REF',
    ]);
    $control = Control::factory()->create(['domain_id' => $domain->id]);

    $this->seed(DomainSeeder::class);

    $this->assertDatabaseHas('domains', [
        'id' => $domain->id,
        'framework' => 'CUSTOM-REFERENCED-REF',
    ]);
    $this->assertDatabaseHas('controls', [
        'id' => $control->id,
        'domain_id' => $domain->id,
    ]);
});

test('framework migration backfills exact codes from domains that already exist', function () {
    $connection = 'crosswalk_migration_test';
    $originalConnection = DB::getDefaultConnection();
    config([
        "database.connections.{$connection}" => array_merge(
            config('database.connections.sqlite'),
            ['database' => ':memory:']
        ),
    ]);
    DB::setDefaultConnection($connection);

    try {
        Schema::create('domains', function ($table): void {
            $table->id();
            $table->string('framework')->nullable();
        });
        DB::table('domains')->insert(['framework' => 'PRE-EXISTING-REF ']);

        $migration = require database_path(
            'migrations/2026_09_16_000001_create_frameworks_table.php'
        );
        $migration->up();

        expect(DB::table('frameworks')->where([
            'code' => 'PRE-EXISTING-REF ',
            'name' => 'PRE-EXISTING-REF ',
        ])->exists())->toBeTrue();
    } finally {
        DB::setDefaultConnection($originalConnection);
        DB::purge($connection);
    }
});

test('saving a domain creates framework metadata without changing its code', function () {
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
        ->and(ControlMapping::inverseMappingType('supports'))->toBe('supported_by')
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
