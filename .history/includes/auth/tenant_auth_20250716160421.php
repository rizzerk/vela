<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../authentication-error.php');
    exit();
}

if ($_SESSION['role'] !== 'tenant') {
    header('Location: ../authentication-error.php');
    exit();
}
?>