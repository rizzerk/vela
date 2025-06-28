<?php
include '../connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property_id = mysqli_real_escape_string($conn, $_POST['property_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $monthly_rent = mysqli_real_escape_string($conn, $_POST['monthly_rent']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Update property details
    $query = "UPDATE PROPERTY SET 
              title = '$title',
              address = '$address',
              description = '$description',
              monthly_rent = '$monthly_rent',
              status = '$status'
              WHERE property_id = '$property_id'";
    
    $success = mysqli_query($conn, $query);
    
    // Handle file uploads
    if ($success && !empty($_FILES['new_photos'])) {
        $upload_dir = '../uploads/properties/' . $property_id . '/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        foreach ($_FILES['new_photos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['new_photos']['error'][$key] === UPLOAD_ERR_OK) {
                $file_name = basename($_FILES['new_photos']['name'][$key]);
                $file_path = 'uploads/properties/' . $property_id . '/' . uniqid() . '_' . $file_name;
                $destination = '../' . $file_path;
                
                if (move_uploaded_file($tmp_name, $destination)) {
                    // Save to database
                    $insert_query = "INSERT INTO PROPERTY_PHOTO (property_id, file_path) 
                                    VALUES ('$property_id', '$file_path')";
                    mysqli_query($conn, $insert_query);
                }
            }
        }
    }
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>