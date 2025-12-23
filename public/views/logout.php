<?php
// Dołączamy klasę User, aby skorzystać z jej metody wylogowywania
require_once __DIR__ . '/../classes/User.php';

// Upewniamy się, że sesja jest aktywna (bo musimy mieć do niej dostęp, żeby ją zniszczyć)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tworzymy obiekt użytkownika i wywołujemy metodę czyszczącą sesję i ciasteczka
$user = new User();
$user->logout();

// Po wylogowaniu wyrzucamy użytkownika na stronę logowania
header('Location: /login');
exit();
?>