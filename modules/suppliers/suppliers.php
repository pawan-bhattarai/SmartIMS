<?php

session_start();

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

// Database connection
require_once "../../config/database.php";


// =========================================================
// FETCH SUPPLIERS
// =========================================================

$sql = "SELECT * FROM suppliers ORDER BY supplier_id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>SmartIMS | Suppliers</title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
        rel="stylesheet">


    <!-- Font Awesome -->

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">


    <!-- Main CSS -->

    <link
        rel="stylesheet"
        href="../../assets/css/style.css">


    <!-- Dashboard CSS -->

    <link
        rel="stylesheet"
        href="../../assets/css/dashboard.css">

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


        <!-- PAGE HEADER -->

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h1 class="page-heading">

                    <i class="fa-solid fa-truck me-2"></i>

                    Suppliers

                </h1>

                <p class="text-muted mb-0">

                    Manage your suppliers and supplier information.

                </p>

            </div>


            <!-- Add Supplier Button -->

            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#addSupplierModal">

                <i class="fa-solid fa-plus me-2"></i>

                Add Supplier

            </button>

        </div>



        <!-- =================================================
             SUCCESS / ERROR MESSAGES
        ================================================== -->

        <?php if (isset($_GET["success"])): ?>

            <div class="alert alert-success alert-dismissible fade show">

                <i class="fa-solid fa-circle-check me-2"></i>

                <?php echo htmlspecialchars($_GET["success"]); ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
                </button>

            </div>

        <?php endif; ?>


        <?php if (isset($_GET["error"])): ?>

            <div class="alert alert-danger alert-dismissible fade show">

                <i class="fa-solid fa-circle-exclamation me-2"></i>

                <?php echo htmlspecialchars($_GET["error"]); ?>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert">
                </button>

            </div>

        <?php endif; ?>



        <!-- =================================================
             SUPPLIER TABLE
        ================================================== -->

        <div class="dashboard-panel">


            <div class="table-responsive">

                <table class="table table-hover align-middle">


                    <!-- TABLE HEADER -->

                    <thead>

                        <tr>

                            <th>#</th>

                            <th>Supplier</th>

                            <th>Contact Person</th>

                            <th>Phone</th>

                            <th>Email</th>

                            <th>Address</th>

                            <th>Status</th>

                            <th class="text-center">
                                Actions
                            </th>

                        </tr>

                    </thead>


                    <!-- TABLE BODY -->

                    <tbody>

                    <?php if ($result && $result->num_rows > 0): ?>

                        <?php $count = 1; ?>


                        <?php while ($supplier = $result->fetch_assoc()): ?>

                            <tr>


                                <!-- Number -->

                                <td>

                                    <?php echo $count++; ?>

                                </td>


                                <!-- Supplier Name -->

                                <td>

                                    <strong>

                                        <?php
                                        echo htmlspecialchars(
                                            $supplier["supplier_name"]
                                        );
                                        ?>

                                    </strong>

                                </td>


                                <!-- Contact Person -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $supplier["contact_person"] ?? "-"
                                    );
                                    ?>

                                </td>


                                <!-- Phone -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $supplier["phone"] ?? "-"
                                    );
                                    ?>

                                </td>


                                <!-- Email -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $supplier["email"] ?? "-"
                                    );
                                    ?>

                                </td>


                                <!-- Address -->

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $supplier["address"] ?? "-"
                                    );
                                    ?>

                                </td>


                                <!-- Status -->

                                <td>

                                    <?php if ($supplier["status"] === "Active"): ?>

                                        <span class="badge bg-success">

                                            Active

                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-secondary">

                                            Inactive

                                        </span>

                                    <?php endif; ?>

                                </td>


                                <!-- Actions -->

                                <td class="text-center">

                                    <!-- Edit -->

                                    <a
                                        href="edit_supplier.php?id=<?php echo $supplier['supplier_id']; ?>"
                                        class="btn btn-sm btn-outline-primary me-1"
                                        title="Edit">

                                        <i class="fa-solid fa-pen"></i>

                                    </a>


                                    <!-- Delete -->

                                    <a
                                        href="delete_supplier.php?id=<?php echo $supplier['supplier_id']; ?>"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete"
                                        onclick="return confirm('Are you sure you want to delete this supplier?');">

                                        <i class="fa-solid fa-trash"></i>

                                    </a>

                                </td>

                            </tr>

                        <?php endwhile; ?>


                    <?php else: ?>

                        <tr>

                            <td
                                colspan="8"
                                class="text-center py-5">

                                <i
                                    class="fa-solid fa-truck fs-1 text-muted mb-3">
                                </i>

                                <p class="text-muted mb-0">

                                    No suppliers found.

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
     ADD SUPPLIER MODAL
========================================================= -->

<div
    class="modal fade"
    id="addSupplierModal"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <!-- Modal Header -->

            <div class="modal-header">

                <h5 class="modal-title">

                    <i class="fa-solid fa-truck me-2"></i>

                    Add New Supplier

                </h5>


                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>


            <!-- Form -->

            <form
                action="add_supplier.php"
                method="POST">


                <div class="modal-body">


                    <!-- Supplier Name -->

                    <div class="mb-3">

                        <label class="form-label">

                            Supplier Name

                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="text"
                            name="supplier_name"
                            class="form-control"
                            placeholder="Enter supplier name"
                            required>

                    </div>


                    <!-- Contact Person -->

                    <div class="mb-3">

                        <label class="form-label">

                            Contact Person

                        </label>

                        <input
                            type="text"
                            name="contact_person"
                            class="form-control"
                            placeholder="Enter contact person">

                    </div>


                    <!-- Phone -->

                    <div class="mb-3">

                        <label class="form-label">

                            Phone

                        </label>

                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            placeholder="Enter phone number">

                    </div>


                    <!-- Email -->

                    <div class="mb-3">

                        <label class="form-label">

                            Email

                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            placeholder="Enter email address">

                    </div>


                    <!-- Address -->

                    <div class="mb-3">

                        <label class="form-label">

                            Address

                        </label>

                        <textarea
                            name="address"
                            class="form-control"
                            rows="3"
                            placeholder="Enter supplier address"></textarea>

                    </div>


                    <!-- Status -->

                    <div class="mb-3">

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

                </div>


                <!-- Modal Footer -->

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

                        Save Supplier

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js">
</script>


</body>

</html>