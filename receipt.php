<?php
include 'db_connect.php';

$sale_id = $_GET['sale_id'] ?? null;
if (!$sale_id) {
    die("Invalid sale ID");
}

$query = "SELECT * FROM sales WHERE id = '$sale_id'";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    $sale = $result->fetch_assoc();
    echo "<div style='max-width: 300px; border: 1px solid #000; padding: 10px; text-align: center;'>";
    echo "<h3>Supermarket Receipt</h3>";
    echo "<p>Date: " . $sale['sale_date'] . "</p>";
    echo "<table border='1' width='100%'><tr><th>Product</th><th>Qty</th><th>Price</th><th>Total</th></tr>";
    echo "<tr><td>{$sale['product']}</td><td>{$sale['quantity']}</td><td>{$sale['price']}</td><td>{$sale['subtotal']}</td></tr>";
    echo "</table>";
    echo "<h4>Grand Total: {$sale['subtotal']}</h4>";
    echo "<button onclick='window.print()'>Print Receipt</button>";
    echo "</div>";
} else {
    echo "Sale not found.";
}
?>