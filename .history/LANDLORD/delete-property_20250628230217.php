<?php
session_start();
include '../connection.php';

// Check if user is logged in and has permission
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'landlord') {
//     header("HTTP/1.1 403 Forbidden");
//     exit("Access denied");
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

// Start transaction
mysqli_begin_transaction($conn);

try {
    // First, delete all photos associated with the property
    $delete_photos_query = "DELETE FROM PROPERTY_PHOTO WHERE property_id = ?";
    $stmt = mysqli_prepare($conn, $delete_photos_query);
    mysqli_stmt_bind_param($stmt, 'i', $property_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to delete property photos");
    }
    
    // Then delete the property itself
    $delete_property_query = "DELETE FROM PROPERTY WHERE property_id = ?";
    $stmt = mysqli_prepare($conn, $delete_property_query);
    mysqli_stmt_bind_param($stmt, 'i', $property_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to delete property");
    }
    
    // Commit transaction if both queries succeeded
    mysqli_commit($conn);
    
    echo json_encode(['success' => true, 'message' => 'Property deleted successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>