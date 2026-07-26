<?php

// ===============================================
// SMART IMS
// Login Authentication
// ===============================================

// Start session to store user information
session_start();

// Include database connection
include("../config/database.php");

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Get username and password from login form
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    // SQL query to find active user
    $sql = "SELECT * FROM users
            WHERE username = ?
            AND status = 'Active'";

    // Prepare statement (Prevents SQL Injection)
    $stmt = $conn->prepare($sql);

    // Bind username parameter
    $stmt->bind_param("s", $username);

    // Execute query
    $stmt->execute();

    // Get query result
    $result = $stmt->get_result();

    // Check if user exists
    if ($result->num_rows == 1) {

        // Fetch user data
        $user = $result->fetch_assoc();

        /*
        ------------------------------------------------
        NOTE:
        Currently your password is stored as plain text.
        Later we'll upgrade to password_hash().
        ------------------------------------------------
        */

        if ($password == $user["password"]) {

            // Store user information in session
            $_SESSION["user_id"] = $user["user_id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["role"] = $user["role"];

            // Redirect to dashboard
           header("Location: ../modules/dashboard/dashboard.php");
            exit();

        } else {

            // Wrong password
            header("Location: login.php?error=Invalid Password");
            exit();

        }

    } else {

        // User not found
        header("Location: login.php?error=User Not Found");
        exit();

    }

} else {

    // Prevent direct access
    header("Location: login.php");
    exit();

}

?>