<!-- =========================================================
     SmartIMS Top Navbar
========================================================= -->

<nav class="top-navbar">

    <!-- Sidebar Toggle -->
    <button class="sidebar-toggle">
        <i class="fa-solid fa-bars"></i>
    </button>

    <!-- Search Box -->
    <div class="search-box">

        <i class="fa-solid fa-magnifying-glass"></i>

        <input
            type="text"
            placeholder="Search products, sales, suppliers..."
        >

    </div>

    <!-- Right Side -->
    <div class="navbar-right">

        <!-- Notification -->
        <button class="notification-btn">

            <i class="fa-regular fa-bell"></i>

            <span class="notification-badge">
                3
            </span>

        </button>

        <!-- Date & Time -->
        <div class="text-center">

            <strong>
                <?php echo date("d M Y"); ?>
            </strong>

            <br>

            <small class="text-muted">
                <?php echo date("l"); ?>
            </small>

        </div>

        <!-- User Profile -->
        <div class="profile">

            <div class="profile-avatar">

                <i class="fa-solid fa-user"></i>

            </div>

            <div class="profile-info">

                <strong>

                    <?php
                    echo $_SESSION["full_name"];
                    ?>

                </strong>

                <span>

                    <?php
                    echo $_SESSION["role"];
                    ?>

                </span>

            </div>

        </div>

    </div>

</nav>