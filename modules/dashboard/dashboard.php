<?php

// =========================================================
// SmartIMS Dashboard
// =========================================================

// Start session
session_start();

// Protect dashboard from unauthorised users
if (!isset($_SESSION["user_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

// Database connection
require_once "../../config/database.php";


// =========================================================
// DASHBOARD STATISTICS
// =========================================================

// Total products
$productQuery = $conn->query(
    "SELECT COUNT(*) AS total FROM products"
);

$totalProducts = $productQuery->fetch_assoc()["total"];


// Total categories
$categoryQuery = $conn->query(
    "SELECT COUNT(*) AS total FROM categories"
);

$totalCategories = $categoryQuery->fetch_assoc()["total"];


// Total suppliers
$supplierQuery = $conn->query(
    "SELECT COUNT(*) AS total FROM suppliers"
);

$totalSuppliers = $supplierQuery->fetch_assoc()["total"];


// Low stock products
$lowStockQuery = $conn->query(
    "SELECT COUNT(*) AS total
     FROM products
     WHERE stock_quantity <= minimum_stock
     AND status = 'Active'"
);

$lowStockProducts = $lowStockQuery->fetch_assoc()["total"];


// Today's sales
$todaySalesQuery = $conn->query(
    "SELECT COALESCE(SUM(grand_total), 0) AS total
     FROM sales
     WHERE sale_date = CURDATE()"
);

$todaySales = $todaySalesQuery->fetch_assoc()["total"];


// Today's purchases
$todayPurchaseQuery = $conn->query(
    "SELECT COALESCE(SUM(total_amount), 0) AS total
     FROM purchases
     WHERE purchase_date = CURDATE()"
);

$todayPurchases = $todayPurchaseQuery->fetch_assoc()["total"];


// =========================================================
// COMMON HEADER
// =========================================================

require_once "../../includes/header.php";


// Sidebar
require_once '../../includes/slidebar.php';

?>

<!-- =========================================================
     MAIN CONTENT
========================================================= -->

<div class="main-content">

    <?php
    // Top Navbar
    require_once "../../includes/navbar.php";
    ?>


    <!-- Page Content -->
    <main class="page-content">


        <!-- =================================================
             PAGE TITLE
        ================================================== -->

        <div class="page-title">

            <h1>
                Dashboard
            </h1>

            <p>
                Welcome back,
                <strong>
                    <?php echo htmlspecialchars($_SESSION["full_name"]); ?>
                </strong>
                👋
            </p>

        </div>


        <!-- =================================================
             STATISTICS CARDS
        ================================================== -->

        <div class="row g-4">


            <!-- Total Products -->

            <div class="col-xl-2 col-md-4 col-sm-6">

                <div class="stat-card">

                    <div class="stat-icon icon-blue">

                        <i class="fa-solid fa-box"></i>

                    </div>

                    <h6>
                        Total Products
                    </h6>

                    <h3>
                        <?php echo $totalProducts; ?>
                    </h3>

                    <span class="stat-change">
                        Active inventory
                    </span>

                </div>

            </div>


            <!-- Categories -->

            <div class="col-xl-2 col-md-4 col-sm-6">

                <div class="stat-card">

                    <div class="stat-icon icon-purple">

                        <i class="fa-solid fa-layer-group"></i>

                    </div>

                    <h6>
                        Categories
                    </h6>

                    <h3>
                        <?php echo $totalCategories; ?>
                    </h3>

                    <span class="stat-change">
                        Product categories
                    </span>

                </div>

            </div>


            <!-- Suppliers -->

            <div class="col-xl-2 col-md-4 col-sm-6">

                <div class="stat-card">

                    <div class="stat-icon icon-green">

                        <i class="fa-solid fa-truck"></i>

                    </div>

                    <h6>
                        Suppliers
                    </h6>

                    <h3>
                        <?php echo $totalSuppliers; ?>
                    </h3>

                    <span class="stat-change">
                        Registered suppliers
                    </span>

                </div>

            </div>


            <!-- Today's Sales -->

            <div class="col-xl-2 col-md-4 col-sm-6">

                <div class="stat-card">

                    <div class="stat-icon icon-blue">

                        <i class="fa-solid fa-cash-register"></i>

                    </div>

                    <h6>
                        Today's Sales
                    </h6>

                    <h3>
                        NPR <?php echo number_format($todaySales, 2); ?>
                    </h3>

                    <span class="stat-change stat-up">
                        <i class="fa-solid fa-arrow-up"></i>
                        Today's revenue
                    </span>

                </div>

            </div>


            <!-- Today's Purchases -->

            <div class="col-xl-2 col-md-4 col-sm-6">

                <div class="stat-card">

                    <div class="stat-icon icon-orange">

                        <i class="fa-solid fa-cart-shopping"></i>

                    </div>

                    <h6>
                        Today's Purchases
                    </h6>

                    <h3>
                        NPR <?php echo number_format($todayPurchases, 2); ?>
                    </h3>

                    <span class="stat-change">
                        Today's purchases
                    </span>

                </div>

            </div>


            <!-- Low Stock -->

            <div class="col-xl-2 col-md-4 col-sm-6">

                <div class="stat-card">

                    <div class="stat-icon icon-red">

                        <i class="fa-solid fa-triangle-exclamation"></i>

                    </div>

                    <h6>
                        Low Stock
                    </h6>

                    <h3>
                        <?php echo $lowStockProducts; ?>
                    </h3>

                    <span class="stat-change stat-down">
                        Needs attention
                    </span>

                </div>

            </div>

        </div>


        <!-- =================================================
             CHART + LOW STOCK
        ================================================== -->

        <div class="row g-4 mt-1">


            <!-- Daily Sales Chart -->

            <div class="col-lg-8">

                <div class="dashboard-panel">

                    <div class="panel-header">

                        <div>

                            <h5>
                                Daily Sales
                            </h5>

                            <small class="text-muted">
                                Sales performance for the last 7 days
                            </small>

                        </div>

                        <i class="fa-solid fa-chart-line text-primary"></i>

                    </div>


                    <!-- Chart -->
                    <div style="height: 300px;">

                        <canvas id="salesChart"></canvas>

                    </div>

                </div>

            </div>


            <!-- Low Stock Products -->

            <div class="col-lg-4">

                <div class="dashboard-panel">

                    <div class="panel-header">

                        <h5>
                            Low Stock Products
                        </h5>

                        <a href="../products/products.php">
                            View All
                        </a>

                    </div>


                    <?php

                    $lowStockList = $conn->query(
                        "SELECT product_name,
                                stock_quantity,
                                minimum_stock
                         FROM products
                         WHERE stock_quantity <= minimum_stock
                         AND status = 'Active'
                         ORDER BY stock_quantity ASC
                         LIMIT 5"
                    );

                    ?>


                    <div class="table-responsive">

                        <table class="dashboard-table">

                            <thead>

                                <tr>

                                    <th>
                                        Product
                                    </th>

                                    <th>
                                        Stock
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if ($lowStockList->num_rows > 0): ?>

                                    <?php while ($product = $lowStockList->fetch_assoc()): ?>

                                        <tr>

                                            <td>
                                                <?php
                                                echo htmlspecialchars(
                                                    $product["product_name"]
                                                );
                                                ?>
                                            </td>

                                            <td class="low-stock">

                                                <?php
                                                echo $product["stock_quantity"];
                                                ?>

                                            </td>

                                        </tr>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <tr>

                                        <td colspan="2" class="text-center">

                                            <i class="fa-solid fa-circle-check text-success"></i>

                                            All products are sufficiently stocked.

                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             RECENT SALES
        ================================================== -->

        <div class="row g-4 mt-1">

            <div class="col-12">

                <div class="dashboard-panel">

                    <div class="panel-header">

                        <div>

                            <h5>
                                Recent Sales
                            </h5>

                            <small class="text-muted">
                                Latest transactions
                            </small>

                        </div>

                        <a href="../sales/sales.php">
                            View All
                        </a>

                    </div>


                    <?php

                    $recentSales = $conn->query(
                        "SELECT
                            sales.invoice_number,
                            sales.customer_name,
                            sales.grand_total,
                            sales.payment_method,
                            sales.sale_date
                         FROM sales
                         ORDER BY sales.sale_id DESC
                         LIMIT 5"
                    );

                    ?>


                    <div class="table-responsive">

                        <table class="dashboard-table">

                            <thead>

                                <tr>

                                    <th>
                                        Invoice
                                    </th>

                                    <th>
                                        Customer
                                    </th>

                                    <th>
                                        Amount
                                    </th>

                                    <th>
                                        Payment
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php if ($recentSales->num_rows > 0): ?>

                                    <?php while ($sale = $recentSales->fetch_assoc()): ?>

                                        <tr>

                                            <td>

                                                <strong>
                                                    <?php
                                                    echo htmlspecialchars(
                                                        $sale["invoice_number"]
                                                    );
                                                    ?>
                                                </strong>

                                            </td>


                                            <td>

                                                <?php

                                                echo htmlspecialchars(
                                                    $sale["customer_name"] ?: "Walk-in Customer"
                                                );

                                                ?>

                                            </td>


                                            <td>

                                                <strong>
                                                    NPR
                                                    <?php
                                                    echo number_format(
                                                        $sale["grand_total"],
                                                        2
                                                    );
                                                    ?>
                                                </strong>

                                            </td>


                                            <td>

                                                <span class="status-badge status-success">

                                                    <?php
                                                    echo htmlspecialchars(
                                                        $sale["payment_method"]
                                                    );
                                                    ?>

                                                </span>

                                            </td>


                                            <td>

                                                <?php
                                                echo date(
                                                    "d M Y",
                                                    strtotime($sale["sale_date"])
                                                );
                                                ?>

                                            </td>

                                        </tr>

                                    <?php endwhile; ?>

                                <?php else: ?>

                                    <tr>

                                        <td colspan="5" class="text-center text-muted">

                                            No sales recorded yet.

                                        </td>

                                    </tr>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>


    </main>


    <!-- Footer -->

    <footer class="dashboard-footer text-center">

        SmartIMS &copy;
        <?php echo date("Y"); ?>

        | Smart Inventory & Sales Management System

    </footer>

</div>


<!-- =========================================================
     CHART.JS
========================================================= -->

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<?php

// =========================================================
// GET LAST 7 DAYS SALES
// =========================================================

// =========================================================
// GET LAST 7 DAYS SALES
// =========================================================

$chartLabels = [];
$chartData = [];

for ($i = 6; $i >= 0; $i--) {

    $date = date(
        "Y-m-d",
        strtotime("-$i days")
    );

    $label = date(
        "D d",
        strtotime($date)
    );

    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(grand_total), 0) AS total_sales
         FROM sales
         WHERE sale_date = ?"
    );

    $stmt->bind_param("s", $date);

    $stmt->execute();

    $result = $stmt->get_result();

    $row = $result->fetch_assoc();

    $chartLabels[] = $label;

    $chartData[] = (float) $row["total_sales"];

    $stmt->close();
}


$chartLabels = [];
$chartData = [];

while ($row = $chartQuery->fetch_assoc()) {

    $chartLabels[] = date(
        "D d",
        strtotime($row["sale_day"])
    );

    $chartData[] = (float) $row["total_sales"];

}

?>

<script>

const salesLabels = <?php echo json_encode($chartLabels); ?>;

const salesData = <?php echo json_encode($chartData); ?>;


const salesChart = document.getElementById("salesChart");


new Chart(salesChart, {

    type: "line",

    data: {

        labels: salesLabels,

        datasets: [{

            label: "Daily Sales",

            data: salesData,

            borderWidth: 3,

            tension: 0.4,

            fill: true,

            pointRadius: 4,

            pointHoverRadius: 6

        }]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {

                display: false

            }

        },

        scales: {

            y: {

                beginAtZero: true,

                ticks: {

                    callback: function(value) {

                        return "NPR " + value;

                    }

                }

            }

        }

    }

});

</script>


<!-- Dashboard JavaScript -->

<script src="../../assets/js/dashboard.js"></script>

</body>

</html>