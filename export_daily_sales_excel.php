<?php
include 'db_connect.php';

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
    // Query sales
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

    // Group sales
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

    // Output CSV
    $filename = 'daily_sales_report_' . $date . ($cashier ? '_' . str_replace(' ', '_', $cashier) : '') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility

    // Header
    fputcsv($output, ['Supermarket Daily Sales Report']);
    fputcsv($output, ['Date', date('F j, Y', strtotime($date))]);
    if ($cashier) {
        fputcsv($output, ['Cashier', htmlspecialchars($cashier)]);
    }
    fputcsv($output, []);

    if (empty($salesGrouped)) {
        fputcsv($output, ['No sales records found for this date' . ($cashier ? ' and cashier' : '') . '.']);
    } else {
        // Table header
        fputcsv($output, ['Product', 'Price (₦)', 'Quantity', 'Subtotal (₦)']);

        foreach ($salesGrouped as $tid => $data) {
            // Transaction header
            
            fputcsv($output, ["Transaction ID: $tid | Cashier: " . htmlspecialchars($data['cashier']) . 
                             " | Date: " . date('Y-m-d H:i:s', strtotime($data['date']))]);
            
            // Items
            foreach ($data['items'] as $item) {
                fputcsv($output, [
                    htmlspecialchars($item['product']),
                    number_format($item['price'], 2),
                    htmlspecialchars($item['quantity']),
                    number_format($item['subtotal'], 2)
                ]);
            }

            // Transaction total
            fputcsv($output, ['', '', 'Transaction Total:', '₦' . number_format($data['total'], 2)]);
            fputcsv($output, []);
        }

        // Grand total
        fputcsv($output, ['', '', 'Grand Total:', '₦' . number_format($grandTotal, 2)]);
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Failed to generate CSV: ' . $e->getMessage()]));
}
?>