<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SESSION['role'] !== 'general_user') {
    header('Location: index.php');
    exit();
}
?>