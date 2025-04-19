<?php
include 'db_connect.php';

$transaction_id = $_GET['transaction_id'] ?? null;
if (!$transaction_id) {
    die("Invalid transaction ID");
}

$query = "SELECT * FROM sales WHERE transaction_id = '$transaction_id'";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?= htmlspecialchars($transaction_id) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    </style>
</head>
<body class="bg-light">

<div class="receipt-box">
<?php if ($result->num_rows > 0): ?>
    <?php $firstRow = $result->fetch_assoc(); ?>
    <div class="receipt-title">
        <h4 class="fw-bold">🛒 Supermarket Receipt</h4>
    </div>

    <div class="mb-3">
        <p><strong>Transaction ID:</strong> <?= htmlspecialchars($transaction_id) ?></p>
        <p><strong>Date:</strong> <?= htmlspecialchars($firstRow['sale_date']) ?></p>
    </div>

    <table class="table table-bordered">
        <thead class="table-secondary">
            <tr>
                <th>Product</th>
                <th>Qty</th>
                <th>Price (₦)</th>
                <th>Total (₦)</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $grandTotal = 0;
            // Display first row
            echo "<tr>
                <td>{$firstRow['product']}</td>
                <td>{$firstRow['quantity']}</td>
                <td>{$firstRow['price']}</td>
                <td>{$firstRow['subtotal']}</td>
            </tr>";
            $grandTotal += $firstRow['subtotal'];

            // Display remaining rows
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                    <td>{$row['product']}</td>
                    <td>{$row['quantity']}</td>
                    <td>{$row['price']}</td>
                    <td>{$row['subtotal']}</td>
                </tr>";
                $grandTotal += $row['subtotal'];
            }
            ?>
        </tbody>
    </table>

    <div class="text-end">
        <h5 class="fw-bold">Grand Total: ₦<?= number_format($grandTotal, 2) ?></h5>
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
