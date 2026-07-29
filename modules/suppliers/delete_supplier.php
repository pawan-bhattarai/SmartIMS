<?php

session_start();

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

// Database connection
require_once "../../config/database.php";


// =========================================================
// CHECK SUPPLIER ID
// =========================================================

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {

    header(
        "Location: suppliers.php?error=Invalid supplier ID"
    );

    exit();

}


$supplier_id = (int) $_GET["id"];


// =========================================================
// DELETE SUPPLIER
// =========================================================

$sql = "DELETE FROM suppliers
        WHERE supplier_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "i",
    $supplier_id
);


if ($stmt->execute()) {

    $stmt->close();
    $conn->close();

    header(
        "Location: suppliers.php?success=Supplier deleted successfully"
    );

    exit();

} else {

    $stmt->close();
    $conn->close();

    header(
        "Location: suppliers.php?error=Failed to delete supplier"
    );

    exit();

}

?>