<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

require_once "../../config/database.php";


// =========================================================
// FETCH PURCHASES
// =========================================================

$sql = "
    SELECT
        p.purchase_id,
        p.invoice_number,
        p.purchase_date,
        p.total_amount,
        p.payment_status,
        s.supplier_name
    FROM purchases p
    INNER JOIN suppliers s
        ON p.supplier_id = s.supplier_id
    ORDER BY p.purchase_id DESC
";

$result = $conn->query($sql);


// =========================================================
// MESSAGES
// =========================================================

$success = $_GET["success"] ?? "";
$error = $_GET["error"] ?? "";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>SmartIMS | Purchases</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


    <!-- SmartIMS CSS -->

    <link
        rel="stylesheet"
        href="../../assets/css/style.css">

    <link
        rel="stylesheet"
        href="../../assets/css/dashboard.css">

</head>


<body>


<!-- =====================================================
     SIDEBAR
===================================================== -->

<?php require_once "../../includes/slidebar.php"; ?>


<!-- =====================================================
     MAIN CONTENT
===================================================== -->

<div class="main-content">


    <!-- NAVBAR -->

    <?php require_once "../../includes/navbar.php"; ?>


    <!-- PAGE CONTENT -->

    <main class="page-content">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <div
            class="d-flex justify-content-between align-items-center mb-4">


            <div>

                <h1 class="page-heading">

                    <i class="fa-solid fa-cart-shopping me-2"></i>

                    Purchase Management

                </h1>


                <p class="text-muted mb-0">

                    Manage supplier purchases and inventory stock.

                </p>

            </div>


            <a
                href="add_purchase.php"
                class="btn btn-primary">

                <i class="fa-solid fa-plus me-2"></i>

                New Purchase

            </a>


        </div>


        <!-- =================================================
             ALERTS
        ================================================== -->


        <?php if ($success !== ""): ?>

            <div class="alert alert-success alert-dismissible fade show">

                <i class="fa-solid fa-circle-check me-2"></i>

                <?php echo htmlspecialchars($success); ?>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
                </button>

            </div>

        <?php endif; ?>


        <?php if ($error !== ""): ?>

            <div class="alert alert-danger alert-dismissible fade show">

                <i class="fa-solid fa-circle-exclamation me-2"></i>

                <?php echo htmlspecialchars($error); ?>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
                </button>

            </div>

        <?php endif; ?>


        <!-- =================================================
             PURCHASE TABLE
        ================================================== -->


        <div class="dashboard-panel">


            <div
                class="d-flex justify-content-between align-items-center mb-3">


                <div>

                    <h5 class="fw-bold mb-1">

                        <i class="fa-solid fa-receipt me-2"></i>

                        Purchase Records

                    </h5>


                    <small class="text-muted">

                        All purchase transactions

                    </small>

                </div>


                <span class="badge bg-primary">

                    <?php echo $result ? $result->num_rows : 0; ?>

                    Purchases

                </span>


            </div>


            <div class="table-responsive">


                <table
                    class="table table-hover align-middle">


                    <thead>


                        <tr>

                            <th>#</th>

                            <th>Invoice</th>

                            <th>Supplier</th>

                            <th>Date</th>

                            <th>Total Amount</th>

                            <th>Payment Status</th>

                            <th class="text-center">
                                Actions
                            </th>

                        </tr>


                    </thead>


                    <tbody>


                    <?php if ($result && $result->num_rows > 0): ?>


                        <?php

                        $count = 1;

                        while ($purchase = $result->fetch_assoc()):

                        ?>


                            <tr>


                                <!-- NUMBER -->

                                <td>

                                    <?php echo $count++; ?>

                                </td>


                                <!-- INVOICE -->

                                <td>

                                    <span class="fw-semibold">

                                        <?php

                                        echo htmlspecialchars(
                                            $purchase["invoice_number"]
                                        );

                                        ?>

                                    </span>

                                </td>


                                <!-- SUPPLIER -->

                                <td>

                                    <i
                                        class="fa-solid fa-truck text-muted me-2">
                                    </i>


                                    <?php

                                    echo htmlspecialchars(
                                        $purchase["supplier_name"]
                                    );

                                    ?>

                                </td>


                                <!-- DATE -->

                                <td>

                                    <?php

                                    echo !empty(
                                        $purchase["purchase_date"]
                                    )

                                        ? date(
                                            "d M Y",
                                            strtotime(
                                                $purchase["purchase_date"]
                                            )
                                        )

                                        : "-";

                                    ?>

                                </td>


                                <!-- TOTAL -->

                                <td>

                                    <strong>

                                        Rs.

                                        <?php

                                        echo number_format(
                                            (float)$purchase["total_amount"],
                                            2
                                        );

                                        ?>

                                    </strong>

                                </td>


                                <!-- PAYMENT STATUS -->

                                <td>


                                    <?php if (
                                        $purchase["payment_status"]
                                        === "Paid"
                                    ): ?>


                                        <span
                                            class="badge bg-success">

                                            <i
                                                class="fa-solid fa-check me-1">
                                            </i>

                                            Paid

                                        </span>


                                    <?php else: ?>


                                        <span
                                            class="badge bg-warning text-dark">

                                            <i
                                                class="fa-solid fa-clock me-1">
                                            </i>

                                            Pending

                                        </span>


                                    <?php endif; ?>


                                </td>


                                <!-- ACTIONS -->

                                <td class="text-center">


                                    <div
                                        class="btn-group"
                                        role="group">


                                        <!-- VIEW / EDIT -->

                                        <a
                                            href="edit_purchase.php?id=<?php echo $purchase["purchase_id"]; ?>"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Edit Purchase">

                                            <i
                                                class="fa-solid fa-pen-to-square">
                                            </i>

                                        </a>


                                        <!-- DELETE -->

                                        <a
                                            href="delete_purchase.php?id=<?php echo $purchase["purchase_id"]; ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Delete Purchase"
                                            onclick="return confirm('Are you sure you want to delete this purchase?');">

                                            <i
                                                class="fa-solid fa-trash">
                                            </i>

                                        </a>


                                    </div>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <!-- EMPTY STATE -->

                        <tr>

                            <td
                                colspan="7"
                                class="text-center py-5">


                                <div class="text-muted">


                                    <i
                                        class="fa-solid fa-cart-shopping fa-3x mb-3">
                                    </i>


                                    <h5>

                                        No purchases found

                                    </h5>


                                    <p class="mb-3">

                                        You haven't recorded any
                                        purchases yet.

                                    </p>


                                    <a
                                        href="add_purchase.php"
                                        class="btn btn-primary">

                                        <i
                                            class="fa-solid fa-plus me-2">
                                        </i>

                                        Add First Purchase

                                    </a>


                                </div>


                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>


                </table>


            </div>


        </div>


    </main>


</div>


<!-- =====================================================
     BOOTSTRAP JS
===================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>