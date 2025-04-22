<?php
include 'db_connect.php';
require_once 'vendor/autoload.php'; // Load Composer autoloader for PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

// Validate date
$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid date format']));
}

try {
    // Query aggregated sales by product
    $stmt = $conn->prepare("SELECT product, SUM(quantity) as total_qty, SUM(subtotal) as total_amount 
                            FROM sales 
                            WHERE DATE(sale_date) = ? 
                            GROUP BY product 
                            ORDER BY product");
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $sales = [];
    $grandTotal = 0;
    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
        $grandTotal += floatval($row['total_amount']);
    }

    $stmt->close();
    $conn->close();

    // Create spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Product Sales Report');

    // Set header
    $row = 1;
    $sheet->setCellValue('A' . $row, 'Supermarket Product Sales Report');
    $sheet->mergeCells('A1:C1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;

    $sheet->setCellValue('A' . $row, 'Date: ' . date('F j, Y', strtotime($date)));
    $sheet->mergeCells('A2:C2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row += 2;

    if (empty($sales)) {
        $sheet->setCellValue('A' . $row, 'No sales records found for this date.');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setItalic(true);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    } else {
        // Table header
        $sheet->setCellValue('A' . $row, 'Product');
        $sheet->setCellValue('B' . $row, 'Total Quantity');
        $sheet->setCellValue('C' . $row, 'Total Sales (₦)');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('B' . $row . ':C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $row++;

        // Table data
        foreach ($sales as $item) {
            $sheet->setCellValue('A' . $row, htmlspecialchars($item['product']));
            $sheet->setCellValue('B' . $row, htmlspecialchars($item['total_qty']));
            $sheet->setCellValue('C' . $row, number_format($item['total_amount'], 2));
            $sheet->getStyle('B' . $row . ':C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $row++;
        }

        // Grand total
        $sheet->setCellValue('B' . $row, 'Grand Total:');
        $sheet->setCellValue('C' . $row, '₦' . number_format($grandTotal, 2));
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);
        $sheet->getStyle('C' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Auto-size columns
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    // Output Excel
    $filename = 'product_sales_report_' . $date . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Failed to generate Excel: ' . $e->getMessage()]));
}
?>