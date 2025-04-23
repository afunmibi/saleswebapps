<?php
include 'db_connect.php';

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

    // Output CSV
    $filename = 'product_sales_report_' . $date . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $output = fopen('php://output', 'w');

    // Write header
    fputcsv($output, ['Supermarket Product Sales Report']);
    fputcsv($output, ['Date: ' . date('F j, Y', strtotime($date))]);
    fputcsv($output, []); // Empty row

    if (empty($sales)) {
        fputcsv($output, ['No sales records found for this date.']);
    } else {
        // Table header
        fputcsv($output, ['Product', 'Total Quantity', 'Total Sales (₦)']);

        // Table data
        foreach ($sales as $item) {
            fputcsv($output, [
                htmlspecialchars($item['product']),
                htmlspecialchars($item['total_qty']),
                number_format($item['total_amount'], 2)
            ]);
        }

        // Grand total
        fputcsv($output, ['', 'Grand Total:', '₦' . number_format($grandTotal, 2)]);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Failed to generate CSV: ' . $e->getMessage()]));
}
?>