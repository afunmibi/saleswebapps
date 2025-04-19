<?php
require_once('tcpdf/tcpdf.php');
include 'db_connect.php';

// Fetch data for the selected cashier and date
$cashier = $_GET['cashier'];
$date = $_GET['date'];

$query = "SELECT product, price, quantity, subtotal, sale_date FROM sales 
          WHERE cashier = ? AND sale_date = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $cashier, $date);
$stmt->execute();
$result = $stmt->get_result();

// Create a new PDF document
$pdf = new TCPDF();
$pdf->AddPage();

// Set document title
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Daily Sales Report - ' . $cashier . ' (' . $date . ')', 0, 1, 'C');

// Add table header
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(50, 10, 'Product', 1);
$pdf->Cell(30, 10, 'Price (₦)', 1);
$pdf->Cell(30, 10, 'Quantity', 1);
$pdf->Cell(40, 10, 'Subtotal (₦)', 1);
$pdf->Ln();

// Add table data
$totalSales = 0;
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(50, 10, $row['product'], 1);
    $pdf->Cell(30, 10, number_format($row['price'], 2), 1);
    $pdf->Cell(30, 10, $row['quantity'], 1);
    $pdf->Cell(40, 10, number_format($row['subtotal'], 2), 1);
    $pdf->Ln();
    $totalSales += $row['subtotal'];
}

// Add total sales
$pdf->Cell(110, 10, 'Total Sales:', 1);
$pdf->Cell(40, 10, '₦' . number_format($totalSales, 2), 1, 0, 'R');
$pdf->Output('D', 'daily_sales_report.pdf');
?>
