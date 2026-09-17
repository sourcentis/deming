<?php

use App\Models\Control;
use App\Models\ControlMapping;
use App\Models\Domain;
use PhpOffice\PhpSpreadsheet\IOFactory;

test('official ReCyF to ISO workbook is structurally valid and conservative', function () {
    $mappingPath = storage_path('app/repository/ReCyF-2.5-ISO27001-2022.mappings.xlsx');
    $isoPath = storage_path('app/repository/ISO27001-2022.fr.xlsx');

    expect($mappingPath)->toBeFile()
        ->and($isoPath)->toBeFile();

    $mappingBook = IOFactory::load($mappingPath);
    $mappingRows = $mappingBook->getActiveSheet()->toArray(null, true, true, false);
    $mappingBook->disconnectWorksheets();

    $headers = array_shift($mappingRows);
    expect($headers)->toBe([
        'source_framework',
        'source_clause',
        'target_framework',
        'target_clause',
        'mapping_type',
        'coverage',
        'rationale',
        'source_reference',
        'source_url',
    ])->and($mappingRows)->toHaveCount(281);

    $isoBook = IOFactory::load($isoPath);
    $isoRows = $isoBook->getActiveSheet()->toArray(null, true, true, false);
    $isoBook->disconnectWorksheets();
    array_shift($isoRows);
    $isoClauses = array_fill_keys(
        array_map(
            fn (mixed $clause): string => trim((string) $clause),
            array_column($isoRows, 3)
        ),
        true
    );

    $pairs = [];
    $sourceClauses = [];
    $targetClauses = [];

    foreach ($mappingRows as $row) {
        [$sourceFramework, $sourceClause, $targetFramework, $targetClause,
            $mappingType, $coverage, $rationale, $sourceReference, $sourceUrl] = $row;

        $pair = $sourceClause.'|'.$targetClause;
        expect($sourceFramework)->toBe('NIS2-ReCyF-2.5-FR')
            ->and($targetFramework)->toBe('27001:2022')
            ->and($mappingType)->toBe('related')
            ->and($mappingType)->toBeIn(ControlMapping::MAPPING_TYPES)
            ->and($coverage)->toBeIn(ControlMapping::COVERAGE_LEVELS)
            ->and($rationale)->not->toBeEmpty()
            ->and($sourceReference)->toContain('ANSSI')
            ->and($sourceUrl)->toBe('https://messervices.cyber.gouv.fr/nis2#exigences')
            ->and(isset($isoClauses[$targetClause]))->toBeTrue()
            ->and(isset($pairs[$pair]))->toBeFalse();

        $pairs[$pair] = true;
        $sourceClauses[$sourceClause] = true;
        $targetClauses[$targetClause] = true;
    }

    expect($sourceClauses)->toHaveCount(118)
        ->and($targetClauses)->toHaveCount(66);
});

test('official workbook imports transactionally when both control sets are present', function () {
    $mappingPath = storage_path('app/repository/ReCyF-2.5-ISO27001-2022.mappings.xlsx');
    $mappingBook = IOFactory::load($mappingPath);
    $mappingRows = $mappingBook->getActiveSheet()->toArray(null, true, true, false);
    $mappingBook->disconnectWorksheets();
    array_shift($mappingRows);

    $sourceDomain = Domain::factory()->create(['framework' => 'NIS2-ReCyF-2.5-FR']);
    $targetDomain = Domain::factory()->create(['framework' => '27001:2022']);

    foreach (array_unique(array_column($mappingRows, 1)) as $clause) {
        Control::factory()->create([
            'domain_id' => $sourceDomain->id,
            'clause' => $clause,
            'name' => "ReCyF {$clause}",
        ]);
    }

    foreach (array_unique(array_column($mappingRows, 3)) as $clause) {
        Control::factory()->create([
            'domain_id' => $targetDomain->id,
            'clause' => $clause,
            'name' => "ISO {$clause}",
        ]);
    }

    $this->artisan('deming:import-mappings', ['filename' => $mappingPath])
        ->expectsOutputToContain('Import complete: 281 created, 0 updated, 0 unchanged.')
        ->assertSuccessful();

    expect(ControlMapping::query()->count())->toBe(281);
});
