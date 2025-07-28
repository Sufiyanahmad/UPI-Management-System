<?php
$host = "localhost";
$username = "root";
$password = ""; // XAMPP default password is empty
$database = "upi_system";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed. Please check MySQL settings.");
}
?>