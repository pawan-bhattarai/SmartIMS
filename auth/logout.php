<?php

// ===============================================
// SMART IMS
// Logout Page
// ===============================================

// Start the session
session_start();

// Remove all session data
session_destroy();

// Redirect the user back to the login page
header("Location: login.php");

// Stop further execution
exit();

?>