<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../authentication-error.php');
    exit();
}

if ($_SESSION['role'] !== 'landlord') {
    header('Location: ../authentication-error.php');
    exit();
}
?>