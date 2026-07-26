<?php

$host = "127.0.0.1";
$user = "root";
$password = "Pawan@123";
$database = "smartims";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}
?>