<?php
if (!isset($_SESSION['tenant_id']) || $_SESSION['user_type'] !== 'tenant') {
    header('Location: ../login.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM leases WHERE tenant_id = ? AND status = 'active'");
$stmt->bind_param("i", $_SESSION['tenant_id']);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    header('Location: ../no-lease.php');
    exit();
}
?>