<?php
include 'db_connect.php';
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

// Validate inputs
$date = $_GET['date'] ?? date('Y-m-d');
$cashier = $_GET['cashier'] ?? null;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid date format']));
}
if ($cashier !== null && empty(trim($cashier))) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid cashier name']));
}

try {
    // Query sales for the given date and optional cashier
    $query = "SELECT transaction_id, product, price, quantity, subtotal, sale_date, cashier_name 
              FROM sales 
              WHERE DATE(sale_date) = ?" . ($cashier ? " AND cashier_name = ?" : "") . 
              " ORDER BY transaction_id, id";
    $stmt = $conn->prepare($query);
    if ($cashier) {
        $stmt->bind_param("ss", $date, $cashier);
    } else {
        $stmt->bind_param("s", $date);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Group sales by transaction_id
    $salesGrouped = [];
    $grandTotal = 0;
    while ($row = $result->fetch_assoc()) {
        $tid = $row['transaction_id'];
        if (!isset($salesGrouped[$tid])) {
            $salesGrouped[$tid] = [
                'cashier' => $row['cashier_name'] ?? 'Unknown',
                'date' => $row['sale_date'] ?? '',
                'items' => [],
                'total' => 0
            ];
        }
        $salesGrouped[$tid]['items'][] = $row;
        $salesGrouped[$tid]['total'] += floatval($row['subtotal']);
        $grandTotal += floatval($row['subtotal']);
    }

    $stmt->close();
    $conn->close();

    // Create PDF
    $pdf = new TCPDF();
    $pdf->SetCreator('Supermarket Sales System');
    $pdf->SetAuthor('Supermarket');
    $pdf->SetTitle('Daily Sales Report');
    $pdf->AddPage();

    // Store header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Supermarket Daily Sales Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Date: ' . date('F j, Y', strtotime($date)), 0, 1, 'C');
    if ($cashier) {
        $pdf->Cell(0, 10, 'Cashier: ' . htmlspecialchars($cashier), 0, 1, 'C');
    }
    $pdf->Ln(5);

    if (empty($salesGrouped)) {
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->Cell(0, 10, 'No sales records found for this date' . ($cashier ? ' and cashier' : '') . '.', 0, 1, 'C');
    } else {
        // Table header
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(60, 8, 'Product', 1);
        $pdf->Cell(30, 8, 'Price (₦)', 1, 0, 'R');
        $pdf->Cell(30, 8, 'Quantity', 1, 0, 'R');
        $pdf->Cell(40, 8, 'Subtotal (₦)', 1, 0, 'R');
        $pdf->Ln();

        // Table data
        $pdf->SetFont('helvetica', '', 10);
        foreach ($salesGrouped as $tid => $data) {
            // Transaction header
            $pdf->SetFont('helvetica', 'B', 11);
            $pdf->Cell(0, 8, "Transaction ID: $tid | Cashier: " . htmlspecialchars($data['cashier']) . 
                            " | Date: " . date('Y-m-d H:i:s', strtotime($data['date'])), 0, 1);
            $pdf->SetFont('helvetica', '', 10);

            // Items
            foreach ($data['items'] as $item) {
                $pdf->Cell(60, 8, htmlspecialchars($item['product']), 1);
                $pdf->Cell(30, 8, number_format($item['price'], 2), 1, 0, 'R');
                $pdf->Cell(30, 8, htmlspecialchars($item['quantity']), 1, 0, 'R');
                $pdf->Cell(40, 8, number_format($item['subtotal'], 2), 1, 0, 'R');
                $pdf->Ln();
            }

            // Transaction total
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(120, 8, 'Transaction Total:', 1);
            $pdf->Cell(40, 8, '₦' . number_format($data['total'], 2), 1, 0, 'R');
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', '', 10);
        }

        // Grand total
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(120, 10, 'Grand Total:', 1);
        $pdf->Cell(40, 10, '₦' . number_format($grandTotal, 2), 1, 0, 'R');
    }

    // Output PDF
    $pdf->Output('daily_sales_report_' . $date . ($cashier ? '_' . str_replace(' ', '_', $cashier) : '') . '.pdf', 'D');

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Failed to generate PDF: ' . $e->getMessage()]));
}
?>