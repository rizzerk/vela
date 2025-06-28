<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../connection.php';

echo "Connection test:<br>";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully!";
    
    // Test query
    $testQuery = "SELECT 1";
    $result = $conn->query($testQuery);
    if ($result === false) {
        echo "<br>Query failed: " . $conn->error;
    } else {
        echo "<br>Query executed successfully!";
    }
}