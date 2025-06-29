<?php
include '../connection.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required = ['property_id', 'title', 'address', 'monthly_rent', 'status', 'property_type'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Escape inputs
    $property_id = mysqli_real_escape_string($conn, $_POST['property_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $monthly_rent = mysqli_real_escape_string($conn, $_POST['monthly_rent']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $property_type = mysqli_real_escape_string($conn, $_POST['property_type']);

    // Start transaction
    mysqli_begin_transaction($conn);

    // Update property details
    $query = "UPDATE PROPERTY SET 
              title = '$title',
              address = '$address',
              description = '$description',
              monthly_rent = '$monthly_rent',
              status = '$status',
              property_type = '$property_type'
              WHERE property_id = '$property_id'";

    if (!mysqli_query($conn, $query)) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }

    // Handle file uploads if any
    if (!empty($_FILES['new_photos']['name'][0])) {
        $base_upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/vela/uploads/properties/';
        $upload_dir = $base_upload_dir . $property_id . '/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("Failed to create upload directory");
            }
        }

        // Loop through each file
        foreach ($_FILES['new_photos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['new_photos']['error'][$key] === UPLOAD_ERR_OK) {
                $file_size = $_FILES['new_photos']['size'][$key];
                if ($file_size > 10 * 1024 * 1024) continue;

                $file_type = $_FILES['new_photos']['type'][$key];
                $valid_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($file_type, $valid_types)) continue;

                $file_name = uniqid() . '_' . basename($_FILES['new_photos']['name'][$key]);
                $relative_path = 'uploads/properties/' . $property_id . '/' . $file_name;
                $absolute_path = $upload_dir . $file_name;

                if (move_uploaded_file($tmp_name, $absolute_path)) {
                    $insert_query = "INSERT INTO PROPERTY_PHOTO (property_id, file_path) 
                                    VALUES ('$property_id', '$relative_path')";
                    if (!mysqli_query($conn, $insert_query)) {
                        unlink($absolute_path);
                        throw new Exception("Failed to save photo to database");
                    }
                }
            }
        }
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>