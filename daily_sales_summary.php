<?php
include 'db_connect.php';

// Get date and cashier parameters or default to today and empty cashier
$date = $_GET['date'] ?? date('Y-m-d');
$cashier = $_GET['cashier'] ?? ''; // Define $cashier from GET parameter
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid date format']));
}

try {
    // Query sales for the given date using prepared statement
    $query = "SELECT * FROM sales WHERE DATE(sale_date) = ?" . ($cashier ? " AND cashier_name = ?" : "") . " ORDER BY transaction_id, id";
    $stmt = $conn->prepare($query);
    if ($cashier) {
        $stmt->bind_param("ss", $date, $cashier);
    } else {
        $stmt->bind_param("s", $date);
    }

    // Execute the query and get the result
    $stmt->execute();
    $result = $stmt->get_result(); // Define $result

    // Organize sales by transaction_id
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
    // Do not close $conn yet, as it is needed for the cashier dropdown query

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Sales Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .card-header { font-size: 1.1rem; }
        .table th, .table td { vertical-align: middle; }
        .table .text-right { text-align: right; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h3 class="text-center">Daily Sales Summary (<?= htmlspecialchars(date('F j, Y', strtotime($date))) ?>)</h3>
    <div class="mb-3">
        <form method="GET" class="row g-3">
            <div class="col-auto">
                <label for="date" class="visually-hidden">Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($date) ?>" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
            <div class="col-auto ms-auto">
                <a href="export_daily_sales_pdf.php?date=<?= htmlspecialchars($date) ?>" class="btn btn-danger">Export PDF</a>
                <a href="export_daily_sales_excel.php?date=<?= htmlspecialchars($date) ?>" class="btn btn-success">Export Excel</a>
            </div>
        </form>
    </div>
    <div class="mb-3">
        <form method="GET" class="row g-3">
            <div class="col-auto">
                <label for="date" class="visually-hidden">Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($date) ?>" required>
            </div>
            <div class="col-auto">
                <label for="cashier" class="visually-hidden">Cashier</label>
                <select class="form-control" id="cashier" name="cashier">
                    <option value="">All Cashiers</option>
                    <?php
                    $cashiers = $conn->query("SELECT DISTINCT cashier_name FROM sales ORDER BY cashier_name");
                    while ($row = $cashiers->fetch_assoc()) {
                        $selected = ($cashier === $row['cashier_name']) ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($row['cashier_name']) . "' $selected>" . htmlspecialchars($row['cashier_name']) . "</option>";
                    }
                    $cashiers->close();
                    ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Filter</button>
            </div>
            <div class="col-auto ms-auto">
                <a href="export_daily_sales_pdf.php?date=<?= htmlspecialchars($date) ?><?= $cashier ? '&cashier=' . urlencode($cashier) : '' ?>" class="btn btn-danger">Export PDF</a>
                <a href="export_daily_sales_excel.php?date=<?= htmlspecialchars($date) ?><?= $cashier ? '&cashier=' . urlencode($cashier) : '' ?>" class="btn btn-success">Export Excel</a>
            </div>
        </form>
    </div>

    <?php if (!empty($salesGrouped)): ?>
        <?php foreach ($salesGrouped as $tid => $data): ?>
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <strong>Transaction ID:</strong> <?= htmlspecialchars($tid) ?> |
                    <strong>Cashier:</strong> <?= htmlspecialchars($data['cashier']) ?> |
                    <strong>Date:</strong> <?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($data['date']))) ?>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-right">Price (₦)</th>
                                <th class="text-right">Qty</th>
                                <th class="text-right">Subtotal (₦)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['items'] as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product']) ?></td>
                                    <td class="text-right"><?= number_format($item['price'], 2) ?></td>
                                    <td class="text-right"><?= htmlspecialchars($item['quantity']) ?></td>
                                    <td class="text-right"><?= number_format($item['subtotal'], 2) ?></td>
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
        <div class="alert alert-success text-end">
            <strong>Grand Total: ₦<?= number_format($grandTotal, 2) ?></strong>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">No sales records found for this date.</div>
    <?php endif; ?>
    <div class="text-center">
        <a href="index.php" class="btn btn-secondary">⬅️ Back</a>
    </div>
</div>
<?php
// Close the database connection after all queries are done
$conn->close();
?>
</body>
</html>