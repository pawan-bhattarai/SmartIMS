<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

require_once "../../config/database.php";


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
        sku,
        purchase_price,
        unit,
        stock_quantity
    FROM products
    WHERE status = 'Active'
    ORDER BY product_name ASC
");


// =========================================================
// PROCESS PURCHASE
// =========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $supplier_id = (int)($_POST["supplier_id"] ?? 0);

    $invoice_number = trim(
        $_POST["invoice_number"] ?? ""
    );

    $purchase_date = $_POST["purchase_date"] ?? date("Y-m-d");

    $payment_status = $_POST["payment_status"] ?? "Paid";

    $remarks = trim(
        $_POST["remarks"] ?? ""
    );

    $product_ids = $_POST["product_id"] ?? [];
    $quantities = $_POST["quantity"] ?? [];
    $prices = $_POST["purchase_price"] ?? [];


    // -----------------------------------------------------
    // BASIC VALIDATION
    // -----------------------------------------------------

    if ($supplier_id <= 0) {

        header(
            "Location: add_purchase.php?error=Please select a supplier"
        );

        exit();
    }


    if ($invoice_number === "") {

        header(
            "Location: add_purchase.php?error=Invoice number is required"
        );

        exit();
    }


    if (empty($product_ids)) {

        header(
            "Location: add_purchase.php?error=Please add at least one product"
        );

        exit();
    }


    // -----------------------------------------------------
    // CHECK INVOICE NUMBER
    // -----------------------------------------------------

    $check = $conn->prepare("
        SELECT purchase_id
        FROM purchases
        WHERE invoice_number = ?
    ");

    $check->bind_param(
        "s",
        $invoice_number
    );

    $check->execute();

    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {

        header(
            "Location: add_purchase.php?error=Invoice number already exists"
        );

        exit();
    }

    $check->close();


    // =====================================================
    // START TRANSACTION
    // =====================================================

    $conn->begin_transaction();


    try {

        // -------------------------------------------------
        // CALCULATE TOTAL
        // -------------------------------------------------

        $total_amount = 0;

        $items = [];


        foreach ($product_ids as $index => $product_id) {

            $product_id = (int)$product_id;

            $quantity = (int)($quantities[$index] ?? 0);

            $purchase_price = (float)($prices[$index] ?? 0);


            if (
                $product_id <= 0 ||
                $quantity <= 0 ||
                $purchase_price < 0
            ) {

                throw new Exception(
                    "Invalid product, quantity or price."
                );
            }


            $subtotal =
                $quantity * $purchase_price;


            $total_amount += $subtotal;


            $items[] = [
                "product_id" => $product_id,
                "quantity" => $quantity,
                "purchase_price" => $purchase_price,
                "subtotal" => $subtotal
            ];
        }


        // -------------------------------------------------
        // INSERT PURCHASE
        // -------------------------------------------------

        $purchase_stmt = $conn->prepare("
            INSERT INTO purchases
            (
                supplier_id,
                invoice_number,
                purchase_date,
                total_amount,
                payment_status,
                remarks,
                purchased_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");


        $purchased_by = $_SESSION["user_id"];


        $purchase_stmt->bind_param(
            "issdssi",
            $supplier_id,
            $invoice_number,
            $purchase_date,
            $total_amount,
            $payment_status,
            $remarks,
            $purchased_by
        );


        if (!$purchase_stmt->execute()) {

            throw new Exception(
                "Failed to create purchase."
            );
        }


        $purchase_id =
            $conn->insert_id;


        $purchase_stmt->close();


        // -------------------------------------------------
        // INSERT PURCHASE DETAILS
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
        // UPDATE STOCK
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


            // Insert detail

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
                    "Failed to save purchase details."
                );
            }


            // Increase stock

            $stock_stmt->bind_param(
                "ii",
                $quantity,
                $product_id
            );


            if (!$stock_stmt->execute()) {

                throw new Exception(
                    "Failed to update product stock."
                );
            }
        }


        $detail_stmt->close();
        $stock_stmt->close();


        // -------------------------------------------------
        // EVERYTHING SUCCESSFUL
        // -------------------------------------------------

        $conn->commit();


        header(
            "Location: purchases.php?success=Purchase added successfully and stock updated"
        );

        exit();


    } catch (Exception $e) {

        // -------------------------------------------------
        // ROLLBACK
        // -------------------------------------------------

        $conn->rollback();


        header(
            "Location: add_purchase.php?error="
            . urlencode($e->getMessage())
        );

        exit();
    }
}


$error = $_GET["error"] ?? "";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>SmartIMS | Add Purchase</title>


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


    <!-- HEADER -->

    <div
        class="d-flex justify-content-between align-items-center mb-4">


        <div>

            <h1 class="page-heading">

                <i class="fa-solid fa-cart-plus me-2"></i>

                Add Purchase

            </h1>


            <p class="text-muted mb-0">

                Record a new supplier purchase.

            </p>

        </div>


        <a
            href="purchases.php"
            class="btn btn-secondary">

            <i class="fa-solid fa-arrow-left me-2"></i>

            Back to Purchases

        </a>

    </div>


    <!-- ERROR -->

    <?php if ($error !== ""): ?>

        <div class="alert alert-danger">

            <i
                class="fa-solid fa-circle-exclamation me-2">
            </i>

            <?php echo htmlspecialchars($error); ?>

        </div>

    <?php endif; ?>


    <div class="dashboard-panel">


        <form
            method="POST"
            id="purchaseForm">


            <!-- PURCHASE INFORMATION -->

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

                        <option value="">
                            Select Supplier
                        </option>


                        <?php while (
                            $supplier = $suppliers->fetch_assoc()
                        ): ?>

                            <option
                                value="<?php echo $supplier["supplier_id"]; ?>">

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
                        placeholder="e.g. PUR-0001"
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
                        value="<?php echo date("Y-m-d"); ?>"
                        required>

                </div>


                <div class="col-md-4 mb-3">

                    <label class="form-label">
                        Payment Status
                    </label>

                    <select
                        name="payment_status"
                        class="form-select">

                        <option value="Paid">
                            Paid
                        </option>

                        <option value="Pending">
                            Pending
                        </option>

                    </select>

                </div>

            </div>


            <!-- PRODUCTS -->

            <div
                class="d-flex justify-content-between align-items-center mt-4">


                <div>

                    <h5 class="fw-bold mb-1">

                        <i class="fa-solid fa-boxes-stacked me-2"></i>

                        Purchase Items

                    </h5>

                    <small class="text-muted">

                        Add one or more products.

                    </small>

                </div>


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
                    class="table align-middle"
                    id="purchaseTable">


                    <thead>

                        <tr>

                            <th style="min-width:250px;">
                                Product
                            </th>

                            <th style="width:150px;">
                                Quantity
                            </th>

                            <th style="width:180px;">
                                Purchase Price
                            </th>

                            <th style="width:180px;">
                                Subtotal
                            </th>

                            <th style="width:70px;">
                                Action
                            </th>

                        </tr>

                    </thead>


                    <tbody id="purchaseItems">


                        <tr class="purchase-row">


                            <td>

                                <select
                                    name="product_id[]"
                                    class="form-select product-select"
                                    required>

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

                                </select>

                            </td>


                            <td>

                                <input
                                    type="number"
                                    name="quantity[]"
                                    class="form-control quantity"
                                    min="1"
                                    value="1"
                                    required>

                            </td>


                            <td>

                                <input
                                    type="number"
                                    name="purchase_price[]"
                                    class="form-control price"
                                    min="0"
                                    step="0.01"
                                    value="0"
                                    required>

                            </td>


                            <td>

                                <input
                                    type="text"
                                    class="form-control subtotal"
                                    value="Rs. 0.00"
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


                    </tbody>


                </table>

            </div>


            <!-- TOTAL -->

            <div class="row justify-content-end mt-3">


                <div class="col-md-4">


                    <div
                        class="card border-0 bg-light">


                        <div
                            class="card-body">


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
                    rows="3"
                    placeholder="Optional remarks..."></textarea>

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

                    Save Purchase

                </button>

            </div>


        </form>


    </div>


</main>

</div>


<script
src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
</script>


<script>


// =========================================================
// ADD PRODUCT ROW
// =========================================================

document.getElementById("addRow").addEventListener(
    "click",
    function () {

        const tbody =
            document.getElementById("purchaseItems");

        const firstRow =
            document.querySelector(".purchase-row");

        const newRow =
            firstRow.cloneNode(true);


        newRow.querySelector(".product-select").value = "";

        newRow.querySelector(".quantity").value = 1;

        newRow.querySelector(".price").value = 0;

        newRow.querySelector(".subtotal").value =
            "Rs. 0.00";


        tbody.appendChild(newRow);

    }
);


// =========================================================
// REMOVE ROW
// =========================================================

document.addEventListener(
    "click",
    function (event) {

        if (
            event.target.closest(".remove-row")
        ) {

            const rows =
                document.querySelectorAll(".purchase-row");


            if (rows.length > 1) {

                event.target
                    .closest(".purchase-row")
                    .remove();


                calculateTotal();

            }

        }

    }
);


// =========================================================
// PRODUCT PRICE AUTO-FILL
// =========================================================

document.addEventListener(
    "change",
    function (event) {

        if (
            event.target.classList.contains(
                "product-select"
            )
        ) {

            const selected =
                event.target.options[
                    event.target.selectedIndex
                ];


            const price =
                selected.dataset.price || 0;


            const row =
                event.target.closest(
                    ".purchase-row"
                );


            row.querySelector(".price").value =
                price;


            calculateRow(row);

        }

    }
);


// =========================================================
// CALCULATE
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

            const row =
                event.target.closest(
                    ".purchase-row"
                );


            calculateRow(row);

        }

    }
);


function calculateRow(row) {

    const quantity =
        parseFloat(
            row.querySelector(".quantity").value
        ) || 0;


    const price =
        parseFloat(
            row.querySelector(".price").value
        ) || 0;


    const subtotal =
        quantity * price;


    row.querySelector(".subtotal").value =
        "Rs. " + subtotal.toFixed(2);


    calculateTotal();

}


function calculateTotal() {

    let total = 0;


    document
        .querySelectorAll(".purchase-row")
        .forEach(function (row) {

            const quantity =
                parseFloat(
                    row.querySelector(".quantity").value
                ) || 0;


            const price =
                parseFloat(
                    row.querySelector(".price").value
                ) || 0;


            total += quantity * price;

        });


    document.getElementById("grandTotal").innerText =
        "Rs. " + total.toFixed(2);

}


</script>


</body>
</html>