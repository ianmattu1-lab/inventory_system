<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../../login.php'); exit; }
include __DIR__ . '/../core/db.php';
require __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Optional category filter
$sql = "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id";
$types = '';
$params = [];
if (!empty($_GET['category_id'])) {
    $sql .= " WHERE p.category_id = ?";
    $types = 'i';
    $params[] = (int)$_GET['category_id'];
}
$sql .= " ORDER BY c.name, p.name";
$result = run_query($conn, $sql, $types, $params)->get_result();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header row (bold + background color)
$headers = ['SKU', 'Product Name', 'Category', 'Unit Price', 'Quantity', 'Min Threshold', 'Status', 'Total Value'];
$sheet->fromArray($headers, null, 'A1');
$sheet->getStyle('A1:H1')->getFont()->setBold(true);
$sheet->getStyle('A1:H1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFD966');

// Data rows with formulas
$row = 2;
while ($p = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $row, $p['sku']);
    $sheet->setCellValue('B' . $row, $p['name']);
    $sheet->setCellValue('C' . $row, $p['category_name']);
    $sheet->setCellValue('D' . $row, $p['unit_price']);
    $sheet->setCellValue('E' . $row, $p['quantity']);
    $sheet->setCellValue('F' . $row, $p['min_stock_threshold']);
    $sheet->setCellValue('G' . $row, '=IF(E' . $row . '<F' . $row . ',"LOW STOCK","OK")');
    $sheet->setCellValue('H' . $row, '=D' . $row . '*E' . $row);
    $row++;
}

// Totals row
$last = $row - 1;
if ($last >= 2) {
    $sheet->setCellValue('A' . $row, 'TOTALS');
    $sheet->setCellValue('E' . $row, '=SUM(E2:E' . $last . ')');
    $sheet->setCellValue('H' . $row, '=SUM(H2:H' . $last . ')');
    $sheet->getStyle('A' . $row . ':H' . $row)->getFont()->setBold(true);
}

// Currency format
$sheet->getStyle('D2:D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
$sheet->getStyle('H2:H' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="inventory_report.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;