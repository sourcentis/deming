<?php

namespace App\Console\Commands;

use App\Models\Control;
use App\Models\ControlMapping;
use App\Models\Framework;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Throwable;

class ImportMappings extends Command
{
    private const HEADERS = [
        'source_framework',
        'source_clause',
        'target_framework',
        'target_clause',
        'mapping_type',
        'coverage',
        'rationale',
        'source_reference',
        'source_url',
    ];

    private const OPTIONAL_HEADERS = [
        'confidence',
    ];

    protected $signature = 'deming:import-mappings
                            {filename : XLSX file containing control mappings}
                            {--dry-run : Validate and report without writing anything}
                            {--update : Update mappings that already exist}';

    protected $description = 'Import generic control mappings from an XLSX file';

    public function handle(): int
    {
        $filename = (string) $this->argument('filename');

        if (! is_file($filename)) {
            $this->components->error("File does not exist: {$filename}");

            return self::FAILURE;
        }

        try {
            $data = $this->readWorkbook($filename);
        } catch (Throwable $exception) {
            $this->components->error('Unable to read XLSX file: '.$exception->getMessage());

            return self::FAILURE;
        }

        [$rows, $errors, $counts] = $this->validateRows($data);

        if ($errors !== []) {
            $this->components->error('Import aborted. No mapping was changed.');
            $this->table(['Line', 'Error'], $errors);

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->components->info(sprintf(
                'Dry run successful: %d row(s) valid, %d to create, %d to update, %d unchanged.',
                count($rows),
                $counts['create'],
                $counts['update'],
                $counts['unchanged']
            ));

            return self::SUCCESS;
        }

        try {
            DB::transaction(function () use ($rows): void {
                foreach ($rows as $row) {
                    $mapping = $row['mapping'] ?? new ControlMapping;
                    $mapping->fill($row['attributes']);

                    if ($mapping->exists && $mapping->isDirty()) {
                        // A modified relation must be explicitly validated again.
                        $mapping->validated = false;
                        $mapping->validated_by = null;
                        $mapping->validated_at = null;
                    }

                    if (! $mapping->exists || $mapping->isDirty()) {
                        $mapping->save();
                    }
                }
            });
        } catch (Throwable $exception) {
            $this->components->error(
                'Import failed and was rolled back: '.$exception->getMessage()
            );

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Import complete: %d created, %d updated, %d unchanged.',
            $counts['create'],
            $counts['update'],
            $counts['unchanged']
        ));

        return self::SUCCESS;
    }

    private function readWorkbook(string $filename): array
    {
        $reader = new Xlsx;
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filename);

        try {
            return $spreadsheet
                ->getSheet($spreadsheet->getFirstSheetIndex())
                ->toArray(null, true, true, false);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function validateRows(array $data): array
    {
        $errors = [];
        $rows = [];
        $counts = ['create' => 0, 'update' => 0, 'unchanged' => 0];

        if ($data === []) {
            return [$rows, [[1, 'The workbook is empty.']], $counts];
        }

        $headers = [];
        foreach ($data[0] as $index => $header) {
            $normalized = strtolower(trim((string) $header));
            if ($normalized !== '') {
                $headers[$normalized] = $index;
            }
        }

        $missingHeaders = array_values(array_diff(self::HEADERS, array_keys($headers)));
        if ($missingHeaders !== []) {
            return [
                $rows,
                [[1, 'Missing column(s): '.implode(', ', $missingHeaders)]],
                $counts,
            ];
        }

        $hasConfidenceColumn = isset($headers['confidence']);
        $frameworks = Framework::query()->pluck('code')->flip();
        $seenPairs = [];

        foreach (array_slice($data, 1, null, true) as $index => $values) {
            $line = $index + 1;
            $raw = [];

            foreach ([...self::HEADERS, ...self::OPTIONAL_HEADERS] as $header) {
                $value = isset($headers[$header])
                    ? ($values[$headers[$header]] ?? null)
                    : null;
                $raw[$header] = is_string($value) ? trim($value) : $value;
            }

            if ($this->isBlankRow($raw)) {
                continue;
            }

            $rowErrors = $this->validateScalarValues($raw, $frameworks);
            if ($rowErrors !== []) {
                foreach ($rowErrors as $error) {
                    $errors[] = [$line, $error];
                }

                continue;
            }

            $sourceControls = $this->findControls(
                (string) $raw['source_framework'],
                (string) $raw['source_clause']
            );
            $targetControls = $this->findControls(
                (string) $raw['target_framework'],
                (string) $raw['target_clause']
            );

            if ($sourceControls->isEmpty()) {
                $errors[] = [$line, sprintf(
                    'Source clause "%s" does not exist in framework "%s".',
                    $raw['source_clause'],
                    $raw['source_framework']
                )];
            } elseif ($sourceControls->count() > 1) {
                $errors[] = [$line, sprintf(
                    'Source clause "%s" is ambiguous in framework "%s".',
                    $raw['source_clause'],
                    $raw['source_framework']
                )];
            }

            if ($targetControls->isEmpty()) {
                $errors[] = [$line, sprintf(
                    'Target clause "%s" does not exist in framework "%s".',
                    $raw['target_clause'],
                    $raw['target_framework']
                )];
            } elseif ($targetControls->count() > 1) {
                $errors[] = [$line, sprintf(
                    'Target clause "%s" is ambiguous in framework "%s".',
                    $raw['target_clause'],
                    $raw['target_framework']
                )];
            }

            if ($sourceControls->count() !== 1 || $targetControls->count() !== 1) {
                continue;
            }

            $sourceControl = $sourceControls->first();
            $targetControl = $targetControls->first();

            if ($sourceControl->id === $targetControl->id) {
                $errors[] = [$line, 'A control cannot be mapped to itself.'];

                continue;
            }

            $pair = $sourceControl->id.':'.$targetControl->id;
            if (isset($seenPairs[$pair])) {
                $errors[] = [$line, sprintf(
                    'Duplicate source/target pair in workbook (already declared on line %d).',
                    $seenPairs[$pair]
                )];

                continue;
            }
            $seenPairs[$pair] = $line;

            $mapping = ControlMapping::query()
                ->where('source_control_id', $sourceControl->id)
                ->where('target_control_id', $targetControl->id)
                ->first();

            if ($mapping !== null && ! $this->option('update')) {
                $errors[] = [$line, 'This source/target mapping already exists (use --update).'];

                continue;
            }

            $attributes = [
                'source_control_id' => $sourceControl->id,
                'target_control_id' => $targetControl->id,
                'mapping_type' => $raw['mapping_type'],
                'coverage' => $raw['coverage'] !== '' ? $raw['coverage'] : null,
                'rationale' => $raw['rationale'] !== '' ? $raw['rationale'] : null,
                'source_reference' => $raw['source_reference'] !== ''
                    ? $raw['source_reference']
                    : null,
                'source_url' => $raw['source_url'] !== '' ? $raw['source_url'] : null,
            ];
            if ($hasConfidenceColumn) {
                $attributes['confidence'] = $raw['confidence'] !== null
                    && $raw['confidence'] !== ''
                        ? (float) $raw['confidence']
                        : null;
            }

            if ($mapping === null) {
                $counts['create']++;
            } else {
                $mapping->fill($attributes);
                $counts[$mapping->isDirty() ? 'update' : 'unchanged']++;
                $mapping->refresh();
            }

            $rows[] = [
                'mapping' => $mapping,
                'attributes' => $attributes,
            ];
        }

        return [$rows, $errors, $counts];
    }

    private function validateScalarValues(array $row, Collection $frameworks): array
    {
        $errors = [];

        foreach (['source_framework', 'source_clause', 'target_framework', 'target_clause', 'mapping_type'] as $required) {
            if ($row[$required] === null || $row[$required] === '') {
                $errors[] = "{$required} is required.";
            }
        }

        if ($row['source_framework'] !== null && $row['source_framework'] !== ''
            && ! $frameworks->has((string) $row['source_framework'])) {
            $errors[] = "Unknown source framework: {$row['source_framework']}.";
        }

        if ($row['target_framework'] !== null && $row['target_framework'] !== ''
            && ! $frameworks->has((string) $row['target_framework'])) {
            $errors[] = "Unknown target framework: {$row['target_framework']}.";
        }

        if ($row['mapping_type'] !== null && $row['mapping_type'] !== ''
            && ! in_array($row['mapping_type'], ControlMapping::MAPPING_TYPES, true)) {
            $errors[] = 'Invalid mapping_type. Allowed values: '
                .implode(', ', ControlMapping::MAPPING_TYPES).'.';
        }

        if ($row['coverage'] !== null && $row['coverage'] !== ''
            && ! in_array($row['coverage'], ControlMapping::COVERAGE_LEVELS, true)) {
            $errors[] = 'Invalid coverage. Allowed values: '
                .implode(', ', ControlMapping::COVERAGE_LEVELS).'.';
        }

        if ($row['confidence'] !== null && $row['confidence'] !== '') {
            if (! is_numeric($row['confidence'])
                || (float) $row['confidence'] < 0
                || (float) $row['confidence'] > 100) {
                $errors[] = 'confidence must be a number between 0 and 100.';
            }
        }

        if (is_string($row['source_reference']) && mb_strlen($row['source_reference']) > 255) {
            $errors[] = 'source_reference may not exceed 255 characters.';
        }

        if (is_string($row['source_url']) && $row['source_url'] !== '') {
            if (mb_strlen($row['source_url']) > 2048) {
                $errors[] = 'source_url may not exceed 2048 characters.';
            } elseif (filter_var($row['source_url'], FILTER_VALIDATE_URL) === false) {
                $errors[] = 'source_url must be a valid URL.';
            }
        }

        return $errors;
    }

    /**
     * @return EloquentCollection<int, Control>
     */
    private function findControls(string $framework, string $clause): EloquentCollection
    {
        return Control::query()
            ->join('domains', 'domains.id', '=', 'controls.domain_id')
            ->where('domains.framework', $framework)
            ->whereRaw('TRIM(controls.clause) = ?', [trim($clause)])
            ->select('controls.*')
            ->get();
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return false;
            }
        }

        return true;
    }
}
