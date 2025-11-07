<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Create new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Define headers
$headers = [
    'Name *',
    'Email *',
    'Mobile *',
    'Address',
    'Department',
    'Gender',
    'Year of Graduation'
];

// Set headers
foreach ($headers as $idx => $header) {
    $col = chr(65 + $idx); // Convert number to letter (0=A, 1=B, etc.)
    $sheet->setCellValue($col . '1', $header);
}

// Add sample data
$sampleData = [
    'John Doe',
    'john.doe@example.com',
    '1234567890',
    '123 Main Street, City',
    'Computer Science',
    'Male',
    '2020'
];

// Set sample data
foreach ($sampleData as $idx => $value) {
    $col = chr(65 + $idx);
    $sheet->setCellValue($col . '2', $value);
}

// Style headers
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => '000000'],
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'E0E0E0'],
    ],
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
    ],
];

// Apply header styles
$sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

// Set column widths
$sheet->getColumnDimension('A')->setWidth(20); // Name
$sheet->getColumnDimension('B')->setWidth(30); // Email
$sheet->getColumnDimension('C')->setWidth(15); // Mobile
$sheet->getColumnDimension('D')->setWidth(35); // Address
$sheet->getColumnDimension('E')->setWidth(20); // Department
$sheet->getColumnDimension('F')->setWidth(15); // Gender
$sheet->getColumnDimension('G')->setWidth(20); // Year of Graduation

// Add data validation for Gender
$validation = $sheet->getCell('F2')->getDataValidation();
$validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
$validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
$validation->setAllowBlank(true);
$validation->setShowInputMessage(true);
$validation->setShowErrorMessage(true);
$validation->setShowDropDown(true);
$validation->setFormula1('"Male,Female,Other"');

// Set the validation for the entire column
$sheet->setDataValidation('F2:F1000', $validation);

// Headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="visitor_import_template.xlsx"');
header('Cache-Control: max-age=0');

// Save file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');