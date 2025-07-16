<?php
session_start();
require_once '../connection.php';

// Check if user is logged in and is landlord
if (!isset($_SESSION['loggedin'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] != 'landlord') {
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['file']) || !isset($_GET['app_id'])) {
    header("Location: applications.php");
    exit();
}

$filename = basename($_GET['file']);
$application_id = (int)$_GET['app_id'];

// Verify the file belongs to this application
$stmt = $conn->prepare("SELECT document_path FROM APPLICATIONS WHERE application_id = ?");
$stmt->bind_param("i", $application_id);
$stmt->execute();
$result = $stmt->get_result();
$application = $result->fetch_assoc();
$stmt->close();

if (!$application || basename($application['document_path']) !== $filename) {
    header("Location: applications.php");
    exit();
}

$filepath = '../' . $application['document_path']; // Adjust path as needed

if (!file_exists($filepath)) {
    die('File not found');
}

// Get file extension
$file_extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));

// Set appropriate headers
switch ($file_extension) {
    case 'pdf':
        $content_type = 'application/pdf';
        break;
    case 'doc':
        $content_type = 'application/msword';
        break;
    case 'docx':
        $content_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    default:
        $content_type = 'application/octet-stream';
}

// Force download
header('Content-Description: File Transfer');
header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));
flush(); // Flush system output buffer
readfile($filepath);
exit;