<?php
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $salesData = json_decode($_POST['sales'], true);
    $date = date('Y-m-d');
    $transaction_id = uniqid("TXN_");

    if (!empty($salesData)) {
        foreach ($salesData as $sale) {
            $product = $sale['product'];
            $price = $sale['price'];
            $qty = $sale['qty'];
            $subtotal = $sale['subtotal'];

            $stmt = $conn->prepare("INSERT INTO sales (transaction_id, product, price, quantity, subtotal, sale_date) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddds", $transaction_id, $product, $price, $qty, $subtotal, $date);
            $stmt->execute();
        }

        echo json_encode([
            "message" => "Sales data saved!",
            "transaction_id" => $transaction_id
        ]);
        exit;
    } else {
        echo json_encode(["error" => "No sales data received."]);
        exit;
    }
} else {
    echo json_encode(["error" => "Invalid request."]);
    exit;
}
?>
