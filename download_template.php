<?php
session_start();
include 'connection.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$headers = ['Name', 'Email', 'Mobile', 'Address', 'Department', 'Gender', 'Year of Graduation'];
foreach ($headers as $idx => $header) {
    $sheet->setCellValueByColumnAndRow($idx + 1, 1, $header);
}

// Add sample row
$sampleData = ['John Doe', 'john.doe@example.com', '1234567890', '123 Main St', 'Computer Science', 'Male', '2020'];
foreach ($sampleData as $idx => $value) {
    $sheet->setCellValueByColumnAndRow($idx + 1, 2, $value);
}

// Set column widths
$sheet->getColumnDimension('A')->setWidth(20);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(15);
$sheet->getColumnDimension('D')->setWidth(30);
$sheet->getColumnDimension('E')->setWidth(20);
$sheet->getColumnDimension('F')->setWidth(10);
$sheet->getColumnDimension('G')->setWidth(15);

// Style the header row
$styleArray = [
    'font' => [
        'bold' => true,
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => [
            'rgb' => 'E0E0E0',
        ],
    ],
];
$sheet->getStyle('A1:G1')->applyFromArray($styleArray);

// Set the header for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="visitors_template.xlsx"');
header('Cache-Control: max-age=0');

// Save file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;