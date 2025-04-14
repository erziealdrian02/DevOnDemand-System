<?php

namespace App\Exports;

use App\Models\Client;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class ClientsExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnFormatting
{
    private $rowNumber = 0;

    public function collection()
    {
        return Client::all();
    }

    public function headings(): array
    {
        return [
            'No',
            'Name',
            'Email',
            'Company Name',
            'NPWP',
            'Bidang',
            'Status',
        ];
    }

    public function map($client): array
    {
        $this->rowNumber++;
        
        $metadata = $client->metadata ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true);
        }

        // Format NPWP properly
        $npwp = $metadata['npwp'] ?? '-';
        if ($npwp !== '-' && is_numeric(str_replace(['.', '-'], '', $npwp))) {
            // Format as 00.000.000.0-000.000 if it's a valid NPWP number
            $npwp = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{1})(\d{3})(\d{3})/', '$1.$2.$3.$4-$5.$6', $npwp);
        }

        return [
            $this->rowNumber,
            $client->name,
            $client->email,
            $client->company_name,
            $npwp,
            $metadata['industri'] ?? '-',
            $client->is_active ? 'Active' : 'Non-Active',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Header style - light blue background with bold black text
        $sheet->getStyle('A1:G1')->applyFromArray([
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

        // Auto-size columns for better readability
        $sheet->getColumnDimension('A')->setWidth(8);  // No
        $sheet->getColumnDimension('B')->setAutoSize(true); // Name
        $sheet->getColumnDimension('C')->setAutoSize(true); // Email
        $sheet->getColumnDimension('D')->setAutoSize(true); // Company Name
        $sheet->getColumnDimension('E')->setWidth(20); // NPWP
        $sheet->getColumnDimension('F')->setAutoSize(true); // Bidang
        $sheet->getColumnDimension('G')->setWidth(12); // Status

        // Wrap text for long content
        $sheet->getStyle('B2:G' . ($sheet->getHighestRow()))
            ->getAlignment()
            ->setWrapText(true);

        // Center align the No and Status columns
        $sheet->getStyle('A2:A' . ($sheet->getHighestRow()))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            
        $sheet->getStyle('G2:G' . ($sheet->getHighestRow()))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT, // Force NPWP column to be treated as text
        ];
    }
}