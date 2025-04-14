<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ProjectSecondExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting
{
    private $rowNumber = 0;

    public function collection()
    {
        return Project::with(['client', 'assignments.employee'])
            ->orderBy('project_id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Project ID',
            'Project Name',
            'Client Company',
            'Start Date',
            'Status',
            'Lokasi',
            'Cost',
            'Employee Count',
            'Assignment ID',
            'Employee Name',
            'Assignment Start',
            'Assignment End',
            'Notes'
        ];
    }

    public function map($project): array
    {
        $this->rowNumber++;
        $settings = $project->settings ?? [];
        if (is_string($settings)) {
            $settings = json_decode($settings, true);
        }

        // If project has no assignments, return just project data
        if ($project->assignments->isEmpty()) {
            return [
                $this->rowNumber,
                $project->project_id,
                $project->project_name,
                $project->client->company_name ?? '-',
                $project->start_date->format('d/m/Y H:i'),
                $project->is_approved ? 'Approved' : 'Pending',
                $settings['lokasi'] ?? '-',
                $settings['cost'] ?? '-',
                $settings['employee_count'] ?? '-',
                '-', // Assignment ID
                '-', // Employee Name
                '-', // Assignment Start
                '-', // Assignment End
                '-'  // Notes
            ];
        }

        $rows = [];
        $firstAssignment = true;

        foreach ($project->assignments as $assignment) {
            $rows[] = [
                $firstAssignment ? $this->rowNumber : '', // Show number only on first row
                $firstAssignment ? $project->project_id : '',
                $firstAssignment ? $project->project_name : '',
                $firstAssignment ? $project->client->company_name ?? '-' : '',
                $firstAssignment ? $project->start_date->format('d/m/Y H:i') : '',
                $firstAssignment ? ($project->is_approved ? 'Approved' : 'Pending') : '',
                $firstAssignment ? ($settings['lokasi'] ?? '-') : '',
                $firstAssignment ? ($settings['cost'] ?? '-') : '',
                $firstAssignment ? ($settings['employee_count'] ?? '-') : '',
                $assignment->ssignments_id,
                $assignment->employee->name ?? '-',
                $assignment->start_date->format('d/m/Y'),
                $assignment->end_date ? $assignment->end_date->format('d/m/Y') : 'Ongoing',
                $assignment->notes ?? '-'
            ];
            $firstAssignment = false;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        // Header style - light blue background with bold black text
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'ADD8E6'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);   // No
        $sheet->getColumnDimension('B')->setWidth(15);  // Project ID
        $sheet->getColumnDimension('C')->setWidth(30);  // Project Name
        $sheet->getColumnDimension('D')->setWidth(30);  // Client Company
        $sheet->getColumnDimension('E')->setWidth(20);  // Start Date
        $sheet->getColumnDimension('F')->setWidth(15);  // Status
        $sheet->getColumnDimension('G')->setWidth(20);  // Lokasi
        $sheet->getColumnDimension('H')->setWidth(15);  // Cost
        $sheet->getColumnDimension('I')->setWidth(15);  // Employee Count
        $sheet->getColumnDimension('J')->setWidth(20);  // Assignment ID
        $sheet->getColumnDimension('K')->setWidth(25);  // Employee Name
        $sheet->getColumnDimension('L')->setWidth(15);  // Assignment Start
        $sheet->getColumnDimension('M')->setWidth(15);  // Assignment End
        $sheet->getColumnDimension('N')->setWidth(40);  // Notes

        // Apply middle alignment to ALL cells
        $sheet->getStyle('A2:N' . ($sheet->getHighestRow()))
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Center align for IDs, dates, and status
        $centerAlignColumns = ['A', 'B', 'E', 'F', 'H', 'I', 'J', 'L', 'M'];
        foreach ($centerAlignColumns as $col) {
            $sheet->getStyle($col . '2:' . $col . $sheet->getHighestRow())
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }

        // Left align for text-heavy columns
        $leftAlignColumns = ['C', 'D', 'G', 'K', 'N'];
        foreach ($leftAlignColumns as $col) {
            $sheet->getStyle($col . '2:' . $col . $sheet->getHighestRow())
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        // Wrap text for notes column
        $sheet->getStyle('N2:N' . ($sheet->getHighestRow()))
            ->getAlignment()
            ->setWrapText(true);

        // Add borders to all cells
        $sheet->getStyle('A1:N' . ($sheet->getHighestRow()))
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_DATE_DDMMYYYY,  // Project Start Date
            'L' => NumberFormat::FORMAT_DATE_DDMMYYYY,  // Assignment Start
            'M' => NumberFormat::FORMAT_DATE_DDMMYYYY,  // Assignment End
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,  // Cost
        ];
    }
}