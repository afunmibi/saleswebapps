<?php
include 'db_connect.php';
require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';

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

    // Create PDF
    $pdf = new TCPDF();
    $pdf->SetCreator('Supermarket Sales System');
    $pdf->SetAuthor('Supermarket');
    $pdf->SetTitle('Product Sales Report');
    $pdf->AddPage();

    // Store header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Supermarket Product Sales Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Date: ' . date('F j, Y', strtotime($date)), 0, 1, 'C');
    $pdf->Ln(5);

    if (empty($sales)) {
        $pdf->SetFont('helvetica', 'I', 12);
        $pdf->Cell(0, 10, 'No sales records found for this date.', 0, 1, 'C');
    } else {
        // Table header
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(80, 8, 'Product', 1);
        $pdf->Cell(40, 8, 'Total Quantity', 1, 0, 'R');
        $pdf->Cell(40, 8, 'Total Sales (₦)', 1, 0, 'R');
        $pdf->Ln();

        // Table data
        $pdf->SetFont('helvetica', '', 10);
        foreach ($sales as $row) {
            $pdf->Cell(80, 8, htmlspecialchars($row['product']), 1);
            $pdf->Cell(40, 8, htmlspecialchars($row['total_qty']), 1, 0, 'R');
            $pdf->Cell(40, 8, number_format($row['total_amount'], 2), 1, 0, 'R');
            $pdf->Ln();
        }

        // Grand total
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(120, 10, 'Grand Total:', 1);
        $pdf->Cell(40, 10, '₦' . number_format($grandTotal, 2), 1, 0, 'R');
    }

    // Output PDF
    $pdf->Output('product_sales_report_' . $date . '.pdf', 'D');

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Failed to generate PDF: ' . $e->getMessage()]));
}
?>