<?php

// Start session
session_start();

// If user is not logged in, go back to login page
if (!isset($_SESSION["user_id"])) {

    header("Location: login.php");
    exit();

}

?>

<!DOCTYPE html>
<html>

<head>

    <title>Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="container mt-5">

<h2>

Welcome,
<?php echo $_SESSION["full_name"]; ?>

🎉

</h2>

<p>

Username :
<b><?php echo $_SESSION["username"]; ?></b>

</p>

<p>

Role :
<b><?php echo $_SESSION["role"]; ?></b>

</p>

<a href="logout.php" class="btn btn-danger">

Logout

</a>

</body>

</html>