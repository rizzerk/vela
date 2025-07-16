<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: authentication-error.php');
    exit();
}

if ($_SESSION['role'] !== 'general_user') {
    header('Location: authentication-error.php');
    exit();
}
?>