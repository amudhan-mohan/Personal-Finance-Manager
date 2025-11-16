<?php
require_once 'includes/auth.php';
$auth = new Auth();

if ($auth->isLoggedIn()) {
    header('Location: pages/dashboard.php');
    exit;
} else {
    header('Location: pages/login.php');
    exit;
}
?>