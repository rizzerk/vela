<?php
session_start();
include '../connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['property_id']) || !is_numeric($input['property_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid property ID']);
    exit();
}

$property_id = (int)$input['property_id'];

try {
    // Start transaction
    $conn->begin_transaction();
    
    // First, get all photos for this property to delete files
    $photo_stmt = $conn->prepare("SELECT file_path FROM PROPERTY_PHOTO WHERE property_id = ?");
    $photo_stmt->bind_param("i", $property_id);
    $photo_stmt->execute();
    $photo_result = $photo_stmt->get_result();
    
    // Delete photo files from server
    while ($photo = $photo_result->fetch_assoc()) {
        $file_path = '../' . $photo['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Delete photos from database
    $delete_photos_stmt = $conn->prepare("DELETE FROM PROPERTY_PHOTO WHERE property_id = ?");
    $delete_photos_stmt->bind_param("i", $property_id);
    $delete_photos_stmt->execute();
    
    // Delete property from database
    $delete_property_stmt = $conn->prepare("DELETE FROM PROPERTY WHERE property_id = ?");
    $delete_property_stmt->bind_param("i", $property_id);
    $delete_property_stmt->execute();
    
    if ($delete_property_stmt->affected_rows > 0) {
        // Commit transaction
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Property deleted successfully']);
    } else {
        // Rollback transaction
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Property not found']);
    }
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Error deleting property: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the property']);
}
?>