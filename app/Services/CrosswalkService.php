<?php

namespace App\Services;

use App\Models\Control;
use App\Models\ControlMapping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class CrosswalkService
{
    /**
     * @return Builder<ControlMapping>
     */
    public function filteredMappings(array $filters): Builder
    {
        return ControlMapping::query()
            ->with([
                'sourceControl.domain',
                'targetControl.domain',
                'validator',
            ])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->whereHas('sourceControl', function (Builder $query) use ($search): void {
                            $query->where('clause', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('targetControl', function (Builder $query) use ($search): void {
                            $query->where('clause', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->when($filters['source_framework'] ?? null, function (Builder $query, string $framework): void {
                $query->whereHas('sourceControl.domain', function (Builder $query) use ($framework): void {
                    $query->where('framework', $framework);
                });
            })
            ->when($filters['target_framework'] ?? null, function (Builder $query, string $framework): void {
                $query->whereHas('targetControl.domain', function (Builder $query) use ($framework): void {
                    $query->where('framework', $framework);
                });
            })
            ->when($filters['mapping_type'] ?? null, function (Builder $query, string $mappingType): void {
                $query->where('mapping_type', $mappingType);
            })
            ->when($filters['coverage'] ?? null, function (Builder $query, string $coverage): void {
                $query->where('coverage', $coverage);
            });
    }

    /**
     * @return EloquentCollection<int, Control>
     */
    public function controlsForFramework(string $framework): EloquentCollection
    {
        return Control::query()
            ->with('domain')
            ->whereHas('domain', function (Builder $query) use ($framework): void {
                $query->where('framework', $framework);
            })
            ->orderBy('clause')
            ->get();
    }

    /**
     * Return mappings as seen from the requested source framework. A stored
     * target-to-source relation is reversed in memory; no mirror row is made.
     */
    public function directionalMappings(
        string $sourceFramework,
        string $targetFramework,
        array $filters = []
    ): Collection {
        $mappings = ControlMapping::query()
            ->with(['sourceControl.domain', 'targetControl.domain', 'validator'])
            ->where(function (Builder $query) use ($sourceFramework, $targetFramework): void {
                $query
                    ->where(function (Builder $query) use ($sourceFramework, $targetFramework): void {
                        $query
                            ->whereHas('sourceControl.domain', function (Builder $query) use ($sourceFramework): void {
                                $query->where('framework', $sourceFramework);
                            })
                            ->whereHas('targetControl.domain', function (Builder $query) use ($targetFramework): void {
                                $query->where('framework', $targetFramework);
                            });
                    })
                    ->orWhere(function (Builder $query) use ($sourceFramework, $targetFramework): void {
                        $query
                            ->whereHas('sourceControl.domain', function (Builder $query) use ($targetFramework): void {
                                $query->where('framework', $targetFramework);
                            })
                            ->whereHas('targetControl.domain', function (Builder $query) use ($sourceFramework): void {
                                $query->where('framework', $sourceFramework);
                            });
                    });
            })
            ->when($filters['coverage'] ?? null, function (Builder $query, string $coverage): void {
                $query->where('coverage', $coverage);
            })
            ->get();

        $directionalMappings = $mappings
            ->map(function (ControlMapping $mapping) use ($sourceFramework): array {
                $isStoredDirection = $mapping->sourceControl->domain->framework === $sourceFramework;

                return [
                    'mapping' => $mapping,
                    'source_control' => $isStoredDirection
                        ? $mapping->sourceControl
                        : $mapping->targetControl,
                    'target_control' => $isStoredDirection
                        ? $mapping->targetControl
                        : $mapping->sourceControl,
                    'mapping_type' => $isStoredDirection
                        ? $mapping->mapping_type
                        : ControlMapping::inverseMappingType($mapping->mapping_type),
                    'reversed' => ! $isStoredDirection,
                ];
            });

        if (isset($filters['mapping_type'])) {
            return $directionalMappings
                ->where('mapping_type', $filters['mapping_type'])
                ->values();
        }

        return $directionalMappings;
    }

    public function matrix(
        string $sourceFramework,
        string $targetFramework,
        array $filters = []
    ): array {
        $sourceControls = $this->controlsForFramework($sourceFramework);
        $mappings = $this->directionalMappings($sourceFramework, $targetFramework, $filters);
        $mappingsBySource = $mappings->groupBy(
            fn (array $item): int => $item['source_control']->id
        );

        $rows = $sourceControls->map(function (Control $control) use ($mappingsBySource): array {
            return [
                'control' => $control,
                'mappings' => $mappingsBySource->get($control->id, collect()),
            ];
        });

        $mappedSourceCount = $mappingsBySource->keys()->count();

        if ($filters['unmapped'] ?? false) {
            $rows = $rows
                ->filter(fn (array $row): bool => $row['mappings']->isEmpty())
                ->values();
        }

        return [
            'rows' => $rows,
            'mappings' => $mappings,
            'stats' => [
                'source_total' => $sourceControls->count(),
                'mapped_source' => $mappedSourceCount,
                'unmapped_source' => $sourceControls->count() - $mappedSourceCount,
                'relations' => $mappings->count(),
            ],
        ];
    }

    public function exportRows(array $filters): Collection
    {
        $sourceFramework = $filters['source_framework'] ?? null;
        $targetFramework = $filters['target_framework'] ?? null;

        if ($sourceFramework && $targetFramework && ($filters['directional'] ?? false)) {
            if ($filters['unmapped'] ?? false) {
                return $this->matrix($sourceFramework, $targetFramework, $filters)['rows']
                    ->map(function (array $row): array {
                        $control = $row['control'];
                        assert($control instanceof Control);

                        return $this->exportUnmappedRow($control);
                    });
            }

            return $this->directionalMappings($sourceFramework, $targetFramework, $filters)
                ->map(fn (array $item): array => $this->exportRow(
                    $item['mapping'],
                    $item['source_control'],
                    $item['target_control'],
                    $item['mapping_type']
                ));
        }

        return $this->filteredMappings($filters)
            ->orderBy('source_control_id')
            ->orderBy('target_control_id')
            ->get()
            ->map(fn (ControlMapping $mapping): array => $this->exportRow(
                $mapping,
                $mapping->sourceControl,
                $mapping->targetControl,
                $mapping->mapping_type
            ));
    }

    private function exportRow(
        ControlMapping $mapping,
        Control $sourceControl,
        Control $targetControl,
        string $mappingType
    ): array {
        return [
            'source_framework' => $sourceControl->domain->framework,
            'source_clause' => trim($sourceControl->clause),
            'target_framework' => $targetControl->domain->framework,
            'target_clause' => trim($targetControl->clause),
            'mapping_type' => $mappingType,
            'coverage' => $mapping->coverage,
            'rationale' => $mapping->rationale,
            'source_reference' => $mapping->source_reference,
            'source_url' => $mapping->source_url,
        ];
    }

    private function exportUnmappedRow(Control $sourceControl): array
    {
        return [
            'source_framework' => $sourceControl->domain->framework,
            'source_clause' => trim($sourceControl->clause),
            'target_framework' => null,
            'target_clause' => null,
            'mapping_type' => null,
            'coverage' => null,
            'rationale' => null,
            'source_reference' => null,
            'source_url' => null,
        ];
    }
}
