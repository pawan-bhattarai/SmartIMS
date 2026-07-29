<?php

session_start();

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

// Database connection
require_once "../../config/database.php";

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: suppliers.php");
    exit();
}


// =========================================================
// GET FORM DATA
// =========================================================

$supplier_name = trim($_POST["supplier_name"] ?? "");
$contact_person = trim($_POST["contact_person"] ?? "");
$phone = trim($_POST["phone"] ?? "");
$email = trim($_POST["email"] ?? "");
$address = trim($_POST["address"] ?? "");
$status = $_POST["status"] ?? "Active";


// =========================================================
// VALIDATION
// =========================================================

if ($supplier_name === "") {

    header(
        "Location: suppliers.php?error=Supplier name is required"
    );

    exit();
}


// Make sure status is valid

if (!in_array($status, ["Active", "Inactive"])) {

    $status = "Active";

}


// =========================================================
// INSERT SUPPLIER
// =========================================================

$sql = "INSERT INTO suppliers
        (supplier_name, contact_person, phone, email, address, status)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ssssss",
    $supplier_name,
    $contact_person,
    $phone,
    $email,
    $address,
    $status
);


// =========================================================
// EXECUTE
// =========================================================

if ($stmt->execute()) {

    $stmt->close();
    $conn->close();

    header(
        "Location: suppliers.php?success=Supplier added successfully"
    );

    exit();

} else {

    $stmt->close();
    $conn->close();

    header(
        "Location: suppliers.php?error=Failed to add supplier"
    );

    exit();
}

?>