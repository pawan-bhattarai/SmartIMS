<?php

session_start();

// Check login
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

// Database connection
require_once "../../config/database.php";

// Only allow POST request
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: categories.php");
    exit();
}

// Get form data
$category_name = trim($_POST["category_name"]);
$description = trim($_POST["description"]);

// Validate category name
if ($category_name === "") {
    header("Location: categories.php?error=Category name is required");
    exit();
}

// Check if category already exists
$check = $conn->prepare(
    "SELECT category_id FROM categories WHERE category_name = ?"
);

$check->bind_param("s", $category_name);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {

    $check->close();

    header(
        "Location: categories.php?error=Category already exists"
    );

    exit();
}

$check->close();

// Insert category
$stmt = $conn->prepare(
    "INSERT INTO categories (category_name, description)
     VALUES (?, ?)"
);

$stmt->bind_param(
    "ss",
    $category_name,
    $description
);

if ($stmt->execute()) {

    header(
        "Location: categories.php?success=Category added successfully"
    );

} else {

    header(
        "Location: categories.php?error=Failed to add category"
    );

}

$stmt->close();
$conn->close();

exit();

?>