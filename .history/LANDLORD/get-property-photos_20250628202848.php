<?php
include '../connection.php';

if (isset($_GET['id'])) {
    $property_id = mysqli_real_escape_string($conn, $_GET['id']);
    $query = "SELECT * FROM PROPERTY_PHOTO WHERE property_id = '$property_id' ORDER BY uploaded_at ASC";
    $result = mysqli_query($conn, $query);
    
    $photos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $photos[] = $row;
    }
    
    echo json_encode($photos);
} else {
    echo json_encode(['error' => 'No property ID provided']);
}
?>