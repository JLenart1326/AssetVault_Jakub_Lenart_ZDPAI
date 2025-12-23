<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Sprawdzenie, czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    // Jeśli nie – przekierowanie do ekranu logowania
    header('Location: /login');
    exit();
}
