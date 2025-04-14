<?php

namespace App\Exports;

use App\Models\EmployeeSecond;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class EmployeeSecondsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting
{
    private $rowNumber = 0;

    public function collection()
    {
        return EmployeeSecond::all();
    }

    public function headings(): array
    {
        return [
            'No',
            'Name',
            'Email',
            'Phone',
            'Skillset',
            'Availability',
        ];
    }

    public function map($employee): array
    {
        $this->rowNumber++;
        
        // Format skillset with line breaks after every 3 skills
        $skillset = $employee->skillset ?? [];
        if (is_string($skillset)) {
            $skillset = json_decode($skillset, true);
        }
        
        $formattedSkills = '';
        if (!empty($skillset)) {
            $chunks = array_chunk($skillset, 3); // Split into groups of 3 skills
            foreach ($chunks as $chunk) {
                $formattedSkills .= implode(', ', $chunk) . "\n";
            }
            $formattedSkills = rtrim($formattedSkills); // Remove last newline
        } else {
            $formattedSkills = '-';
        }

        return [
            $this->rowNumber,
            $employee->name,
            $employee->email,
            $employee->phone ?? '-',
            $formattedSkills,
            $employee->is_available ? 'Available' : 'Not Available',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header style - light blue background with bold black text
        $sheet->getStyle('A1:F1')->applyFromArray([
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
        ]);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(8);  // No
        $sheet->getColumnDimension('B')->setWidth(25); // Name
        $sheet->getColumnDimension('C')->setWidth(30); // Email
        $sheet->getColumnDimension('D')->setWidth(15); // Phone
        $sheet->getColumnDimension('E')->setWidth(30); // Skillset
        $sheet->getColumnDimension('F')->setWidth(15); // Availability

        // Apply middle alignment to ALL cells
        $sheet->getStyle('A2:F' . ($sheet->getHighestRow()))
            ->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Special alignment for name and email (left aligned vertically centered)
        $sheet->getStyle('B2:C' . ($sheet->getHighestRow()))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Wrap text for all cells
        $sheet->getStyle('A2:F' . ($sheet->getHighestRow()))
            ->getAlignment()
            ->setWrapText(true);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT, // Force Skillset column to be treated as text
        ];
    }
}