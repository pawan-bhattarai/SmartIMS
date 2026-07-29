<?php

// =========================================================
// SmartIMS - Categories Management
// =========================================================

session_start();

// Protect page
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

// Database connection
require_once "../../config/database.php";

// Get all categories
$query = "SELECT * FROM categories ORDER BY category_id DESC";

$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>SmartIMS | Categories</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <!-- Font Awesome -->
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">

    <!-- Dashboard CSS -->
    <link rel="stylesheet" href="../../assets/css/dashboard.css">

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


    <!-- Navbar -->

    <?php require_once "../../includes/navbar.php"; ?>


    <!-- =====================================================
         PAGE CONTENT
    ====================================================== -->

    <main class="page-content">


        <!-- Page Heading -->

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h1 class="page-heading">

                    <i class="fa-solid fa-layer-group me-2"></i>

                    Categories

                </h1>

                <p class="text-muted mb-0">

                    Manage your product categories

                </p>

            </div>


            <!-- Add Category Button -->

            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#addCategoryModal">

                <i class="fa-solid fa-plus me-2"></i>

                Add Category

            </button>

        </div>


        <!-- =================================================
             CATEGORY TABLE
        ================================================== -->

        <div class="dashboard-panel">


            <!-- Table Header -->

            <div class="panel-header">

                <div>

                    <h5 class="mb-1">

                        Category List

                    </h5>

                    <small class="text-muted">

                        All categories in SmartIMS

                    </small>

                </div>


                <!-- Search -->

                <div style="max-width: 280px;">

                    <div class="input-group">

                        <span class="input-group-text">

                            <i class="fa-solid fa-search"></i>

                        </span>

                        <input
                            type="text"
                            id="categorySearch"
                            class="form-control"
                            placeholder="Search category...">

                    </div>

                </div>

            </div>


            <!-- Table -->

            <div class="table-responsive">

                <table
                    class="table align-middle mb-0"
                    id="categoryTable">


                    <thead>

                        <tr>

                            <th>
                                #
                            </th>

                            <th>
                                Category Name
                            </th>

                            <th>
                                Description
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Created At
                            </th>

                            <th class="text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if ($result->num_rows > 0): ?>


                        <?php while ($category = $result->fetch_assoc()): ?>


                            <tr>


                                <!-- ID -->

                                <td>

                                    <?php
                                    echo $category["category_id"];
                                    ?>

                                </td>


                                <!-- Category Name -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $category["category_name"]
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <!-- Description -->

                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $category["description"] ?: "No description"
                                    );

                                    ?>

                                </td>


                                <!-- Status -->

                                <td>


                                    <?php if ($category["status"] === "Active"): ?>

                                        <span class="badge bg-success">

                                            Active

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">

                                            Inactive

                                        </span>

                                    <?php endif; ?>


                                </td>


                                <!-- Created Date -->

                                <td>

                                    <?php

                                    echo date(
                                        "d M Y",
                                        strtotime($category["created_at"])
                                    );

                                    ?>

                                </td>


                                <!-- Actions -->

                                <td class="text-center">


                                    <a
                                   href="edit_category.php?id=<?php echo $category['category_id']; ?>"
                                 class="btn btn-sm btn-outline-primary me-1"
                                  title="Edit">

                                    <i class="fa-solid fa-pen"></i>
                                                                        
                                    </a>


                                                                        <a
                                        href="delete_category.php?id=<?php echo $category['category_id']; ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete"
                                        onclick="return confirm('Are you sure you want to delete this category?');">

                                        <i class="fa-solid fa-trash"></i>

                                    </a>


                                </td>


                            </tr>


                        <?php endwhile; ?>


                    <?php else: ?>


                        <tr>

                            <td
                                colspan="6"
                                class="text-center py-5">

                                <i
                                    class="fa-solid fa-folder-open fa-2x text-muted mb-3">
                                </i>

                                <p class="text-muted mb-0">

                                    No categories found.

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
     ADD CATEGORY MODAL
========================================================= -->

<div
    class="modal fade"
    id="addCategoryModal"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <!-- Header -->

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="fa-solid fa-plus me-2"></i>

                    Add New Category

                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>


            <!-- FORM START -->

            <form action="add_category.php" method="POST">

                <div class="modal-body">

                    <!-- Category Name -->

                    <div class="mb-3">

                        <label class="form-label">
                            Category Name
                        </label>

                        <input
                            type="text"
                            name="category_name"
                            class="form-control"
                            placeholder="Enter category name"
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
                            rows="3"
                            placeholder="Enter category description"></textarea>

                    </div>

                </div>


                <!-- Footer -->

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Cancel

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="fa-solid fa-save me-2"></i>

                        Save Category

                    </button>

                </div>

            </form>

            <!-- FORM END -->

        </div>

    </div>

</div>

<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
</script>


<!-- =========================================================
     SEARCH
========================================================= -->

<script>

document
    .getElementById("categorySearch")
    .addEventListener("keyup", function () {

        let searchValue = this.value.toLowerCase();

        let rows = document.querySelectorAll(
            "#categoryTable tbody tr"
        );

        rows.forEach(function (row) {

            let rowText = row.textContent.toLowerCase();

            if (rowText.includes(searchValue)) {

                row.style.display = "";

            } else {

                row.style.display = "none";

            }

        });

    });

</script>


</body>

</html>