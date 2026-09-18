<?php

use App\Models\Control;
use App\Models\ControlMapping;
use App\Models\Domain;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function mappingWorkbook(array $rows, bool $includeConfidence = true): string
{
    $headers = [
        'source_framework',
        'source_clause',
        'target_framework',
        'target_clause',
        'mapping_type',
        'coverage',
        'confidence',
        'rationale',
        'source_reference',
        'source_url',
    ];
    if (! $includeConfidence) {
        $headers = array_values(array_diff($headers, ['confidence']));
    }

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([
        $headers,
        ...array_map(
            fn (array $row): array => array_map(
                fn (string $header) => $row[$header] ?? null,
                $headers
            ),
            $rows
        ),
    ]);

    $path = tempnam(sys_get_temp_dir(), 'deming-mappings-');
    (new Xlsx($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

function validMappingRow(array $overrides = []): array
{
    return array_merge([
        'source_framework' => 'REF-A',
        'source_clause' => 'A-1',
        'target_framework' => 'REF-B',
        'target_clause' => 'B-1',
        'mapping_type' => 'related',
        'coverage' => 'medium',
        'rationale' => 'Officially documented correspondence.',
        'source_reference' => 'Official table, row 1',
        'source_url' => 'https://example.test/crosswalk',
    ], $overrides);
}

beforeEach(function () {
    $this->mappingFiles = [];
    $sourceDomain = Domain::factory()->create(['framework' => 'REF-A']);
    $targetDomain = Domain::factory()->create(['framework' => 'REF-B']);
    $this->source = Control::factory()->create([
        'domain_id' => $sourceDomain->id,
        'clause' => 'A-1',
    ]);
    $this->target = Control::factory()->create([
        'domain_id' => $targetDomain->id,
        'clause' => 'B-1',
    ]);
});

afterEach(function () {
    foreach ($this->mappingFiles as $path) {
        if (is_file($path)) {
            unlink($path);
        }
    }
});

test('dry run validates the workbook without writing', function () {
    $path = mappingWorkbook([validMappingRow()]);
    $this->mappingFiles[] = $path;

    $this->artisan('deming:import-mappings', [
        'filename' => $path,
        '--dry-run' => true,
    ])
        ->expectsOutputToContain('Dry run successful: 1 row(s) valid, 1 to create')
        ->assertSuccessful();

    expect(ControlMapping::query()->count())->toBe(0);
});

test('normal import creates a mapping with documentary provenance', function () {
    $path = mappingWorkbook([validMappingRow()]);
    $this->mappingFiles[] = $path;

    $this->artisan('deming:import-mappings', ['filename' => $path])
        ->expectsOutputToContain('Import complete: 1 created, 0 updated, 0 unchanged.')
        ->assertSuccessful();

    $this->assertDatabaseHas('control_mappings', [
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->target->id,
        'mapping_type' => 'related',
        'coverage' => 'medium',
        'rationale' => 'Officially documented correspondence.',
        'source_reference' => 'Official table, row 1',
        'source_url' => 'https://example.test/crosswalk',
    ]);
});

test('import preserves optional confidence from an exported workbook', function () {
    $path = mappingWorkbook([validMappingRow(['confidence' => 92.75])]);
    $this->mappingFiles[] = $path;

    $this->artisan('deming:import-mappings', ['filename' => $path])
        ->assertSuccessful();

    expect(ControlMapping::query()->sole()->confidence)->toBe('92.75');
});

test('update without a confidence column preserves the existing value', function () {
    $mapping = ControlMapping::factory()->create([
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->target->id,
        'mapping_type' => 'partial',
        'coverage' => 'low',
        'confidence' => 73.25,
    ]);
    $path = mappingWorkbook([validMappingRow()], false);
    $this->mappingFiles[] = $path;

    $this->artisan('deming:import-mappings', [
        'filename' => $path,
        '--update' => true,
    ])->assertSuccessful();

    $mapping->refresh();
    expect($mapping->mapping_type)->toBe('related')
        ->and($mapping->confidence)->toBe('73.25');
});

test('import rejects confidence outside the zero to one hundred range', function () {
    $path = mappingWorkbook([validMappingRow(['confidence' => 101])]);
    $this->mappingFiles[] = $path;

    $this->artisan('deming:import-mappings', ['filename' => $path])
        ->expectsOutputToContain('confidence must be a number between 0 and 100.')
        ->assertFailed();

    expect(ControlMapping::query()->count())->toBe(0);
});

test('update changes an existing mapping and clears its validation', function () {
    $mapping = ControlMapping::factory()->create([
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->target->id,
        'mapping_type' => 'partial',
        'coverage' => 'low',
        'validated' => true,
        'validated_by' => User::factory()->admin()->create()->id,
        'validated_at' => now(),
    ]);
    $path = mappingWorkbook([validMappingRow([
        'mapping_type' => 'supports',
        'coverage' => 'high',
    ])]);
    $this->mappingFiles[] = $path;

    $this->artisan('deming:import-mappings', [
        'filename' => $path,
        '--update' => true,
    ])
        ->expectsOutputToContain('Import complete: 0 created, 1 updated, 0 unchanged.')
        ->assertSuccessful();

    $mapping->refresh();
    expect($mapping->mapping_type)->toBe('supports')
        ->and($mapping->coverage)->toBe('high')
        ->and($mapping->validated)->toBeFalse()
        ->and($mapping->validated_by)->toBeNull()
        ->and($mapping->validated_at)->toBeNull();
});

test('existing pair requires the update option', function () {
    ControlMapping::factory()->create([
        'source_control_id' => $this->source->id,
        'target_control_id' => $this->target->id,
    ]);
    $path = mappingWorkbook([validMappingRow()]);
    $this->mappingFiles[] = $path;

    $this->artisan('deming:import-mappings', ['filename' => $path])
        ->expectsOutputToContain('This source/target mapping already exists (use --update).')
        ->assertFailed();

    expect(ControlMapping::query()->count())->toBe(1);
});

test('duplicate rows abort the whole import', function () {
    $path = mappingWorkbook([validMappingRow(), validMappingRow()]);
    $this->mappingFiles[] = $path;

    $this->artisan('deming:import-mappings', ['filename' => $path])
        ->expectsOutputToContain('Duplicate source/target pair in workbook')
        ->assertFailed();

    expect(ControlMapping::query()->count())->toBe(0);
});

test('unknown frameworks and missing clauses are reported without writes', function () {
    $path = mappingWorkbook([
        validMappingRow(['source_framework' => 'UNKNOWN']),
        validMappingRow(['source_clause' => 'A-404']),
        validMappingRow(['mapping_type' => 'same', 'coverage' => 'maximum']),
    ]);
    $this->mappingFiles[] = $path;

    $this->artisan('deming:import-mappings', ['filename' => $path])
        ->expectsOutputToContain('Unknown source framework: UNKNOWN.')
        ->expectsOutputToContain('Source clause "A-404" does not exist in framework "REF-A".')
        ->expectsOutputToContain('Invalid mapping_type.')
        ->expectsOutputToContain('Invalid coverage.')
        ->assertFailed();

    expect(ControlMapping::query()->count())->toBe(0);
});

test('a write failure rolls back mappings already inserted in the transaction', function () {
    $secondTarget = Control::factory()->create([
        'domain_id' => $this->target->domain_id,
        'clause' => 'B-2',
    ]);
    $path = mappingWorkbook([
        validMappingRow(),
        validMappingRow(['target_clause' => 'B-2']),
    ]);
    $this->mappingFiles[] = $path;

    $eventName = 'eloquent.creating: '.ControlMapping::class;
    Event::listen($eventName, function (ControlMapping $mapping) use ($secondTarget): void {
        if ($mapping->target_control_id === $secondTarget->id) {
            throw new RuntimeException('forced failure');
        }
    });

    try {
        $this->artisan('deming:import-mappings', ['filename' => $path])
            ->expectsOutputToContain('Import failed and was rolled back:')
            ->assertFailed();
    } finally {
        Event::forget($eventName);
    }

    expect(ControlMapping::query()->count())->toBe(0);
});
