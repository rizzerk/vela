<?php
include '../connection.php';

// Set header for JSON response
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required = ['property_id', 'title', 'address', 'monthly_rent', 'status'];
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

    // Start transaction
    mysqli_begin_transaction($conn);

    // Update property details
    $query = "UPDATE PROPERTY SET 
              title = '$title',
              address = '$address',
              description = '$description',
              monthly_rent = '$monthly_rent',
              status = '$status'
              WHERE property_id = '$property_id'";

    if (!mysqli_query($conn, $query)) {
        throw new Exception("Database error: " . mysqli_error($conn));
    }

    // Handle file uploads if any
    if (!empty($_FILES['new_photos'])) {
        $upload_dir = '../uploads/properties/' . $property_id . '/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("Failed to create upload directory");
            }
        }

        foreach ($_FILES['new_photos']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['new_photos']['error'][$key] === UPLOAD_ERR_OK) {
                // Validate file
                $file_size = $_FILES['new_photos']['size'][$key];
                if ($file_size > 10 * 1024 * 1024) {
                    continue; // Skip files that are too large
                }

                $file_name = uniqid() . '_' . basename($_FILES['new_photos']['name'][$key]);
                $file_path = 'uploads/properties/' . $property_id . '/' . $file_name;
                $destination = '../' . $file_path;

                if (move_uploaded_file($tmp_name, $destination)) {
                    $insert_query = "INSERT INTO PROPERTY_PHOTO (property_id, file_path) 
                                    VALUES ('$property_id', '$file_path')";
                    if (!mysqli_query($conn, $insert_query)) {
                        // If insert fails, delete the uploaded file
                        if (file_exists($destination)) {
                            unlink($destination);
                        }
                        throw new Exception("Failed to save photo to database");
                    }
                }
            }
        }
    }

    // Commit transaction
    mysqli_commit($conn);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>