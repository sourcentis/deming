<?php

use App\Exports\ControlMappingsExport;
use App\Models\Control;
use App\Models\ControlMapping;
use App\Models\Domain;
use App\Models\User;
use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Facades\Excel;

function matrixControl(string $framework, string $clause): Control
{
    $domain = Domain::factory()->create(['framework' => $framework]);

    return Control::factory()->create([
        'domain_id' => $domain->id,
        'clause' => $clause,
        'name' => "Matrix {$clause}",
    ]);
}

beforeEach(function () {
    $this->user = User::factory()->user()->create();
    $this->sourceOne = matrixControl('MATRIX-A', 'MA-1');
    $this->sourceTwo = matrixControl('MATRIX-A', 'MA-2');
    $this->targetOne = matrixControl('MATRIX-B', 'MB-1');
    $this->targetTwo = matrixControl('MATRIX-B', 'MB-2');

    $this->reverseStored = ControlMapping::factory()->create([
        'source_control_id' => $this->targetOne->id,
        'target_control_id' => $this->sourceOne->id,
        'mapping_type' => 'covers',
        'coverage' => 'high',
    ]);
    $this->directStored = ControlMapping::factory()->create([
        'source_control_id' => $this->sourceOne->id,
        'target_control_id' => $this->targetTwo->id,
        'mapping_type' => 'supports',
        'coverage' => 'medium',
        'confidence' => 87.5,
    ]);
});

test('matrix reads a single stored relation in both directions', function () {
    $this->actingAs($this->user)
        ->get(route('crosswalk.matrix', [
            'source_framework' => 'MATRIX-A',
            'target_framework' => 'MATRIX-B',
        ]))
        ->assertOk()
        ->assertViewHas('matrix', function (array $matrix): bool {
            $types = $matrix['mappings']->pluck('mapping_type')->all();

            return $matrix['stats'] === [
                'source_total' => 2,
                'mapped_source' => 1,
                'unmapped_source' => 1,
                'relations' => 2,
            ]
                && in_array('covered_by', $types, true)
                && in_array('supports', $types, true)
                && $matrix['mappings']->where('reversed', true)->count() === 1;
        });

    $this->actingAs($this->user)
        ->get(route('crosswalk.matrix', [
            'source_framework' => 'MATRIX-B',
            'target_framework' => 'MATRIX-A',
            'mapping_type' => 'covers',
        ]))
        ->assertOk()
        ->assertViewHas('matrix', function (array $matrix): bool {
            return $matrix['stats']['relations'] === 1
                && $matrix['mappings']->first()['mapping_type'] === 'covers'
                && $matrix['mappings']->first()['reversed'] === false;
        });

    expect(ControlMapping::query()->count())->toBe(2);
});

test('matrix can display only source controls without a filtered mapping', function () {
    $this->actingAs($this->user)
        ->get(route('crosswalk.matrix', [
            'source_framework' => 'MATRIX-A',
            'target_framework' => 'MATRIX-B',
            'unmapped' => 1,
        ]))
        ->assertOk()
        ->assertViewHas('matrix', function (array $matrix): bool {
            return $matrix['stats']['unmapped_source'] === 1
                && $matrix['rows']->count() === 1
                && $matrix['rows']->first()['control']->is($this->sourceTwo)
                && $matrix['rows']->first()['mappings']->isEmpty();
        });
});

test('unmapped controls and metrics remain absolute when relation filters are active', function () {
    $this->actingAs($this->user)
        ->get(route('crosswalk.matrix', [
            'source_framework' => 'MATRIX-A',
            'target_framework' => 'MATRIX-B',
            'mapping_type' => 'equivalent',
            'coverage' => 'low',
            'unmapped' => 1,
        ]))
        ->assertOk()
        ->assertViewHas('matrix', function (array $matrix): bool {
            return $matrix['stats'] === [
                'source_total' => 2,
                'mapped_source' => 1,
                'unmapped_source' => 1,
                'relations' => 0,
            ]
                && $matrix['rows']->count() === 1
                && $matrix['rows']->first()['control']->is($this->sourceTwo);
        });
});

test('matrix labels an incoming supports relation as supported by', function () {
    $this->actingAs($this->user)
        ->get(route('crosswalk.matrix', [
            'source_framework' => 'MATRIX-B',
            'target_framework' => 'MATRIX-A',
            'mapping_type' => 'supported_by',
        ]))
        ->assertOk()
        ->assertViewHas('matrix', function (array $matrix): bool {
            $mapping = $matrix['mappings']->first();

            return $matrix['mappings']->count() === 1
                && $mapping['mapping']->is($this->directStored)
                && $mapping['mapping_type'] === 'supported_by'
                && $mapping['reversed'] === true;
        })
        ->assertSee('Supported by');
});

test('directional XLSX export applies filters and reverses asymmetric mapping types', function () {
    Excel::fake();
    $this->travelTo(CarbonImmutable::parse('2026-09-16 12:34:56'));

    $this->actingAs($this->user)
        ->get(route('crosswalk.export', [
            'source_framework' => 'MATRIX-A',
            'target_framework' => 'MATRIX-B',
            'mapping_type' => 'covered_by',
            'coverage' => 'high',
            'directional' => 1,
        ]))
        ->assertOk();

    Excel::assertDownloaded(
        'control-mappings-20260916-123456.xlsx',
        function (ControlMappingsExport $export): bool {
            $rows = $export->collection();

            return $rows->count() === 1
                && $rows->first()['source_framework'] === 'MATRIX-A'
                && $rows->first()['source_clause'] === 'MA-1'
                && $rows->first()['target_framework'] === 'MATRIX-B'
                && $rows->first()['target_clause'] === 'MB-1'
                && $rows->first()['mapping_type'] === 'covered_by'
                && $rows->first()['coverage'] === 'high';
        }
    );
});

test('directional XLSX export can contain only unmapped source controls', function () {
    Excel::fake();
    $this->travelTo(CarbonImmutable::parse('2026-09-16 12:45:00'));

    $this->actingAs($this->user)
        ->get(route('crosswalk.export', [
            'source_framework' => 'MATRIX-A',
            'target_framework' => 'MATRIX-B',
            'unmapped' => 1,
            'directional' => 1,
        ]))
        ->assertOk();

    Excel::assertDownloaded(
        'control-mappings-20260916-124500.xlsx',
        function (ControlMappingsExport $export): bool {
            $rows = $export->collection();

            return $rows->count() === 1
                && $rows->first()['source_framework'] === 'MATRIX-A'
                && $rows->first()['source_clause'] === 'MA-2'
                && $rows->first()['target_framework'] === null
                && $rows->first()['target_clause'] === null
                && $rows->first()['mapping_type'] === null;
        }
    );
});

test('standard XLSX export applies stored-direction filters', function () {
    Excel::fake();
    $this->travelTo(CarbonImmutable::parse('2026-09-16 13:00:00'));

    $this->actingAs($this->user)
        ->get(route('crosswalk.export', [
            'source_framework' => 'MATRIX-A',
            'target_framework' => 'MATRIX-B',
            'mapping_type' => 'supports',
        ]))
        ->assertOk();

    Excel::assertDownloaded(
        'control-mappings-20260916-130000.xlsx',
        fn (ControlMappingsExport $export): bool => $export->collection()->count() === 1
            && $export->collection()->first()['source_clause'] === 'MA-1'
            && $export->collection()->first()['target_clause'] === 'MB-2'
            && $export->collection()->first()['confidence'] === '87.50'
            && in_array('confidence', $export->headings(), true)
    );
});

test('reverse supports export preserves the stored direction and type', function () {
    Excel::fake();
    $this->travelTo(CarbonImmutable::parse('2026-09-16 13:15:00'));

    $this->actingAs($this->user)
        ->get(route('crosswalk.export', [
            'source_framework' => 'MATRIX-B',
            'target_framework' => 'MATRIX-A',
            'mapping_type' => 'supported_by',
            'directional' => 1,
        ]))
        ->assertOk();

    Excel::assertDownloaded(
        'control-mappings-20260916-131500.xlsx',
        function (ControlMappingsExport $export): bool {
            $row = $export->collection()->first();

            return $export->collection()->count() === 1
                && $row['source_framework'] === 'MATRIX-A'
                && $row['source_clause'] === 'MA-1'
                && $row['target_framework'] === 'MATRIX-B'
                && $row['target_clause'] === 'MB-2'
                && $row['mapping_type'] === 'supports'
                && $row['confidence'] === '87.50';
        }
    );
});
