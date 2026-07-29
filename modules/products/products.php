<?php

session_start();

// =========================================================
// CHECK LOGIN
// =========================================================

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}


// =========================================================
// DATABASE CONNECTION
// =========================================================

require_once "../../config/database.php";


// =========================================================
// FETCH PRODUCTS
// JOIN CATEGORIES AND SUPPLIERS
// =========================================================

$sql = "SELECT
            p.product_id,
            p.product_name,
            p.brand,
            p.sku,
            p.barcode,
            p.purchase_price,
            p.selling_price,
            p.expiry_date,
            p.stock_quantity,
            p.minimum_stock,
            p.unit,
            p.product_image,
            p.description,
            p.status,

            c.category_name,

            s.supplier_name

        FROM products p

        INNER JOIN categories c
            ON p.category_id = c.category_id

        INNER JOIN suppliers s
            ON p.supplier_id = s.supplier_id

        ORDER BY p.product_id DESC";


$result = $conn->query($sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>SmartIMS | Products</title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- =====================================================
         FONT AWESOME
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


    <!-- =====================================================
         MAIN CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../../assets/css/style.css">


    <!-- =====================================================
         DASHBOARD CSS
    ====================================================== -->

    <link
        rel="stylesheet"
        href="../../assets/css/dashboard.css">

</head>


<body>


<!-- =========================================================
     SIDEBAR
========================================================= -->

<?php require_once "../../includes/slidebar.php"; ?>


<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="main-content">


    <!-- =====================================================
         NAVBAR
    ====================================================== -->

    <?php require_once "../../includes/navbar.php"; ?>


    <!-- =====================================================
         PAGE CONTENT
    ====================================================== -->

    <main class="page-content">


        <!-- =================================================
             PAGE HEADER
        ================================================== -->

        <div class="d-flex justify-content-between align-items-center mb-4">


            <div>

                <h1 class="page-heading">

                    <i class="fa-solid fa-boxes-stacked me-2"></i>

                    Products

                </h1>


                <p class="text-muted mb-0">

                    Manage products, pricing and inventory.

                </p>

            </div>


            <!-- ADD PRODUCT -->

            <a
                href="add_product.php"
                class="btn btn-primary">

                <i class="fa-solid fa-plus me-2"></i>

                Add Product

            </a>

        </div>



        <!-- =================================================
             SUCCESS MESSAGE
        ================================================== -->

        <?php if (isset($_GET["success"])): ?>

            <div
                class="alert alert-success alert-dismissible fade show">

                <i class="fa-solid fa-circle-check me-2"></i>

                <?php echo htmlspecialchars($_GET["success"]); ?>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
                </button>

            </div>

        <?php endif; ?>



        <!-- =================================================
             ERROR MESSAGE
        ================================================== -->

        <?php if (isset($_GET["error"])): ?>

            <div
                class="alert alert-danger alert-dismissible fade show">

                <i class="fa-solid fa-circle-exclamation me-2"></i>

                <?php echo htmlspecialchars($_GET["error"]); ?>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
                </button>

            </div>

        <?php endif; ?>



        <!-- =================================================
             PRODUCT TABLE
        ================================================== -->

        <div class="dashboard-panel">


            <div class="table-responsive">


                <table class="table table-hover align-middle">


                    <!-- TABLE HEADER -->

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Product</th>

                            <th>Category</th>

                            <th>Supplier</th>

                            <th>Purchase Price</th>

                            <th>Selling Price</th>

                            <th>Stock</th>

                            <th>Status</th>

                            <th class="text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <!-- TABLE BODY -->

                    <tbody>


                    <?php if ($result && $result->num_rows > 0): ?>


                        <?php $count = 1; ?>


                        <?php while ($product = $result->fetch_assoc()): ?>


                            <tr>


                                <!-- NUMBER -->

                                <td>

                                    <?php echo $count++; ?>

                                </td>



                                <!-- PRODUCT -->

                                <td>

                                    <div class="d-flex align-items-center">


                                        <!-- Product Image -->

                                        <?php if (
                                            !empty($product["product_image"])
                                        ): ?>

                                            <img
                                                src="../../assets/images/products/<?php echo htmlspecialchars($product["product_image"]); ?>"
                                                alt="Product"
                                                width="45"
                                                height="45"
                                                class="rounded me-2"
                                                style="object-fit: cover;">


                                        <?php else: ?>

                                            <div
                                                class="bg-light rounded d-flex align-items-center justify-content-center me-2"
                                                style="width:45px;height:45px;">

                                                <i
                                                    class="fa-solid fa-box text-muted">
                                                </i>

                                            </div>

                                        <?php endif; ?>


                                        <div>

                                            <strong>

                                                <?php
                                                echo htmlspecialchars(
                                                    $product["product_name"]
                                                );
                                                ?>

                                            </strong>


                                            <?php if (
                                                !empty($product["brand"])
                                            ): ?>

                                                <small
                                                    class="d-block text-muted">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $product["brand"]
                                                    );
                                                    ?>

                                                </small>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                </td>



                                <!-- CATEGORY -->

                                <td>

                                    <span class="badge bg-light text-dark">

                                        <?php
                                        echo htmlspecialchars(
                                            $product["category_name"]
                                        );
                                        ?>

                                    </span>

                                </td>



                                <!-- SUPPLIER -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $product["supplier_name"]
                                    );
                                    ?>

                                </td>



                                <!-- PURCHASE PRICE -->

                                <td>

                                    Rs.
                                    <?php
                                    echo number_format(
                                        $product["purchase_price"],
                                        2
                                    );
                                    ?>

                                </td>



                                <!-- SELLING PRICE -->

                                <td>

                                    <strong>

                                        Rs.
                                        <?php
                                        echo number_format(
                                            $product["selling_price"],
                                            2
                                        );
                                        ?>

                                    </strong>

                                </td>



                                <!-- STOCK -->

                                <td>


                                    <?php

                                    $stock =
                                        (int) $product["stock_quantity"];

                                    $minimum =
                                        (int) $product["minimum_stock"];


                                    if ($stock <= 0) {

                                        $stock_class = "bg-danger";

                                        $stock_text = "Out of Stock";

                                    } elseif ($stock <= $minimum) {

                                        $stock_class = "bg-warning text-dark";

                                        $stock_text = "Low Stock";

                                    } else {

                                        $stock_class = "bg-success";

                                        $stock_text = "In Stock";

                                    }

                                    ?>


                                    <span
                                        class="badge <?php echo $stock_class; ?>">

                                        <?php echo $stock; ?>

                                        <?php
                                        echo htmlspecialchars(
                                            $product["unit"]
                                        );
                                        ?>

                                    </span>


                                    <small
                                        class="d-block text-muted mt-1">

                                        <?php echo $stock_text; ?>

                                    </small>

                                </td>



                                <!-- STATUS -->

                                <td>


                                    <?php if (
                                        $product["status"] === "Active"
                                    ): ?>

                                        <span
                                            class="badge bg-success">

                                            Active

                                        </span>

                                    <?php else: ?>

                                        <span
                                            class="badge bg-secondary">

                                            Inactive

                                        </span>

                                    <?php endif; ?>


                                </td>



                                <!-- ACTIONS -->

                                <td class="text-center">


                                    <!-- EDIT -->

                                    <a
                                        href="edit_product.php?id=<?php echo $product['product_id']; ?>"
                                        class="btn btn-sm btn-outline-primary me-1"
                                        title="Edit">

                                        <i
                                            class="fa-solid fa-pen">
                                        </i>

                                    </a>



                                    <!-- DELETE -->

                                    <a
                                        href="delete_product.php?id=<?php echo $product['product_id']; ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete"
                                        onclick="return confirm('Are you sure you want to delete this product?');">

                                        <i
                                            class="fa-solid fa-trash">
                                        </i>

                                    </a>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <!-- NO PRODUCTS -->

                        <tr>

                            <td
                                colspan="9"
                                class="text-center py-5">


                                <i
                                    class="fa-solid fa-box-open fs-1 text-muted mb-3">
                                </i>


                                <p
                                    class="text-muted mb-0">

                                    No products found.

                                </p>


                                <p
                                    class="text-muted small">

                                    Click "Add Product" to create your first product.

                                </p>


                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>


            </div>

        </div>


    </main>

</div>



<!-- =========================================================
     BOOTSTRAP JS
========================================================= -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>