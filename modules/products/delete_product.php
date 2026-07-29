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
// DATABASE
// =========================================================

require_once "../../config/database.php";


// =========================================================
// GET PRODUCT ID
// =========================================================

$id = (int)($_GET["id"] ?? 0);


if ($id <= 0) {

    header(
        "Location: products.php?error=Invalid product ID"
    );

    exit();
}


// =========================================================
// GET PRODUCT IMAGE
// =========================================================

$stmt = $conn->prepare(
    "SELECT product_image
     FROM products
     WHERE product_id = ?"
);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

$product = $result->fetch_assoc();

$stmt->close();


if (!$product) {

    header(
        "Location: products.php?error=Product not found"
    );

    exit();
}


// =========================================================
// CHECK WHETHER PRODUCT IS USED IN PURCHASE DETAILS
// =========================================================

$stmt = $conn->prepare(
    "SELECT purchase_detail_id
     FROM purchase_details
     WHERE product_id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows > 0) {

    header(
        "Location: products.php?error=This product cannot be deleted because it is already used in purchase records"
    );

    exit();
}

$stmt->close();


// =========================================================
// CHECK WHETHER PRODUCT IS USED IN SALE DETAILS
// =========================================================

$stmt = $conn->prepare(
    "SELECT sale_detail_id
     FROM sale_details
     WHERE product_id = ?
     LIMIT 1"
);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows > 0) {

    header(
        "Location: products.php?error=This product cannot be deleted because it is already used in sales records"
    );

    exit();
}

$stmt->close();


// =========================================================
// DELETE PRODUCT
// =========================================================

$stmt = $conn->prepare(
    "DELETE FROM products
     WHERE product_id = ?"
);

$stmt->bind_param("i", $id);


if ($stmt->execute()) {


    // -----------------------------------------------------
    // DELETE PRODUCT IMAGE
    // -----------------------------------------------------

    if (!empty($product["product_image"])) {

        $image_path =
            "../../assets/images/products/" .
            $product["product_image"];


        if (file_exists($image_path)) {

            unlink($image_path);

        }

    }


    $stmt->close();


    header(
        "Location: products.php?success=Product deleted successfully"
    );

    exit();


} else {

    $stmt->close();


    header(
        "Location: products.php?error=Failed to delete product"
    );

    exit();

}

?>