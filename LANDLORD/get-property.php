<?php
include '../connection.php';

if (isset($_GET['id'])) {
    $property_id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM PROPERTY WHERE property_id = '$property_id'";
    $result = mysqli_query($conn, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        echo json_encode(mysqli_fetch_assoc($result));
    } else {
        echo json_encode(['error' => 'Property not found']);
    }
} else {
    echo json_encode(['error' => 'No property ID provided']);
}
?>