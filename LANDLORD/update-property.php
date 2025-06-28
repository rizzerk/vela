<?php
include '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property_id = mysqli_real_escape_string($conn, $_POST['property_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $monthly_rent = mysqli_real_escape_string($conn, $_POST['monthly_rent']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $query = "UPDATE PROPERTY SET 
              title = '$title',
              address = '$address',
              description = '$description',
              monthly_rent = '$monthly_rent',
              status = '$status'
              WHERE property_id = '$property_id'";
    
    if (mysqli_query($conn, $query)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>