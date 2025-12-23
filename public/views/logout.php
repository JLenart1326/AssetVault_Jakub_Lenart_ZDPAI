<?php
require_once __DIR__ . '/../classes/User.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = new User();
$user->logout();

header('Location: /login');
exit();
?>
