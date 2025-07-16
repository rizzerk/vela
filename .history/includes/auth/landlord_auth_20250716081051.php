<?php
if (!isset($_SESSION['landlord_id']) || $_SESSION['user_type'] !== 'landlord') {
    header('Location: ../login.php');
    exit();
}
?>