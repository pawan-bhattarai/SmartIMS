<?php

session_start();


// =========================================================
// CHECK LOGIN
// =========================================================

if (!isset($_SESSION["user_id"])) {

    header("Location: ../../auth/login.php");

    exit();
}


require_once "../../config/database.php";


// =========================================================
// GET PURCHASE ID
// =========================================================

$purchase_id =
    (int)($_GET["id"] ?? 0);


if ($purchase_id <= 0) {

    header(
        "Location: purchases.php?error=Invalid purchase ID"
    );

    exit();
}


// =========================================================
// START TRANSACTION
// =========================================================

$conn->begin_transaction();


try {


    // -----------------------------------------------------
    // GET PURCHASE DETAILS
    // -----------------------------------------------------

    $detail_stmt = $conn->prepare("
        SELECT
            product_id,
            quantity
        FROM purchase_details
        WHERE purchase_id = ?
    ");


    $detail_stmt->bind_param(
        "i",
        $purchase_id
    );


    $detail_stmt->execute();


    $details =
        $detail_stmt->get_result();


    $items = [];


    while (
        $item = $details->fetch_assoc()
    ) {

        $items[] = $item;

    }


    $detail_stmt->close();


    // -----------------------------------------------------
    // CHECK PURCHASE EXISTS
    // -----------------------------------------------------

    if (empty($items)) {

        // It may still be a valid purchase with
        // no details, so check parent record.

        $check_stmt = $conn->prepare("
            SELECT purchase_id
            FROM purchases
            WHERE purchase_id = ?
        ");


        $check_stmt->bind_param(
            "i",
            $purchase_id
        );


        $check_stmt->execute();


        $check_result =
            $check_stmt->get_result();


        if (
            $check_result->num_rows === 0
        ) {

            throw new Exception(
                "Purchase not found."
            );

        }


        $check_stmt->close();

    }


    // -----------------------------------------------------
    // DECREASE STOCK
    // -----------------------------------------------------

    $stock_stmt = $conn->prepare("
        UPDATE products
        SET stock_quantity =
            stock_quantity - ?
        WHERE product_id = ?
        AND stock_quantity >= ?
    ");


    foreach ($items as $item) {

        $quantity =
            (int)$item["quantity"];

        $product_id =
            (int)$item["product_id"];


        $stock_stmt->bind_param(
            "iii",
            $quantity,
            $product_id,
            $quantity
        );


        if (!$stock_stmt->execute()) {

            throw new Exception(
                "Failed to update product stock."
            );

        }


        if (
            $stock_stmt->affected_rows === 0
        ) {

            throw new Exception(
                "Purchase cannot be deleted because the current stock is lower than the purchased quantity."
            );

        }

    }


    $stock_stmt->close();


    // -----------------------------------------------------
    // DELETE PURCHASE
    // -----------------------------------------------------
    //
    // purchase_details has:
    //
    // ON DELETE CASCADE
    //
    // Therefore deleting the purchase automatically
    // deletes its details.
    // -----------------------------------------------------

    $delete_stmt = $conn->prepare("
        DELETE FROM purchases
        WHERE purchase_id = ?
    ");


    $delete_stmt->bind_param(
        "i",
        $purchase_id
    );


    if (!$delete_stmt->execute()) {

        throw new Exception(
            "Failed to delete purchase."
        );

    }


    if (
        $delete_stmt->affected_rows === 0
    ) {

        throw new Exception(
            "Purchase not found."
        );

    }


    $delete_stmt->close();


    // -----------------------------------------------------
    // COMMIT
    // -----------------------------------------------------

    $conn->commit();


    header(
        "Location: purchases.php?success=Purchase deleted successfully and stock adjusted"
    );

    exit();


} catch (Exception $e) {


    // -----------------------------------------------------
    // ROLLBACK
    // -----------------------------------------------------

    $conn->rollback();


    header(
        "Location: purchases.php?error="
        . urlencode($e->getMessage())
    );

    exit();

}

?>