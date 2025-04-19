<?php
include 'db_connect.php';

$data = json_decode($_POST['sales'], true);
$cashier_name = $_POST['cashier_name'] ?? 'Unknown';

$transaction_id = "TXN_" . uniqid();
$date = date("Y-m-d H:i:s");

foreach ($data as $sale) {
    $product = $conn->real_escape_string($sale['product']);
    $price = floatval($sale['price']);
    $qty = intval($sale['qty']);
    $subtotal = floatval($sale['subtotal']);

    $query = "INSERT INTO sales (transaction_id, product, price, quantity, subtotal, sale_date, cashier_name)
              VALUES ('$transaction_id', '$product', $price, $qty, $subtotal, '$date', '$cashier_name')";
    $conn->query($query);
}

echo json_encode([
    "message" => "Sales data saved!",
    "transaction_id" => $transaction_id
]);
?>
<script>
    let cashierName = $("#cashierName").val().trim();
    data: {
    cashier_name: cashierName,
    sales: JSON.stringify(salesData)
}


</script>
