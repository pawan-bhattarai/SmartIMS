<!DOCTYPE html>
<html lang="en">

<head>

    <!-- Basic Page Settings -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Browser Tab Title -->
    <title>SmartIMS | Login</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <!-- External CSS File -->
   <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <!-- Main Container -->
    <div class="container">

        <!-- Center Login Card -->
        <div class="row justify-content-center align-items-center vh-100">

       <div class="col-lg-8 col-xl-7">

                <!-- Login Card -->
                <div class="card shadow-lg border-0 rounded-4 overflow-hidden">

                    <div class="row g-0">

                        <!-- ================= LEFT SIDE ================= -->
                        <div class="col-md-5 left-side">

                            <!-- Company Logo -->
                           <img src="../assets/images/logo.png"
                            alt="SmartIMS Logo"
                              class="logo">
                              
                            <!-- Project Title -->
                            <h2 class="mt-4 fw-bold">
                                SmartIMS
                            </h2>

                            <!-- Short Description -->
                            <p class="mt-3">
                                Smart Inventory & Sales Management System
                            </p>

                        </div>

                        <!-- ================= RIGHT SIDE ================= -->
                        <div class="col-md-7">

                            <div class="login-area">

                                <!-- Login Heading -->
                                <h3 class="text-center mb-4">
                                    Login
                                </h3>

                                <?php


                                // Display error message if login fails
                            if (isset($_GET["error"])) {
                            ?>

                            <div class="alert alert-danger">

                            <?php echo htmlspecialchars($_GET["error"]); ?>

                            </div>

                            <?php
                            }
                                ?>
                                
                                <!-- Login Form -->
                                <form action="authenticate.php" method="POST">

                                    <!-- Username -->
                                    <div class="mb-3">

                                        <label class="form-label">
                                            Username
                                        </label>

                                        <div class="input-group">

                                            <span class="input-group-text">
                                                <i class="fa-solid fa-user"></i>
                                            </span>

                                            <input
                                                type="text"
                                                name="username"
                                                class="form-control"
                                                placeholder="Enter Username"
                                                required>

                                        </div>

                                    </div>

                                    <!-- Password -->
                                    <div class="mb-3">

                                        <label class="form-label">
                                            Password
                                        </label>

                                        <div class="input-group">

                                            <span class="input-group-text">
                                                <i class="fa-solid fa-lock"></i>
                                            </span>

                                            <input
                                                type="password"
                                                id="password"
                                                name="password"
                                                class="form-control"
                                                placeholder="Enter Password"
                                                required>

                                        </div>

                                    </div>

                                    <!-- Show Password -->
                                    <div class="form-check mb-4">

                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            id="showPassword">

                                        <label class="form-check-label">

                                            Show Password

                                        </label>

                                    </div>

                                    <!-- Login Button -->
                                    <button
                                        type="submit"
                                        class="btn btn-primary w-100 login-btn">

                                        <i class="fa-solid fa-right-to-bracket"></i>

                                        Login

                                    </button>

                                </form>

                            </div>

                        </div>
                        <!-- Right Side Ends -->

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <!-- External JavaScript -->
    <script src="../assets/js/script.js"></script>

</body>

</html>