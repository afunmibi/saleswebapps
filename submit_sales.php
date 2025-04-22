<?php
// submit_sales.php
session_start();
include 'db_connect.php';

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

// Validate and decode JSON data
$sales = json_decode($_POST['sales'] ?? '', true);
$cashier_name = isset($_POST['cashier_name']) ? trim($conn->real_escape_string($_POST['cashier_name'])) : 'Unknown';

if (!is_array($sales) || empty($sales)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or empty sales data']);
    exit;
}

// Generate transaction ID and date
$transaction_id = "TXN_" . uniqid();
$date = date("Y-m-d H:i:s");

try {
    // Begin transaction
    $conn->begin_transaction();

    foreach ($sales as $sale) {
        // Validate and sanitize sale data
        $product = $conn->real_escape_string($sale['product'] ?? '');
        $price = floatval($sale['price'] ?? 0);
        $qty = intval($sale['qty'] ?? 0);
        $subtotal = floatval($sale['subtotal'] ?? 0);

        if (empty($product) || $price <= 0 || $qty <= 0 || $subtotal <= 0) {
            throw new Exception('Invalid sale data');
        }

        // Validate subtotal
        if (abs($subtotal - ($price * $qty)) > 0.01) {
            throw new Exception('Subtotal does not match price * quantity for product: ' . $product);
        }

        // Use prepared statement
        $stmt = $conn->prepare(
            "INSERT INTO sales (transaction_id, product, price, quantity, subtotal, sale_date, cashier_name) 
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "ssddsss",
            $transaction_id,
            $product,
            $price,
            $qty,
            $subtotal,
            $date,
            $cashier_name
        );
        $stmt->execute();
        $stmt->close();
    }

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        "message" => "Sales data saved!",
        "transaction_id" => $transaction_id
    ]);

} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save sales data: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>