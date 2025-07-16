<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

if (isset($conn) && isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT * FROM LEASE WHERE tenant_id = ? AND active = 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        header('Location: ../index.php');
        exit();
    }
}
?>