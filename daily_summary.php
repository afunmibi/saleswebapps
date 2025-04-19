<?php
include 'db_connect.php';
$date = date('Y-m-d');

$result = $conn->query("SELECT product, SUM(quantity) as total_qty, SUM(subtotal) as total_amount FROM sales WHERE sale_date = '$date' GROUP BY product");

echo '<div class="container mt-4">';
echo "<h3>Daily Sales Summary for " . date('F j, Y') . "</h3>";
echo '<table class="table table-striped">';
echo '<thead><tr><th>Product</th><th>Total Quantity</th><th>Total Sales (₦)</th></tr></thead><tbody>';

$grandTotal = 0;
while ($row = $result->fetch_assoc()) {
    echo "<tr>
        <td>{$row['product']}</td>
        <td>{$row['total_qty']}</td>
        <td>" . number_format($row['total_amount'], 2) . "</td>
    </tr>";
    $grandTotal += $row['total_amount'];
}

echo "</tbody></table>";
echo "<h5 class='text-end'>Grand Total: ₦" . number_format($grandTotal, 2) . "</h5>";
echo '</div>';
?>
