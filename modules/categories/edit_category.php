<?php

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

require_once "../../config/database.php";

// Make sure category ID exists
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: categories.php?error=Invalid category");
    exit();
}

$category_id = (int) $_GET["id"];

// =========================================================
// UPDATE CATEGORY
// =========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $category_name = trim($_POST["category_name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $status = $_POST["status"] ?? "Active";

    if ($category_name === "") {
        header(
            "Location: edit_category.php?id=$category_id&error=Category name is required"
        );
        exit();
    }

    // Check duplicate category name
    $check = $conn->prepare(
        "SELECT category_id
         FROM categories
         WHERE category_name = ?
         AND category_id != ?"
    );

    $check->bind_param(
        "si",
        $category_name,
        $category_id
    );

    $check->execute();

    $result = $check->get_result();

    if ($result->num_rows > 0) {

        $check->close();

        header(
            "Location: edit_category.php?id=$category_id&error=Category already exists"
        );

        exit();
    }

    $check->close();


    // Update category
    $stmt = $conn->prepare(
        "UPDATE categories
         SET category_name = ?,
             description = ?,
             status = ?
         WHERE category_id = ?"
    );

    $stmt->bind_param(
        "sssi",
        $category_name,
        $description,
        $status,
        $category_id
    );


    if ($stmt->execute()) {

        $stmt->close();
        $conn->close();

        header(
            "Location: categories.php?success=Category updated successfully"
        );

        exit();

    } else {

        $error = "Failed to update category.";

        $stmt->close();
    }
}


// =========================================================
// GET CATEGORY DATA
// =========================================================

$stmt = $conn->prepare(
    "SELECT *
     FROM categories
     WHERE category_id = ?"
);

$stmt->bind_param(
    "i",
    $category_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();
    $conn->close();

    header(
        "Location: categories.php?error=Category not found"
    );

    exit();
}

$category = $result->fetch_assoc();

$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>SmartIMS | Edit Category</title>

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

                    Edit Category

                </h1>

                <p class="text-muted mb-0">

                    Update category information

                </p>

            </div>

            <a
                href="categories.php"
                class="btn btn-secondary">

                <i class="fa-solid fa-arrow-left me-2"></i>

                Back

            </a>

        </div>


        <div class="dashboard-panel">

            <?php if (isset($_GET["error"])): ?>

                <div class="alert alert-danger">

                    <?php
                    echo htmlspecialchars($_GET["error"]);
                    ?>

                </div>

            <?php endif; ?>


            <form
                action="edit_category.php?id=<?php echo $category_id; ?>"
                method="POST">


                <!-- Category Name -->

                <div class="mb-3">

                    <label class="form-label">

                        Category Name

                    </label>

                    <input
                        type="text"
                        name="category_name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($category["category_name"]); ?>"
                        required>

                </div>


                <!-- Description -->

                <div class="mb-3">

                    <label class="form-label">

                        Description

                    </label>

                    <textarea
                        name="description"
                        class="form-control"
                        rows="4"><?php echo htmlspecialchars($category["description"] ?? ""); ?></textarea>

                </div>


                <!-- Status -->

                <div class="mb-4">

                    <label class="form-label">

                        Status

                    </label>

                    <select
                        name="status"
                        class="form-select">

                        <option
                            value="Active"
                            <?php
                            echo $category["status"] === "Active"
                                ? "selected"
                                : "";
                            ?>>

                            Active

                        </option>

                        <option
                            value="Inactive"
                            <?php
                            echo $category["status"] === "Inactive"
                                ? "selected"
                                : "";
                            ?>>

                            Inactive

                        </option>

                    </select>

                </div>


                <!-- Buttons -->

                <div class="d-flex gap-2">

                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="fa-solid fa-save me-2"></i>

                        Update Category

                    </button>


                    <a
                        href="categories.php"
                        class="btn btn-secondary">

                        Cancel

                    </a>

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