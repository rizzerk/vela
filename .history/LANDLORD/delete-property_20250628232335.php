<?php
session_start();
include '../connection.php';

// Check if user is logged in and has permission
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'landlord') {
//     header("HTTP/1.1 403 Forbidden");
//     exit(json_encode(['success' => false, 'message' => 'Access denied']));
// }

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    header("HTTP/1.1 405 Method Not Allowed");
    exit(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

// Get property ID from query string
$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($property_id <= 0) {
    header("HTTP/1.1 400 Bad Request");
    exit(json_encode(['success' => false, 'message' => 'Invalid property ID']));
}

// Define your upload directory path (matches your add property script)
$project_root = $_SERVER['DOCUMENT_ROOT'] . '/vela';
$upload_dir = $project_root . '/uploads/properties/';

// Start transaction
mysqli_begin_transaction($conn);

try {
    // First, get all photo file paths associated with the property
    $get_photos_query = "SELECT file_path FROM PROPERTY_PHOTO WHERE property_id = ?";
    $stmt = mysqli_prepare($conn, $get_photos_query);
    mysqli_stmt_bind_param($stmt, 'i', $property_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $photo_paths = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $photo_paths[] = $row['file_path'];
    }
    
    // Then delete all photos from database
    $delete_photos_query = "DELETE FROM PROPERTY_PHOTO WHERE property_id = ?";
    $stmt = mysqli_prepare($conn, $delete_photos_query);
    mysqli_stmt_bind_param($stmt, 'i', $property_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to delete property photos from database");
    }
    
    // Then delete the property itself
    $delete_property_query = "DELETE FROM PROPERTY WHERE property_id = ?";
    $stmt = mysqli_prepare($conn, $delete_property_query);
    mysqli_stmt_bind_param($stmt, 'i', $property_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to delete property from database");
    }
    
    // Commit transaction if both queries succeeded
    mysqli_commit($conn);
    
    // Now delete the actual photo files from server
    $deleted_files = [];
    $failed_deletions = [];
    
    foreach ($photo_paths as $path) {
        // Extract just the filename from the stored path
        $filename = basename($path);
        $full_path = $upload_dir . $filename;
        
        if (file_exists($full_path)) {
            if (unlink($full_path)) {
                $deleted_files[] = $filename;
            } else {
                $failed_deletions[] = $filename;
            }
        } else {
            $failed_deletions[] = $filename . ' (file not found)';
        }
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Property deleted successfully',
        'deleted_files' => $deleted_files,
        'upload_directory' => $upload_dir // For debugging
    ];
    
    if (!empty($failed_deletions)) {
        $response['warning'] = 'Some files could not be deleted: ' . implode(', ', $failed_deletions);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage(),
        'upload_directory' => $upload_dir // For debugging
    ]);
}
?>