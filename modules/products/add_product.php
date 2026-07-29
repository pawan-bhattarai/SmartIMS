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
// PROCESS FORM
// =========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // -----------------------------------------------------
    // GET FORM DATA
    // -----------------------------------------------------

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

    $created_by = $_SESSION["user_id"];


    // -----------------------------------------------------
    // BASIC VALIDATION
    // -----------------------------------------------------

    if ($product_name === "") {
        header("Location: add_product.php?error=Product name is required");
        exit();
    }


    if ($category_id <= 0) {
        header("Location: add_product.php?error=Please select a category");
        exit();
    }


    if ($supplier_id <= 0) {
        header("Location: add_product.php?error=Please select a supplier");
        exit();
    }


    if ($purchase_price < 0 || $selling_price < 0) {
        header("Location: add_product.php?error=Price cannot be negative");
        exit();
    }


    if ($stock_quantity < 0 || $minimum_stock < 0) {
        header("Location: add_product.php?error=Stock quantity cannot be negative");
        exit();
    }


    // -----------------------------------------------------
    // CHECK SKU
    // -----------------------------------------------------

    if ($sku !== "") {

        $check_sku = $conn->prepare(
            "SELECT product_id FROM products WHERE sku = ?"
        );

        $check_sku->bind_param("s", $sku);

        $check_sku->execute();

        $sku_result = $check_sku->get_result();

        if ($sku_result->num_rows > 0) {

            header(
                "Location: add_product.php?error=SKU already exists"
            );

            exit();
        }

        $check_sku->close();
    }


    // -----------------------------------------------------
    // CHECK BARCODE
    // -----------------------------------------------------

    if ($barcode !== "") {

        $check_barcode = $conn->prepare(
            "SELECT product_id FROM products WHERE barcode = ?"
        );

        $check_barcode->bind_param("s", $barcode);

        $check_barcode->execute();

        $barcode_result = $check_barcode->get_result();

        if ($barcode_result->num_rows > 0) {

            header(
                "Location: add_product.php?error=Barcode already exists"
            );

            exit();
        }

        $check_barcode->close();
    }


    // -----------------------------------------------------
    // PRODUCT IMAGE UPLOAD
    // -----------------------------------------------------

    $product_image = null;

    if (
        isset($_FILES["product_image"]) &&
        $_FILES["product_image"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES["product_image"]["error"] !== UPLOAD_ERR_OK) {

            header(
                "Location: add_product.php?error=Image upload failed"
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
                "Location: add_product.php?error=Only JPG, PNG and WEBP images are allowed"
            );

            exit();
        }


        if ($_FILES["product_image"]["size"] > 2 * 1024 * 1024) {

            header(
                "Location: add_product.php?error=Image must be less than 2MB"
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
                "Location: add_product.php?error=Could not save product image"
            );

            exit();
        }
    }


    // -----------------------------------------------------
    // INSERT PRODUCT
    // -----------------------------------------------------

    $sql = "INSERT INTO products (

                category_id,
                supplier_id,
                product_name,
                brand,
                sku,
                barcode,
                purchase_price,
                selling_price,
                expiry_date,
                stock_quantity,
                minimum_stock,
                unit,
                product_image,
                description,
                status,
                created_by

            ) VALUES (

                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?

            )";


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
        $created_by
    );


    if ($stmt->execute()) {

        $stmt->close();

        header(
            "Location: products.php?success=Product added successfully"
        );

        exit();

    } else {

        if ($product_image !== null) {

            $image_path =
                "../../assets/images/products/" .
                $product_image;

            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }


        $stmt->close();

        header(
            "Location: add_product.php?error=Failed to add product"
        );

        exit();
    }
}


// =========================================================
// FETCH CATEGORIES
// =========================================================

$category_sql = "
    SELECT category_id, category_name
    FROM categories
    WHERE status = 'Active'
    ORDER BY category_name ASC
";

$category_result = $conn->query($category_sql);


// =========================================================
// FETCH SUPPLIERS
// =========================================================

$supplier_sql = "
    SELECT supplier_id, supplier_name
    FROM suppliers
    WHERE status = 'Active'
    ORDER BY supplier_name ASC
";

$supplier_result = $conn->query($supplier_sql);


$error = $_GET["error"] ?? "";

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>SmartIMS | Add Product</title>


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

                    <i class="fa-solid fa-box-open me-2"></i>

                    Add Product

                </h1>

                <p class="text-muted mb-0">
                    Add a new product to your inventory.
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

                <i class="fa-solid fa-circle-exclamation me-2"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <div class="dashboard-panel">


            <form
                action="add_product.php"
                method="POST"
                enctype="multipart/form-data">


                <!-- BASIC INFORMATION -->

                <div class="mb-4">

                    <h5 class="fw-bold">

                        <i class="fa-solid fa-circle-info me-2"></i>

                        Basic Information

                    </h5>

                    <hr>

                </div>


                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Product Name <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="product_name"
                            class="form-control"
                            required>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Brand
                        </label>

                        <input
                            type="text"
                            name="brand"
                            class="form-control">

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            SKU
                        </label>

                        <input
                            type="text"
                            name="sku"
                            class="form-control">

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Barcode
                        </label>

                        <input
                            type="text"
                            name="barcode"
                            class="form-control">

                    </div>

                </div>


                <!-- CLASSIFICATION -->

                <div class="mb-4 mt-4">

                    <h5 class="fw-bold">

                        <i class="fa-solid fa-layer-group me-2"></i>

                        Classification

                    </h5>

                    <hr>

                </div>


                <div class="row">


                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Category <span class="text-danger">*</span>
                        </label>

                        <select
                            name="category_id"
                            class="form-select"
                            required>

                            <option value="">
                                Select Category
                            </option>

                            <?php while (
                                $category = $category_result->fetch_assoc()
                            ): ?>

                                <option
                                    value="<?php echo $category["category_id"]; ?>">

                                    <?php echo htmlspecialchars(
                                        $category["category_name"]
                                    ); ?>

                                </option>

                            <?php endwhile; ?>

                        </select>

                    </div>


                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Supplier <span class="text-danger">*</span>
                        </label>

                        <select
                            name="supplier_id"
                            class="form-select"
                            required>

                            <option value="">
                                Select Supplier
                            </option>

                            <?php while (
                                $supplier = $supplier_result->fetch_assoc()
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
                            Unit
                        </label>

                        <select
                            name="unit"
                            class="form-select">

                            <option value="pcs">Pieces</option>
                            <option value="kg">Kilogram</option>
                            <option value="litre">Litre</option>
                            <option value="packet">Packet</option>
                            <option value="box">Box</option>
                            <option value="bottle">Bottle</option>

                        </select>

                    </div>

                </div>


                <!-- PRICING -->

                <div class="mb-4 mt-4">

                    <h5 class="fw-bold">

                        <i class="fa-solid fa-money-bill-wave me-2"></i>

                        Pricing

                    </h5>

                    <hr>

                </div>


                <div class="row">


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Purchase Price <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                Rs.
                            </span>

                            <input
                                type="number"
                                name="purchase_price"
                                class="form-control"
                                step="0.01"
                                min="0"
                                required>

                        </div>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Selling Price <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">

                            <span class="input-group-text">
                                Rs.
                            </span>

                            <input
                                type="number"
                                name="selling_price"
                                class="form-control"
                                step="0.01"
                                min="0"
                                required>

                        </div>

                    </div>

                </div>


                <!-- INVENTORY -->

                <div class="mb-4 mt-4">

                    <h5 class="fw-bold">

                        <i class="fa-solid fa-warehouse me-2"></i>

                        Inventory

                    </h5>

                    <hr>

                </div>


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
                            value="0">

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
                            value="10">

                    </div>


                    <div class="col-md-4 mb-3">

                        <label class="form-label">
                            Expiry Date
                        </label>

                        <input
                            type="date"
                            name="expiry_date"
                            class="form-control">

                    </div>

                </div>


                <!-- OTHER -->

                <div class="mb-4 mt-4">

                    <h5 class="fw-bold">

                        <i class="fa-solid fa-file-lines me-2"></i>

                        Other Information

                    </h5>

                    <hr>

                </div>


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

                        <small class="text-muted">
                            Maximum size: 2MB
                        </small>

                    </div>


                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select
                            name="status"
                            class="form-select">

                            <option value="Active">
                                Active
                            </option>

                            <option value="Inactive">
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
                            rows="4"></textarea>

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

                        Save Product

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