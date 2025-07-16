<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'landlord') {
    header('Location: ../authentication-error.php');
    exit();
}
?>