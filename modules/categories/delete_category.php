<?php

session_start();

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

// Database connection
require_once "../../config/database.php";

// Check category ID
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: categories.php?error=Invalid category");
    exit();
}

$category_id = (int) $_GET["id"];

// Delete category
$stmt = $conn->prepare(
    "DELETE FROM categories WHERE category_id = ?"
);

$stmt->bind_param("i", $category_id);

if ($stmt->execute()) {

    $stmt->close();
    $conn->close();

    header(
        "Location: categories.php?success=Category deleted successfully"
    );

    exit();

} else {

    $stmt->close();
    $conn->close();

    header(
        "Location: categories.php?error=Failed to delete category"
    );

    exit();
}

?>