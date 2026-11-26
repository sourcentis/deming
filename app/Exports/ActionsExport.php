<?php

namespace App\Exports;

use App\Models\Action;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActionsExport implements FromQuery, WithMapping, WithHeadings, WithStyles, WithColumnWidths
{
    public function headings(): array
    {
        return [
            trans('cruds.action.fields.reference'),
            trans('cruds.action.fields.type'),
            trans('cruds.action.fields.due_date'),
            trans('cruds.action.fields.scope'),
            trans('cruds.action.fields.clauses'),
            trans('cruds.action.fields.name'),
            trans('cruds.action.fields.cause'),
            trans('cruds.action.fields.owners'),
            trans('cruds.action.fields.remediation'),
            trans('cruds.action.fields.progress'),
            trans('cruds.action.fields.status'),
            trans('cruds.action.fields.close_date'),
            trans('cruds.action.fields.justification'),
        ];
    }

    public function styles(Worksheet $_sheet)
    {
        // return
        return [
            // Style the first row as bold text.
            1 => ['font' => ['bold' => true],
                'alignment' => [
                    'wrapText' => true,
                    'vertical' => 'top',
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,  // Reference
            'B' => 10,  // Type
            'C' => 10,  // creation_date
            'D' => 20,  // scope
            'E' => 30,  // clause
            'F' => 50,  // name
            'G' => 50,  // Cause
            'H' => 20,  // Owners
            'I' => 50,  // remediation
            'J' => 10,  // progress
            'K' => 10,  // status
            'L' => 10,  // close_date
            'M' => 50,  // justification
        ];
    }

    public function map($action): array
    {
        return [
            [
                $action->reference,
                $action->type === 1 ? trans('cruds.action.types.major') : ($action->type === 2 ? trans('cruds.action.types.minor') : ($action->type === 3 ? trans('cruds.action.types.observation') : ($action->type === 4 ? trans('cruds.action.types.opportunity') : ''))),
                $action->due_date,
                $action->scope,
                $action->controls()->implode('clause', ', '),
                $action->name,
                $action->cause,
                $action->owners()->implode('name', ', '),
                $action->remediation,
                $action->progress,
                $action->status === 1 ? trans('cruds.action.fields.status_open') : ($action->status === 2 ? trans('cruds.action.fields.status_closed') : ($action->status === 3 ? trans('cruds.action.fields.status_rejected') : '')),
                $action->close_date,
                $action->justification,
            ],
        ];
    }

    public function query(): Builder
    {
        return Action::orderBy('creation_date');
    }
}
