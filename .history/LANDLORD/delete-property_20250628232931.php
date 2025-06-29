<?php
session_start();
include '../connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    header("HTTP/1.1 405 Method Not Allowed");
    exit(json_encode(['success' => false, 'message' => 'Invalid request method']));
}

$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($property_id <= 0) {
    header("HTTP/1.1 400 Bad Request");
    exit(json_encode(['success' => false, 'message' => 'Invalid property ID']));
}

// Define your upload directory path
$base_upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/vela/uploads/properties/';
$property_upload_dir = $base_upload_dir . $property_id . '/';

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get all photo file paths
    $get_photos_query = "SELECT file_path FROM PROPERTY_PHOTO WHERE property_id = ?";
    $stmt = mysqli_prepare($conn, $get_photos_query);
    mysqli_stmt_bind_param($stmt, 'i', $property_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Delete photos from database
    $delete_photos_query = "DELETE FROM PROPERTY_PHOTO WHERE property_id = ?";
    $stmt = mysqli_prepare($conn, $delete_photos_query);
    mysqli_stmt_bind_param($stmt, 'i', $property_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to delete property photos from database");
    }
    
    // Delete property
    $delete_property_query = "DELETE FROM PROPERTY WHERE property_id = ?";
    $stmt = mysqli_prepare($conn, $delete_property_query);
    mysqli_stmt_bind_param($stmt, 'i', $property_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to delete property from database");
    }
    
    mysqli_commit($conn);
    
    // Delete the entire property directory and its contents
    $deleted_directory = false;
    if (file_exists($property_upload_dir)) {
        // Delete all files in directory first
        array_map('unlink', glob("$property_upload_dir/*"));
        // Then delete the directory
        $deleted_directory = rmdir($property_upload_dir);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Property deleted successfully',
        'directory_deleted' => $deleted_directory
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>