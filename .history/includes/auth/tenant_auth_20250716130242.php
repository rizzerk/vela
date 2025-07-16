<?php
require_once '../../connection.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'tenant') {
    header('Location: ../index.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM leases WHERE user_id = ? AND active = '1'");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    header('Location: ../index.php');
    exit();
}
?>