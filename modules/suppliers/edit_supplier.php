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

    header("Location: suppliers.php?error=Invalid supplier ID");

    exit();

}

$supplier_id = (int) $_GET["id"];


// =========================================================
// UPDATE SUPPLIER
// =========================================================

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $supplier_name = trim($_POST["supplier_name"] ?? "");
    $contact_person = trim($_POST["contact_person"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $address = trim($_POST["address"] ?? "");
    $status = $_POST["status"] ?? "Active";


    // Validation

    if ($supplier_name === "") {

        header(
            "Location: edit_supplier.php?id=$supplier_id&error=Supplier name is required"
        );

        exit();

    }


    // Validate status

    if (!in_array($status, ["Active", "Inactive"])) {

        $status = "Active";

    }


    // Update query

    $sql = "UPDATE suppliers
            SET supplier_name = ?,
                contact_person = ?,
                phone = ?,
                email = ?,
                address = ?,
                status = ?
            WHERE supplier_id = ?";


    $stmt = $conn->prepare($sql);


    $stmt->bind_param(
        "ssssssi",
        $supplier_name,
        $contact_person,
        $phone,
        $email,
        $address,
        $status,
        $supplier_id
    );


    if ($stmt->execute()) {

        $stmt->close();
        $conn->close();

        header(
            "Location: suppliers.php?success=Supplier updated successfully"
        );

        exit();

    } else {

        $stmt->close();
        $conn->close();

        header(
            "Location: suppliers.php?error=Failed to update supplier"
        );

        exit();

    }

}


// =========================================================
// FETCH SUPPLIER
// =========================================================

$sql = "SELECT *
        FROM suppliers
        WHERE supplier_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $supplier_id);

$stmt->execute();

$result = $stmt->get_result();


// Supplier does not exist

if ($result->num_rows !== 1) {

    $stmt->close();
    $conn->close();

    header(
        "Location: suppliers.php?error=Supplier not found"
    );

    exit();

}


$supplier = $result->fetch_assoc();

$stmt->close();


// =========================================================
// ERROR MESSAGE
// =========================================================

$error = $_GET["error"] ?? "";

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>SmartIMS | Edit Supplier</title>


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


        <!-- Page Header -->

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>

                <h1 class="page-heading">

                    <i class="fa-solid fa-pen-to-square me-2"></i>

                    Edit Supplier

                </h1>

                <p class="text-muted mb-0">

                    Update supplier information.

                </p>

            </div>


            <a
                href="suppliers.php"
                class="btn btn-secondary">

                <i class="fa-solid fa-arrow-left me-2"></i>

                Back

            </a>

        </div>


        <!-- Error -->

        <?php if ($error !== ""): ?>

            <div class="alert alert-danger">

                <i class="fa-solid fa-circle-exclamation me-2"></i>

                <?php echo htmlspecialchars($error); ?>

            </div>

        <?php endif; ?>


        <!-- Edit Form -->

        <div class="dashboard-panel">


            <form
                method="POST"
                action="edit_supplier.php?id=<?php echo $supplier_id; ?>">


                <div class="row">


                    <!-- Supplier Name -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">

                            Supplier Name
                            <span class="text-danger">*</span>

                        </label>

                        <input
                            type="text"
                            name="supplier_name"
                            class="form-control"
                            value="<?php echo htmlspecialchars($supplier["supplier_name"]); ?>"
                            required>

                    </div>


                    <!-- Contact Person -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">

                            Contact Person

                        </label>

                        <input
                            type="text"
                            name="contact_person"
                            class="form-control"
                            value="<?php echo htmlspecialchars($supplier["contact_person"] ?? ""); ?>">

                    </div>


                    <!-- Phone -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">

                            Phone

                        </label>

                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            value="<?php echo htmlspecialchars($supplier["phone"] ?? ""); ?>">

                    </div>


                    <!-- Email -->

                    <div class="col-md-6 mb-3">

                        <label class="form-label">

                            Email

                        </label>

                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?php echo htmlspecialchars($supplier["email"] ?? ""); ?>">

                    </div>


                    <!-- Address -->

                    <div class="col-md-8 mb-3">

                        <label class="form-label">

                            Address

                        </label>

                        <textarea
                            name="address"
                            class="form-control"
                            rows="3"><?php echo htmlspecialchars($supplier["address"] ?? ""); ?></textarea>

                    </div>


                    <!-- Status -->

                    <div class="col-md-4 mb-3">

                        <label class="form-label">

                            Status

                        </label>

                        <select
                            name="status"
                            class="form-select">


                            <option
                                value="Active"
                                <?php echo ($supplier["status"] === "Active") ? "selected" : ""; ?>>

                                Active

                            </option>


                            <option
                                value="Inactive"
                                <?php echo ($supplier["status"] === "Inactive") ? "selected" : ""; ?>>

                                Inactive

                            </option>


                        </select>

                    </div>

                </div>


                <!-- Buttons -->

                <div class="mt-4">

                    <a
                        href="suppliers.php"
                        class="btn btn-secondary me-2">

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="btn btn-primary">

                        <i class="fa-solid fa-save me-2"></i>

                        Update Supplier

                    </button>

                </div>


            </form>

        </div>

    </main>

</div>


</body>

</html>