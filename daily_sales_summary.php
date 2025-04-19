<?php
include 'db_connect.php';

$date = date("Y-m-d");
$query = "SELECT * FROM sales WHERE DATE(sale_date) = '$date' ORDER BY transaction_id, id";
$result = $conn->query($query);

// Organize sales by transaction_id
$salesGrouped = [];
$grandTotal = 0;

while ($row = $result->fetch_assoc()) {
    $tid = $row['transaction_id'];

    // Initialize the transaction group if not already set
    if (!isset($salesGrouped[$tid])) {
        $salesGrouped[$tid] = [
            'cashier' => $row['cashier_name'] ?? 'Unknown',
            'date' => $row['sale_date'] ?? '',
            'items' => [],
            'total' => 0
        ];
    }

    $salesGrouped[$tid]['items'][] = $row;
    $salesGrouped[$tid]['total'] += $row['subtotal'];
    $grandTotal += $row['subtotal'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Daily Sales Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h3 class="text-center">Daily Sales Summary (<?= date("F j, Y") ?>)</h3>
    <div class="text-end mb-3">
        <a href="export_daily_sales_pdf.php?date=<?= $date ?>" class="btn btn-danger">Export PDF</a>
        <a href="export_daily_sales_excel.php?date=<?= $date ?>" class="btn btn-success">Export Excel</a>
    </div>

    <?php if (count($salesGrouped) > 0): ?>
        <?php foreach ($salesGrouped as $tid => $data): ?>
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <strong>Transaction ID:</strong> <?= $tid ?> |
                    <strong>Cashier:</strong> <?= htmlspecialchars($data['cashier']) ?> |
                    <strong>Date:</strong> <?= date('Y-m-d H:i:s', strtotime($data['date'])) ?>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price (₦)</th>
                                <th>Qty</th>
                                <th>Subtotal (₦)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['items'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product']) ?></td>
                                    <td><?= number_format($item['price'], 2) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td><?= number_format($item['subtotal'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="text-end p-2">
                        <strong>Total: ₦<?= number_format($data['total'], 2) ?></strong>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="alert alert-success text-end"><strong>Grand Total: ₦<?= number_format($grandTotal, 2) ?></strong></div>
    <?php else: ?>
        <div class="alert alert-info text-center">No sales records found for today.</div>
    <?php endif; ?>
</div>
</body>
</html>
