<?php

namespace App\Exports;

use App\Models\Measure;
use App\Models\Risk;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RiskExport extends StringValueBinder implements FromQuery, WithMapping, WithHeadings, WithStyles, WithColumnWidths, WithCustomStartCell, WithEvents
{
    /** Row 1 holds the title, row 2 is left blank, headings start on row 3. */
    public function startCell(): string
    {
        return 'A3';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setCellValue('A1', 'Deming - ' . trans('cruds.risk.list') . ' - ' . now()->format('d/m/Y'));
                $sheet->mergeCells('A1:N1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            },
        ];
    }

    public function headings(): array
    {
        return [
            'name',
            'description',
            'owner',
            'probability',
            'probability_comment',
            'impact',
            'impact_comment',
            'status',
            'status_comment',
            'review_frequency',
            'next_review_at',
            'exposure',
            'vulnerability',
            'residual_risk',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the headings row as bold text.
            3 => ['font' => ['bold' => true],
                'alignment' => [
                    'wrapText' => true,
                    'vertical' => 'top',
                ],
            ],
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,  // Name
            'B' => 50,  // Description
            'C' => 30,  // Owner
            'D' => 10,  // probability
            'E' => 30,  // probability_comment
            'F' => 10,  // impact
            'G' => 30,  // impact_comment
            'H' => 10,  // status
            'I' => 30,  // status_comment
            'J' => 10,  // review_frequency
            'K' => 30,  // next_review_at
            'L' => 10, // exposure
            'M' => 10,  // vulnerability
            'N' => 10,  // residual_risk
        ];
    }

    public function map($risk): array
    {
        return [
            [
                $risk->name,
                $risk->description,
                $risk->owner?->name,
                $risk->probability,
                $risk->probability_comment,
                $risk->impact,
                $risk->impact_comment,
                $risk->status,
                $risk->status_comment,
                $risk->review_frequency,
                $risk->next_review_at?->format('Y-m-d'),
                $risk->exposure,
                $risk->vulnerability,
                $risk->residual_risk,
            ],
        ];
    }

    public function query(): Builder
    {
        return Risk::query()->with('owner')->orderBy('name');
    }
}
