<?php
// Database configuration
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "library_management_system";

// Create connection
$conn = mysqli_connect($host, $user, $pass, $dbname);

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>