<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

require_once "../../config/database.php";


$purchase_id = (int)(
    $_GET["id"] ??
    $_POST["purchase_id"] ??
    0
);


if ($purchase_id <= 0) {

    header(
        "Location: purchases.php?error=Invalid purchase ID"
    );

    exit();
}


// =========================================================
// UPDATE PURCHASE
// =========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $supplier_id =
        (int)($_POST["supplier_id"] ?? 0);

    $invoice_number =
        trim($_POST["invoice_number"] ?? "");

    $purchase_date =
        $_POST["purchase_date"] ?? date("Y-m-d");

    $payment_status =
        $_POST["payment_status"] ?? "Paid";

    $remarks =
        trim($_POST["remarks"] ?? "");

    $product_ids =
        $_POST["product_id"] ?? [];

    $quantities =
        $_POST["quantity"] ?? [];

    $prices =
        $_POST["purchase_price"] ?? [];


    if (
        $supplier_id <= 0 ||
        $invoice_number === "" ||
        empty($product_ids)
    ) {

        header(
            "Location: edit_purchase.php?id=$purchase_id&error=Please complete all required fields"
        );

        exit();
    }


    // -----------------------------------------------------
    // CHECK INVOICE
    // -----------------------------------------------------

    $check = $conn->prepare("
        SELECT purchase_id
        FROM purchases
        WHERE invoice_number = ?
        AND purchase_id != ?
    ");

    $check->bind_param(
        "si",
        $invoice_number,
        $purchase_id
    );

    $check->execute();

    $result = $check->get_result();


    if ($result->num_rows > 0) {

        header(
            "Location: edit_purchase.php?id=$purchase_id&error=Invoice number already exists"
        );

        exit();
    }

    $check->close();


    // =====================================================
    // TRANSACTION
    // =====================================================

    $conn->begin_transaction();


    try {


        // -------------------------------------------------
        // GET OLD PURCHASE DETAILS
        // -------------------------------------------------

        $old_stmt = $conn->prepare("
            SELECT
                product_id,
                quantity
            FROM purchase_details
            WHERE purchase_id = ?
        ");

        $old_stmt->bind_param(
            "i",
            $purchase_id
        );

        $old_stmt->execute();

        $old_result =
            $old_stmt->get_result();


        $old_items = [];


        while (
            $old = $old_result->fetch_assoc()
        ) {

            $old_items[] = $old;

        }


        $old_stmt->close();


        // -------------------------------------------------
        // RESTORE STOCK
        // -------------------------------------------------

        $restore_stmt = $conn->prepare("
            UPDATE products
            SET stock_quantity =
                stock_quantity - ?
            WHERE product_id = ?
        ");


        foreach ($old_items as $old) {

            $old_quantity =
                (int)$old["quantity"];

            $old_product_id =
                (int)$old["product_id"];


            $restore_stmt->bind_param(
                "ii",
                $old_quantity,
                $old_product_id
            );


            if (!$restore_stmt->execute()) {

                throw new Exception(
                    "Failed to restore old stock."
                );
            }

        }


        $restore_stmt->close();


        // -------------------------------------------------
        // DELETE OLD DETAILS
        // -------------------------------------------------

        $delete_details = $conn->prepare("
            DELETE FROM purchase_details
            WHERE purchase_id = ?
        ");

        $delete_details->bind_param(
            "i",
            $purchase_id
        );

        if (!$delete_details->execute()) {

            throw new Exception(
                "Failed to update purchase details."
            );
        }

        $delete_details->close();


        // -------------------------------------------------
        // CALCULATE NEW TOTAL
        // -------------------------------------------------

        $total_amount = 0;

        $items = [];


        foreach ($product_ids as $index => $product_id) {

            $product_id =
                (int)$product_id;

            $quantity =
                (int)($quantities[$index] ?? 0);

            $purchase_price =
                (float)($prices[$index] ?? 0);


            if (
                $product_id <= 0 ||
                $quantity <= 0 ||
                $purchase_price < 0
            ) {

                throw new Exception(
                    "Invalid product information."
                );
            }


            $subtotal =
                $quantity * $purchase_price;


            $total_amount += $subtotal;


            $items[] = [

                "product_id" =>
                    $product_id,

                "quantity" =>
                    $quantity,

                "purchase_price" =>
                    $purchase_price,

                "subtotal" =>
                    $subtotal
            ];

        }


        // -------------------------------------------------
        // UPDATE PURCHASE
        // -------------------------------------------------

        $purchase_stmt = $conn->prepare("
            UPDATE purchases

            SET
                supplier_id = ?,
                invoice_number = ?,
                purchase_date = ?,
                total_amount = ?,
                payment_status = ?,
                remarks = ?

            WHERE purchase_id = ?
        ");


        $purchase_stmt->bind_param(
            "issdssi",
            $supplier_id,
            $invoice_number,
            $purchase_date,
            $total_amount,
            $payment_status,
            $remarks,
            $purchase_id
        );


        if (!$purchase_stmt->execute()) {

            throw new Exception(
                "Failed to update purchase."
            );
        }


        $purchase_stmt->close();


        // -------------------------------------------------
        // INSERT NEW DETAILS
        // -------------------------------------------------

        $detail_stmt = $conn->prepare("
            INSERT INTO purchase_details
            (
                purchase_id,
                product_id,
                quantity,
                purchase_price,
                subtotal
            )
            VALUES (?, ?, ?, ?, ?)
        ");


        // -------------------------------------------------
        // ADD NEW STOCK
        // -------------------------------------------------

        $stock_stmt = $conn->prepare("
            UPDATE products
            SET stock_quantity =
                stock_quantity + ?
            WHERE product_id = ?
        ");


        foreach ($items as $item) {

            $product_id =
                $item["product_id"];

            $quantity =
                $item["quantity"];

            $purchase_price =
                $item["purchase_price"];

            $subtotal =
                $item["subtotal"];


            $detail_stmt->bind_param(
                "iiidd",
                $purchase_id,
                $product_id,
                $quantity,
                $purchase_price,
                $subtotal
            );


            if (!$detail_stmt->execute()) {

                throw new Exception(
                    "Failed to insert new purchase details."
                );
            }


            $stock_stmt->bind_param(
                "ii",
                $quantity,
                $product_id
            );


            if (!$stock_stmt->execute()) {

                throw new Exception(
                    "Failed to update stock."
                );
            }

        }


        $detail_stmt->close();

        $stock_stmt->close();


        // -------------------------------------------------
        // COMMIT
        // -------------------------------------------------

        $conn->commit();


        header(
            "Location: purchases.php?success=Purchase updated successfully and stock recalculated"
        );

        exit();


    } catch (Exception $e) {

        $conn->rollback();


        header(
            "Location: edit_purchase.php?id="
            . $purchase_id
            . "&error="
            . urlencode($e->getMessage())
        );

        exit();

    }

}


// =========================================================
// FETCH PURCHASE
// =========================================================

$stmt = $conn->prepare("
    SELECT *
    FROM purchases
    WHERE purchase_id = ?
");

$stmt->bind_param(
    "i",
    $purchase_id
);

$stmt->execute();

$purchase_result =
    $stmt->get_result();

$purchase =
    $purchase_result->fetch_assoc();

$stmt->close();


if (!$purchase) {

    header(
        "Location: purchases.php?error=Purchase not found"
    );

    exit();
}


// =========================================================
// FETCH PURCHASE DETAILS
// =========================================================

$detail_stmt = $conn->prepare("
    SELECT
        pd.product_id,
        pd.quantity,
        pd.purchase_price,
        pd.subtotal,
        p.product_name,
        p.brand
    FROM purchase_details pd

    INNER JOIN products p
        ON pd.product_id = p.product_id

    WHERE pd.purchase_id = ?
");

$detail_stmt->bind_param(
    "i",
    $purchase_id
);

$detail_stmt->execute();

$details_result =
    $detail_stmt->get_result();

$purchase_items = [];


while (
    $item = $details_result->fetch_assoc()
) {

    $purchase_items[] = $item;

}


$detail_stmt->close();


// =========================================================
// FETCH SUPPLIERS
// =========================================================

$suppliers = $conn->query("
    SELECT supplier_id, supplier_name
    FROM suppliers
    WHERE status = 'Active'
    ORDER BY supplier_name ASC
");


// =========================================================
// FETCH PRODUCTS
// =========================================================

$products = $conn->query("
    SELECT
        product_id,
        product_name,
        brand,
        purchase_price
    FROM products
    WHERE status = 'Active'
    ORDER BY product_name ASC
");


$error = $_GET["error"] ?? "";

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0">

<title>SmartIMS | Edit Purchase</title>


<link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
    rel="stylesheet">


<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


<link
    rel="stylesheet"
    href="../../assets/css/style.css">

<link
    rel="stylesheet"
    href="../../assets/css/dashboard.css">

</head>


<body>


<?php require_once "../../includes/slidebar.php"; ?>


<div class="main-content">


<?php require_once "../../includes/navbar.php"; ?>


<main class="page-content">


<div
    class="d-flex justify-content-between align-items-center mb-4">


<div>

<h1 class="page-heading">

<i class="fa-solid fa-pen-to-square me-2"></i>

Edit Purchase

</h1>

<p class="text-muted mb-0">

Update purchase information and inventory.

</p>

</div>


<a
    href="purchases.php"
    class="btn btn-secondary">

<i class="fa-solid fa-arrow-left me-2"></i>

Back to Purchases

</a>


</div>


<?php if ($error !== ""): ?>

<div class="alert alert-danger">

<i class="fa-solid fa-circle-exclamation me-2"></i>

<?php echo htmlspecialchars($error); ?>

</div>

<?php endif; ?>


<div class="dashboard-panel">


<form method="POST">


<input
    type="hidden"
    name="purchase_id"
    value="<?php echo $purchase_id; ?>">


<!-- PURCHASE INFO -->

<h5 class="fw-bold">

<i class="fa-solid fa-file-invoice me-2"></i>

Purchase Information

</h5>

<hr>


<div class="row">


<div class="col-md-4 mb-3">

<label class="form-label">
Supplier *
</label>


<select
    name="supplier_id"
    class="form-select"
    required>


<?php while (
    $supplier = $suppliers->fetch_assoc()
): ?>

<option
    value="<?php echo $supplier["supplier_id"]; ?>"
    <?php echo
        $supplier["supplier_id"]
        == $purchase["supplier_id"]
        ? "selected"
        : "";
    ?>>

<?php echo htmlspecialchars(
    $supplier["supplier_name"]
); ?>

</option>

<?php endwhile; ?>

</select>

</div>


<div class="col-md-4 mb-3">

<label class="form-label">
Invoice Number *
</label>


<input
    type="text"
    name="invoice_number"
    class="form-control"
    value="<?php echo htmlspecialchars(
        $purchase["invoice_number"]
    ); ?>"
    required>

</div>


<div class="col-md-4 mb-3">

<label class="form-label">
Purchase Date *
</label>


<input
    type="date"
    name="purchase_date"
    class="form-control"
    value="<?php echo
        $purchase["purchase_date"];
    ?>"
    required>

</div>


<div class="col-md-4 mb-3">

<label class="form-label">
Payment Status
</label>


<select
    name="payment_status"
    class="form-select">


<option
    value="Paid"
    <?php echo
        $purchase["payment_status"]
        === "Paid"
        ? "selected"
        : "";
    ?>>

Paid

</option>


<option
    value="Pending"
    <?php echo
        $purchase["payment_status"]
        === "Pending"
        ? "selected"
        : "";
    ?>>

Pending

</option>


</select>

</div>

</div>


<!-- PRODUCTS -->

<div
    class="d-flex justify-content-between align-items-center mt-4">


<h5 class="fw-bold">

<i class="fa-solid fa-boxes-stacked me-2"></i>

Purchase Items

</h5>


<button
    type="button"
    class="btn btn-outline-primary btn-sm"
    id="addRow">

<i class="fa-solid fa-plus me-1"></i>

Add Product

</button>


</div>


<hr>


<div class="table-responsive">


<table
    class="table align-middle">


<thead>

<tr>

<th>
Product
</th>

<th>
Quantity
</th>

<th>
Purchase Price
</th>

<th>
Subtotal
</th>

<th>
Action
</th>

</tr>

</thead>


<tbody id="purchaseItems">


<?php foreach (
    $purchase_items as $item
): ?>


<tr class="purchase-row">


<td>

<select
    name="product_id[]"
    class="form-select product-select"
    required>


<?php

$products->data_seek(0);

while (
    $product =
    $products->fetch_assoc()
):

?>


<option
    value="<?php echo $product["product_id"]; ?>"
    data-price="<?php echo $product["purchase_price"]; ?>"
    <?php echo
        $product["product_id"]
        == $item["product_id"]
        ? "selected"
        : "";
    ?>>

<?php echo htmlspecialchars(
    $product["product_name"]
); ?>


<?php if (
    !empty($product["brand"])
): ?>

-
<?php echo htmlspecialchars(
    $product["brand"]
); ?>

<?php endif; ?>

</option>


<?php endwhile; ?>


</select>

</td>


<td>

<input
    type="number"
    name="quantity[]"
    class="form-control quantity"
    min="1"
    value="<?php echo $item["quantity"]; ?>"
    required>

</td>


<td>

<input
    type="number"
    name="purchase_price[]"
    class="form-control price"
    min="0"
    step="0.01"
    value="<?php echo $item["purchase_price"]; ?>"
    required>

</td>


<td>

<input
    type="text"
    class="form-control subtotal"
    value="Rs. <?php echo number_format(
        $item["subtotal"],
        2
    ); ?>"
    readonly>

</td>


<td>

<button
    type="button"
    class="btn btn-outline-danger remove-row">

<i class="fa-solid fa-trash"></i>

</button>

</td>


</tr>


<?php endforeach; ?>


</tbody>

</table>

</div>


<!-- TOTAL -->

<div
    class="row justify-content-end mt-3">


<div class="col-md-4">


<div class="card border-0 bg-light">


<div class="card-body">


<div
    class="d-flex justify-content-between">


<span class="fw-semibold">

Grand Total

</span>


<span
    class="fw-bold fs-5"
    id="grandTotal">

Rs. 0.00

</span>


</div>


</div>

</div>


</div>

</div>


<!-- REMARKS -->

<div class="mt-4">

<label class="form-label">
Remarks
</label>


<textarea
    name="remarks"
    class="form-control"
    rows="3"><?php echo htmlspecialchars(
        $purchase["remarks"] ?? ""
    ); ?></textarea>

</div>


<!-- BUTTONS -->

<div
    class="d-flex justify-content-end gap-2 mt-4">


<a
    href="purchases.php"
    class="btn btn-secondary">

Cancel

</a>


<button
    type="submit"
    class="btn btn-primary">

<i class="fa-solid fa-save me-2"></i>

Update Purchase

</button>


</div>


</form>


</div>


</main>

</div>


<script>


// =========================================================
// PRODUCT OPTIONS TEMPLATE
// =========================================================

const productOptions = `

<option value="">
    Select Product
</option>

<?php

$products->data_seek(0);

while (
    $product =
    $products->fetch_assoc()
):

?>

<option
    value="<?php echo $product["product_id"]; ?>"
    data-price="<?php echo $product["purchase_price"]; ?>">

<?php echo htmlspecialchars(
    $product["product_name"]
); ?>

<?php if (
    !empty($product["brand"])
): ?>

-
<?php echo htmlspecialchars(
    $product["brand"]
); ?>

<?php endif; ?>

</option>

<?php endwhile; ?>

`;


// =========================================================
// ADD ROW
// =========================================================

document.getElementById("addRow")
.addEventListener(
    "click",
    function () {

        const tbody =
            document.getElementById(
                "purchaseItems"
            );


        const firstRow =
            document.querySelector(
                ".purchase-row"
            );


        const newRow =
            firstRow.cloneNode(true);


        newRow.querySelector(
            ".product-select"
        ).innerHTML =
            productOptions;


        newRow.querySelector(
            ".quantity"
        ).value = 1;


        newRow.querySelector(
            ".price"
        ).value = 0;


        newRow.querySelector(
            ".subtotal"
        ).value = "Rs. 0.00";


        tbody.appendChild(newRow);


        calculateTotal();

    }
);


// =========================================================
// REMOVE ROW
// =========================================================

document.addEventListener(
    "click",
    function (event) {

        if (
            event.target.closest(
                ".remove-row"
            )
        ) {

            const rows =
                document.querySelectorAll(
                    ".purchase-row"
                );


            if (rows.length > 1) {

                event.target
                    .closest(
                        ".purchase-row"
                    )
                    .remove();


                calculateTotal();

            }

        }

    }
);


// =========================================================
// PRODUCT CHANGE
// =========================================================

document.addEventListener(
    "change",
    function (event) {

        if (
            event.target.classList.contains(
                "product-select"
            )
        ) {

            const option =
                event.target.options[
                    event.target.selectedIndex
                ];


            const price =
                option.dataset.price || 0;


            const row =
                event.target.closest(
                    ".purchase-row"
                );


            row.querySelector(
                ".price"
            ).value = price;


            calculateRow(row);

        }

    }
);


// =========================================================
// INPUT
// =========================================================

document.addEventListener(
    "input",
    function (event) {

        if (
            event.target.classList.contains(
                "quantity"
            ) ||
            event.target.classList.contains(
                "price"
            )
        ) {

            calculateRow(
                event.target.closest(
                    ".purchase-row"
                )
            );

        }

    }
);


// =========================================================
// CALCULATE ROW
// =========================================================

function calculateRow(row) {

    const quantity =
        parseFloat(
            row.querySelector(
                ".quantity"
            ).value
        ) || 0;


    const price =
        parseFloat(
            row.querySelector(
                ".price"
            ).value
        ) || 0;


    const subtotal =
        quantity * price;


    row.querySelector(
        ".subtotal"
    ).value =
        "Rs. " +
        subtotal.toFixed(2);


    calculateTotal();

}


// =========================================================
// TOTAL
// =========================================================

function calculateTotal() {

    let total = 0;


    document
        .querySelectorAll(
            ".purchase-row"
        )
        .forEach(
            function (row) {

                const quantity =
                    parseFloat(
                        row.querySelector(
                            ".quantity"
                        ).value
                    ) || 0;


                const price =
                    parseFloat(
                        row.querySelector(
                            ".price"
                        ).value
                    ) || 0;


                total +=
                    quantity * price;

            }
        );


    document.getElementById(
        "grandTotal"
    ).innerText =
        "Rs. " +
        total.toFixed(2);

}


// =========================================================
// INITIAL TOTAL
// =========================================================

calculateTotal();

</script>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>