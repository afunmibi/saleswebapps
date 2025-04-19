<?php // index.php or sales.php ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supermarket Sales System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <h2>Supermarket Sales System</h2>
    
    <div class="mb-3">
    <label for="cashierName" class="form-label">Cashier Name</label>
    <input type="text" class="form-control" id="cashierName" placeholder="Enter cashier name" required>
</div>


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
    <h4 class="mt-3">Grand Total: ₦<span id="grandTotal">0.00</span></h4>
    <button class="btn btn-success" id="submitSales">Submit Sales</button>
</div>

<!-- Thank You Modal -->
<div class="modal fade" id="thankYouModal" tabindex="-1" aria-labelledby="thankYouModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="thankYouModalLabel">Success!</h5>
      </div>
      <div class="modal-body">
        Sales recorded successfully. Redirecting to receipt...
      </div>
    </div>
  </div>
  
</div>

<div class="mx-auto my-3 text-center" >
<a href="daily_sales_summary.php" class="btn btn-info mt-3">View Today's Sales Summary</a>
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
    let total = 0;
    $(".subtotal").each(function () {
        total += parseFloat($(this).val()) || 0;
    });
    $("#grandTotal").text(total.toFixed(2));
}

$(document).on("click", "#addRow", function () {
    let row = `<tr>
        <td><input type="text" class="form-control product"></td>
        <td><input type="number" class="form-control price" oninput="calculateSubtotal(this)"></td>
        <td><input type="number" class="form-control qty" oninput="calculateSubtotal(this)"></td>
        <td><input type="text" class="form-control subtotal" readonly></td>
        <td><button class="btn btn-danger removeRow">Remove</button></td>
    </tr>`;
    $("#salesTable tbody").append(row);
});

$(document).on("click", ".removeRow", function () {
    $(this).closest("tr").remove();
    calculateGrandTotal();
});
$("#submitSales").click(function () {
    let cashierName = $("#cashierName").val().trim();
    if (!cashierName) {
        alert("Please enter cashier name.");
        return;
    }

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

    if (salesData.length === 0) {
        alert("Please enter at least one sale.");
        return;
    }

    $.ajax({
        url: "submit_sales.php",
        type: "POST",
        data: {
            cashier_name: cashierName,
            sales: JSON.stringify(salesData)
        },
        success: function (response) {
            try {
                let res = JSON.parse(response);
                if (res.transaction_id) {
                    alert(res.message);
                    window.open("receipt.php?transaction_id=" + res.transaction_id, "_blank");
                    location.reload(); // Reset the form
                } else {
                    alert("Error: " + res.error);
                }
            } catch (e) {
                alert("Unexpected response: " + response);
            }
        }
    });
});



</script>


</body>
</html>
