<?php
include 'db_connect.php';

// Get date parameter or default to today
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

} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database error: ' . $e->getMessage()]));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Sales Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .table th, .table td { vertical-align: middle; }
        .table .text-right { text-align: right; }
    </style>
</head>
<body class="bg-light">
<div class="container mt-4">
    <h3 class="text-center">Product Sales Summary (<?= htmlspecialchars(date('F j, Y', strtotime($date))) ?>)</h3>
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
            <a href="export_product_sales_pdf.php?date=<?= htmlspecialchars($date) ?>" class="btn btn-danger">Export PDF</a>
            <a href="export_product_sales_excel.php?date=<?= htmlspecialchars($date) ?>" class="btn btn-success">Export Excel</a>
        </div>
    </form>
</div>

    <?php if (!empty($sales)): ?>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-right">Total Quantity</th>
                    <th class="text-right">Total Sales (₦)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sales as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['product']) ?></td>
                        <td class="text-right"><?= htmlspecialchars($row['total_qty']) ?></td>
                        <td class="text-right"><?= number_format($row['total_amount'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <h5 class="text-end">Grand Total: ₦<?= number_format($grandTotal, 2) ?></h5>
    <?php else: ?>
        <div class="alert alert-info text-center">No sales records found for this date.</div>
    <?php endif; ?>
    <div class="text-center">
        <a href="index.php" class="btn btn-secondary">⬅️ Back</a>
    </div>
</div>
</body>
</html>