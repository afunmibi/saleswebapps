<?php
include 'db_connect.php'; // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $salesData = json_decode($_POST['sales'], true);
    $date = date('Y-m-d');

    if (!empty($salesData)) {
        foreach ($salesData as $sale) {
            $product = $sale['product'];
            $price = $sale['price'];
            $qty = $sale['qty'];
            $subtotal = $sale['subtotal'];

            // Insert sales record into database
            $stmt = $conn->prepare("INSERT INTO sales (product, price, quantity, subtotal, sale_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sddds", $product, $price, $qty, $subtotal, $date);
            $stmt->execute();
        }
        echo "Sales data saved successfully!";
    } else {
        echo "No sales data received.";
    }
} else {
    echo "Invalid request.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h2>Supermarket Sales System</h2>
        <table class="table table-bordered" id="salesTable">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><input type="text" class="form-control product"></td>
                    <td><input type="number" class="form-control price" oninput="calculateSubtotal(this)"></td>
                    <td><input type="number" class="form-control qty" oninput="calculateSubtotal(this)"></td>
                    <td><input type="text" class="form-control subtotal" readonly></td>
                    <td><button class="btn btn-danger removeRow">Remove</button></td>
                </tr>
            </tbody>
        </table>
        <button class="btn btn-primary" id="addRow">Add Row</button>
        <h4 class="mt-3">Grand Total: <span id="grandTotal">0.00</span></h4>
        <button class="btn btn-success" id="submitSales">Submit Sales</button>
    </div>

    <script>
        function calculateSubtotal(element) {
            let row = $(element).closest("tr");
            let price = parseFloat(row.find(".price").val()) || 0;
            let qty = parseFloat(row.find(".qty").val()) || 0;
            let subtotal = price * qty;
            row.find(".subtotal").val(subtotal.toFixed(2));
            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            let grandTotal = 0;
            $(".subtotal").each(function () {
                grandTotal += parseFloat($(this).val()) || 0;
            });
            $("#grandTotal").text(grandTotal.toFixed(2));
        }

        $(document).on("click", "#addRow", function () {
            let newRow = `<tr>
                <td><input type="text" class="form-control product"></td>
                <td><input type="number" class="form-control price" oninput="calculateSubtotal(this)"></td>
                <td><input type="number" class="form-control qty" oninput="calculateSubtotal(this)"></td>
                <td><input type="text" class="form-control subtotal" readonly></td>
                <td><button class="btn btn-danger removeRow">Remove</button></td>
            </tr>`;
            $("#salesTable tbody").append(newRow);
        });

        $(document).on("click", ".removeRow", function () {
            $(this).closest("tr").remove();
            calculateGrandTotal();
        });

        $("#submitSales").click(function () {
            let salesData = [];
            $("#salesTable tbody tr").each(function () {
                let product = $(this).find(".product").val();
                let price = $(this).find(".price").val();
                let qty = $(this).find(".qty").val();
                let subtotal = $(this).find(".subtotal").val();
                if (product && price && qty) {
                    salesData.push({ product, price, qty, subtotal });
                }
            });
            $.ajax({
                url: "submit_sales.php",
                type: "POST",
                data: { sales: JSON.stringify(salesData) },
                success: function (response) {
                    alert("Sales saved successfully!");
                }
            });
        });
    </script>
</body>
</html>
