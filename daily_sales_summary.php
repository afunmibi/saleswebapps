<?php
include 'db_connect.php';

// Get today's date
$date = date('Y-m-d');

// Fetch daily sales grouped by cashier
$query = "SELECT cashier, SUM(subtotal) AS total_sales, COUNT(*) AS num_sales 
          FROM sales WHERE sale_date = ? 
          GROUP BY cashier";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Sales Summary</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Daily Sales Summary - <?php echo $date; ?></h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Cashier</th>
                <th>Number of Sales</th>
                <th>Total Sales (₦)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo $row['cashier']; ?></td>
                    <td><?php echo $row['num_sales']; ?></td>
                    <td><?php echo number_format($row['total_sales'], 2); ?></td>
                    <td>
                        <a href="export_sales.php?cashier=<?php echo $row['cashier']; ?>&date=<?php echo $date; ?>" class="btn btn-primary btn-sm">Export</a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
</body>
</html>
