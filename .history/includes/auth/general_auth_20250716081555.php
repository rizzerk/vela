<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'general_user') {
    header('Location: ../../index.php');
    exit();
}
?>