<?php
include 'db_connect.php';

// Get sales data from POST request
$sales = json_decode($_POST['sales'], true);

// Loop through each sale and insert into database
$transaction_id = 'TXN_' . uniqid();

foreach ($sales as $sale) {
    $product = $sale['product'];
    $price = $sale['price'];
    $qty = $sale['qty'];
    $subtotal = $sale['subtotal'];

    $query = "INSERT INTO sales (transaction_id, product, price, quantity, subtotal, sale_date) 
              VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssdds", $transaction_id, $product, $price, $qty, $subtotal);
    $stmt->execute();
}

// Send JSON response back to client
$response = array(
    "message" => "Sales data saved!",
    "transaction_id" => $transaction_id
);

echo json_encode($response);
?>
