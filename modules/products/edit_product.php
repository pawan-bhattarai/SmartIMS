<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

require_once "../../config/database.php";


/* =========================================================
   GET PRODUCT ID
========================================================= */

$id = (int)($_GET["id"] ?? $_POST["product_id"] ?? 0);

if ($id <= 0) {
    header("Location: products.php?error=Invalid product ID");
    exit();
}


/* =========================================================
   UPDATE PRODUCT
========================================================= */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_name = trim($_POST["product_name"] ?? "");
    $brand = trim($_POST["brand"] ?? "");
    $sku = trim($_POST["sku"] ?? "");
    $barcode = trim($_POST["barcode"] ?? "");

    $category_id = (int)($_POST["category_id"] ?? 0);
    $supplier_id = (int)($_POST["supplier_id"] ?? 0);

    $purchase_price = (float)($_POST["purchase_price"] ?? 0);
    $selling_price = (float)($_POST["selling_price"] ?? 0);

    $expiry_date = !empty($_POST["expiry_date"])
        ? $_POST["expiry_date"]
        : null;

    $stock_quantity = (int)($_POST["stock_quantity"] ?? 0);
    $minimum_stock = (int)($_POST["minimum_stock"] ?? 10);

    $unit = $_POST["unit"] ?? "pcs";

    $description = trim($_POST["description"] ?? "");

    $status = $_POST["status"] ?? "Active";


    /* -----------------------------------------------------
       VALIDATION
    ----------------------------------------------------- */

    if ($product_name === "") {
        header("Location: edit_product.php?id=$id&error=Product name is required");
        exit();
    }

    if ($category_id <= 0 || $supplier_id <= 0) {
        header("Location: edit_product.php?id=$id&error=Category and supplier are required");
        exit();
    }


    /* -----------------------------------------------------
       CHECK SKU
    ----------------------------------------------------- */

    if ($sku !== "") {

        $stmt = $conn->prepare(
            "SELECT product_id
             FROM products
             WHERE sku = ?
             AND product_id != ?"
        );

        $stmt->bind_param("si", $sku, $id);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            header(
                "Location: edit_product.php?id=$id&error=SKU already exists"
            );

            exit();
        }

        $stmt->close();
    }


    /* -----------------------------------------------------
       CHECK BARCODE
    ----------------------------------------------------- */

    if ($barcode !== "") {

        $stmt = $conn->prepare(
            "SELECT product_id
             FROM products
             WHERE barcode = ?
             AND product_id != ?"
        );

        $stmt->bind_param("si", $barcode, $id);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0) {

            header(
                "Location: edit_product.php?id=$id&error=Barcode already exists"
            );

            exit();
        }

        $stmt->close();
    }


    /* -----------------------------------------------------
       GET CURRENT IMAGE
    ----------------------------------------------------- */

    $stmt = $conn->prepare(
        "SELECT product_image
         FROM products
         WHERE product_id = ?"
    );

    $stmt->bind_param("i", $id);

    $stmt->execute();

    $current_result = $stmt->get_result();

    $current_product = $current_result->fetch_assoc();

    $old_image = $current_product["product_image"] ?? null;

    $stmt->close();


    $product_image = $old_image;


    /* -----------------------------------------------------
       NEW IMAGE
    ----------------------------------------------------- */

    if (
        isset($_FILES["product_image"]) &&
        $_FILES["product_image"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["product_image"]["error"] !== UPLOAD_ERR_OK) {

            header(
                "Location: edit_product.php?id=$id&error=Image upload failed"
            );

            exit();
        }


        $allowed_types = [
            "image/jpeg",
            "image/png",
            "image/webp"
        ];


        $file_type = mime_content_type(
            $_FILES["product_image"]["tmp_name"]
        );


        if (!in_array($file_type, $allowed_types)) {

            header(
                "Location: edit_product.php?id=$id&error=Only JPG, PNG and WEBP images are allowed"
            );

            exit();
        }


        if ($_FILES["product_image"]["size"] > 2 * 1024 * 1024) {

            header(
                "Location: edit_product.php?id=$id&error=Image must be less than 2MB"
            );

            exit();
        }


        $extension = strtolower(
            pathinfo(
                $_FILES["product_image"]["name"],
                PATHINFO_EXTENSION
            )
        );


        $product_image =
            uniqid("product_", true) . "." . $extension;


        $upload_directory =
            "../../assets/images/products/";


        if (!is_dir($upload_directory)) {
            mkdir($upload_directory, 0777, true);
        }


        $upload_path =
            $upload_directory . $product_image;


        if (
            !move_uploaded_file(
                $_FILES["product_image"]["tmp_name"],
                $upload_path
            )
        ) {

            header(
                "Location: edit_product.php?id=$id&error=Could not save image"
            );

            exit();
        }


        /* Delete old image */

        if (
            !empty($old_image) &&
            file_exists($upload_directory . $old_image)
        ) {

            unlink($upload_directory . $old_image);
        }
    }


    /* -----------------------------------------------------
       UPDATE DATABASE
    ----------------------------------------------------- */

    $sql = "UPDATE products SET

                category_id = ?,
                supplier_id = ?,
                product_name = ?,
                brand = ?,
                sku = ?,
                barcode = ?,
                purchase_price = ?,
                selling_price = ?,
                expiry_date = ?,
                stock_quantity = ?,
                minimum_stock = ?,
                unit = ?,
                product_image = ?,
                description = ?,
                status = ?

            WHERE product_id = ?";


    $stmt = $conn->prepare($sql);


    $stmt->bind_param(
        "iissssddsiissssi",
        $category_id,
        $supplier_id,
        $product_name,
        $brand,
        $sku,
        $barcode,
        $purchase_price,
        $selling_price,
        $expiry_date,
        $stock_quantity,
        $minimum_stock,
        $unit,
        $product_image,
        $description,
        $status,
        $id
    );


    if ($stmt->execute()) {

        header(
            "Location: products.php?success=Product updated successfully"
        );

        exit();

    } else {

        header(
            "Location: edit_product.php?id=$id&error=Failed to update product"
        );

        exit();
    }
}


/* =========================================================
   FETCH PRODUCT
========================================================= */

$stmt = $conn->prepare(
    "SELECT *
     FROM products
     WHERE product_id = ?"
);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

$product = $result->fetch_assoc();

$stmt->close();


if (!$product) {

    header("Location: products.php?error=Product not found");

    exit();
}


/* =========================================================
   FETCH CATEGORIES
========================================================= */

$category_result = $conn->query(
    "SELECT category_id, category_name
     FROM categories
     WHERE status = 'Active'
     ORDER BY category_name"
);


/* =========================================================
   FETCH SUPPLIERS
========================================================= */

$supplier_result = $conn->query(
    "SELECT supplier_id, supplier_name
     FROM suppliers
     WHERE status = 'Active'
     ORDER BY supplier_name"
);


$error = $_GET["error"] ?? "";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>SmartIMS | Edit Product</title>


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


        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h1 class="page-heading">

                    <i class="fa-solid fa-pen-to-square me-2"></i>

                    Edit Product

                </h1>

                <p class="text-muted mb-0">

                    Update product information.

                </p>

            </div>


            <a
                href="products.php"
                class="btn btn-secondary">

                <i class="fa-solid fa-arrow-left me-2"></i>

                Back to Products

            </a>

        </div>


        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <div class="dashboard-panel">


            <form
                method="POST"
                enctype="multipart/form-data">


                <input
                    type="hidden"
                    name="product_id"
                    value="<?php echo $product["product_id"]; ?>">


                <!-- BASIC -->

                <h5 class="fw-bold">

                    <i class="fa-solid fa-circle-info me-2"></i>

                    Basic Information

                </h5>

                <hr>


                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Product Name *
                        </label>

                        <input
                            type="text"
                            name="product_name"
                            class="form-control"
                            value="<?php echo htmlspecialchars($product["product_name"]); ?>"
                            required>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Brand
                        </label>

                        <input
                            type="text"
                            name="brand"
                            class="form-control"
                            value="<?php echo htmlspecialchars($product["brand"] ?? ""); ?>">

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            SKU
                        </label>

                        <input
                            type="text"
                            name="sku"
                            class="form-control"
                            value="<?php echo htmlspecialchars($product["sku"] ?? ""); ?>">

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Barcode
                        </label>

                        <input
                            type="text"
                            name="barcode"
                            class="form-control"
                            value="<?php echo htmlspecialchars($product["barcode"] ?? ""); ?>">

                    </div>

                </div>


                <!-- CLASSIFICATION -->

                <h5 class="fw-bold mt-4">

                    <i class="fa-solid fa-layer-group me-2"></i>

                    Classification

                </h5>

                <hr>


                <div class="row">


                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Category *
                        </label>

                        <select
                            name="category_id"
                            class="form-select"
                            required>

                            <?php while (
                                $category = $category_result->fetch_assoc()
                            ): ?>

                                <option
                                    value="<?php echo $category["category_id"]; ?>"
                                    <?php
                                    echo (
                                        $category["category_id"]
                                        == $product["category_id"]
                                    )
                                    ? "selected"
                                    : "";
                                    ?>>

                                    <?php echo htmlspecialchars(
                                        $category["category_name"]
                                    ); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Supplier *
                        </label>

                        <select
                            name="supplier_id"
                            class="form-select"
                            required>

                            <?php while (
                                $supplier = $supplier_result->fetch_assoc()
                            ): ?>

                                <option
                                    value="<?php echo $supplier["supplier_id"]; ?>"
                                    <?php
                                    echo (
                                        $supplier["supplier_id"]
                                        == $product["supplier_id"]
                                    )
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
                            Unit
                        </label>

                        <select
                            name="unit"
                            class="form-select">

                            <?php

                            $units = [
                                "pcs" => "Pieces",
                                "kg" => "Kilogram",
                                "litre" => "Litre",
                                "packet" => "Packet",
                                "box" => "Box",
                                "bottle" => "Bottle"
                            ];

                            foreach ($units as $value => $label):

                            ?>

                                <option
                                    value="<?php echo $value; ?>"
                                    <?php
                                    echo (
                                        $product["unit"] === $value
                                    )
                                    ? "selected"
                                    : "";
                                    ?>>

                                    <?php echo $label; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                </div>


                <!-- PRICING -->

                <h5 class="fw-bold mt-4">

                    <i class="fa-solid fa-money-bill-wave me-2"></i>

                    Pricing

                </h5>

                <hr>


                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Purchase Price *
                        </label>

                        <input
                            type="number"
                            name="purchase_price"
                            class="form-control"
                            step="0.01"
                            value="<?php echo $product["purchase_price"]; ?>"
                            required>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Selling Price *
                        </label>

                        <input
                            type="number"
                            name="selling_price"
                            class="form-control"
                            step="0.01"
                            value="<?php echo $product["selling_price"]; ?>"
                            required>

                    </div>

                </div>


                <!-- INVENTORY -->

                <h5 class="fw-bold mt-4">

                    <i class="fa-solid fa-warehouse me-2"></i>

                    Inventory

                </h5>

                <hr>


                <div class="row">


                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Stock Quantity
                        </label>

                        <input
                            type="number"
                            name="stock_quantity"
                            class="form-control"
                            min="0"
                            value="<?php echo $product["stock_quantity"]; ?>">

                    </div>


                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Minimum Stock
                        </label>

                        <input
                            type="number"
                            name="minimum_stock"
                            class="form-control"
                            min="0"
                            value="<?php echo $product["minimum_stock"]; ?>">

                    </div>


                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Expiry Date
                        </label>

                        <input
                            type="date"
                            name="expiry_date"
                            class="form-control"
                            value="<?php echo htmlspecialchars($product["expiry_date"] ?? ""); ?>">

                    </div>

                </div>


                <!-- OTHER -->

                <h5 class="fw-bold mt-4">

                    <i class="fa-solid fa-file-lines me-2"></i>

                    Other Information

                </h5>

                <hr>


                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Product Image
                        </label>

                        <input
                            type="file"
                            name="product_image"
                            class="form-control"
                            accept="image/jpeg,image/png,image/webp">


                        <?php if (!empty($product["product_image"])): ?>

                            <div class="mt-3">

                                <img
                                    src="../../assets/images/products/<?php echo htmlspecialchars($product["product_image"]); ?>"
                                    width="100"
                                    height="100"
                                    style="object-fit:cover;"
                                    class="rounded">

                            </div>

                        <?php endif; ?>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select">

                            <option
                                value="Active"
                                <?php
                                echo $product["status"] === "Active"
                                    ? "selected"
                                    : "";
                                ?>>

                                Active

                            </option>


                            <option
                                value="Inactive"
                                <?php
                                echo $product["status"] === "Inactive"
                                    ? "selected"
                                    : "";
                                ?>>

                                Inactive

                            </option>

                        </select>

                    </div>


                    <div class="col-12 mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea
                            name="description"
                            class="form-control"
                            rows="4"><?php echo htmlspecialchars($product["description"] ?? ""); ?></textarea>

                    </div>

                </div>


                <div class="d-flex justify-content-end gap-2 mt-4">


                    <a
                        href="products.php"
                        class="btn btn-secondary">

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="fa-solid fa-save me-2"></i>

                        Update Product

                    </button>


                </div>


            </form>

        </div>


    </main>

</div>


<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
</script>


</body>
</html>