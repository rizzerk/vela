<?php
$host = 'localhost';
$dbname = 'vela';
$username = 'root';
$password = '';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
    die("Connection error. Please try again later.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Set charset
$conn->set_charset("utf8mb4");



?>