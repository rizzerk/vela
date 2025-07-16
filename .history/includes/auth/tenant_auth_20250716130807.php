<?php

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'tenant') {
    header('Location: ../index.php');
    exit();
}

if (isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM leases WHERE tenant_id = ? AND active = '1'");
    $stmt->bind_param("i", $_SESSION['tenant_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        header('Location: ../index.php');
        exit();
    }
}
?>