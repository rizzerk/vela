<?php
include '../connection.php';

if (isset($_GET['id'])) {
    $photo_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // First get the file path to delete the actual file
    $query = "SELECT file_path FROM PROPERTY_PHOTO WHERE photo_id = '$photo_id'";
    $result = mysqli_query($conn, $query);
    $photo = mysqli_fetch_assoc($result);
    
    if ($photo) {
        $file_path = '../' . $photo['file_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Then delete from database
        $delete_query = "DELETE FROM PROPERTY_PHOTO WHERE photo_id = '$photo_id'";
        if (mysqli_query($conn, $delete_query)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Photo not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No photo ID provided']);
}
?>