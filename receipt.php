<?php
include 'db_connect.php';

// Validate transaction ID
$transaction_id = $_GET['transaction_id'] ?? null;
if (!$transaction_id || !preg_match('/^TXN_[a-f0-9]{13}$/', $transaction_id)) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid transaction ID']));
}

try {
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM sales WHERE transaction_id = ?");
    $stmt->bind_param("s", $transaction_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all rows
    $sales = [];
    $cashier_name = 'N/A';
    $sale_date = 'N/A';
    $grand_total = 0;

    while ($row = $result->fetch_assoc()) {
        $sales[] = $row;
        if ($cashier_name === 'N/A') {
            $cashier_name = $row['cashier_name'] ?? 'N/A';
            $sale_date = $row['sale_date'] ?? 'N/A';
        }
        $grand_total += floatval($row['subtotal'] ?? 0);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?= htmlspecialchars($transaction_id) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        @media print {
            .no-print { display: none; }
        }
        .receipt-box {
            max-width: 600px;
            margin: 30px auto;
            padding: 25px;
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .receipt-title {
            text-align: center;
            margin-bottom: 20px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .table .text-right {
            text-align: right;
        }
    </style>
</head>
<body class="bg-light">
<div class="receipt-box">
    <?php if (!empty($sales)): ?>
        <div class="receipt-title">
            <h4 class="fw-bold">🛒 Supermarket Receipt</h4>
        </div>

        <div class="mb-3">
            <p><strong>Transaction ID:</strong> <?= htmlspecialchars($transaction_id) ?></p>
            <p><strong>Date:</strong> <?= htmlspecialchars($sale_date) ?></p>
            <p><strong>Cashier:</strong> <?= htmlspecialchars($cashier_name) ?></p>
        </div>

        <table class="table table-bordered">
            <thead class="table-secondary">
                <tr>
                    <th>Product</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Price (₦)</th>
                    <th class="text-right">Total (₦)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['product']) ?></td>
                        <td class="text-right"><?= htmlspecialchars($row['quantity']) ?></td>
                        <td class="text-right"><?= number_format($row['price'], 2) ?></td>
                        <td class="text-right"><?= number_format($row['subtotal'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="text-end">
            <h5 class="fw-bold">Grand Total: ₦<?= number_format($grand_total, 2) ?></h5>
        </div>

        <div class="text-center mt-4 no-print">
            <button class="btn btn-primary" onclick="window.print()">🖨️ Print Receipt</button>
            <a href="index.php" class="btn btn-secondary">⬅️ Back</a>
        </div>
    <?php else: ?>
        <div class="alert alert-warning text-center">
            No records found for this transaction.
        </div>
    <?php endif; ?>
</div>
</body>
</html>