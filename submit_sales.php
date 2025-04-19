<?php
include 'db_connect.php';

$cashier_name = $_POST['cashier_name'] ?? 'Unknown';
$sales = json_decode($_POST['sales'], true);

$transaction_id = 'TXN_' . uniqid();

foreach ($sales as $sale) {
    $product = $sale['product'];
    $price = $sale['price'];
    $qty = $sale['qty'];
    $subtotal = $sale['subtotal'];

    $query = "INSERT INTO sales (transaction_id, cashier_name, product, price, quantity, subtotal, sale_date) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssdds", $transaction_id, $cashier_name, $product, $price, $qty, $subtotal);
    $stmt->execute();
}

echo json_encode([
    "message" => "Sales data saved!",
    "transaction_id" => $transaction_id
]);
?>
