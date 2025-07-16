<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'general') {
    header('Location: login.php');
    exit();
}
?>