<?php
// Dołączamy mechanizm autoryzacji i klasę Asset
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../classes/Asset.php';

// Zabezpieczenie: plik może być wywołany tylko metodą POST i musi zawierać ID assetu
// Chroni to przed przypadkowym usunięciem przez wpisanie adresu w przeglądarce
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: /assets");
    exit();
}

$assetId = (int)$_POST['id'];
$returnTo = 'assets';

// Sprawdzamy, skąd przyszło żądanie (dashboard czy assets), żeby wiedzieć gdzie wrócić po usunięciu
if (isset($_POST['from']) && in_array($_POST['from'], ['dashboard', 'assets'])) {
    $returnTo = $_POST['from'];
}

$userId = $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] === 'admin');

// Pobieramy informacje o assecie, żeby sprawdzić, czy w ogóle istnieje
$assetObj = new Asset();
$asset = $assetObj->getById($assetId);

if (!$asset) {
    header("Location: /{$returnTo}");
    exit();
}

// Sprawdzamy uprawnienia: usuwać może tylko właściciel pliku lub administrator
if ($isAdmin || $asset['user_id'] == $userId) {
    
    // 1. Usuwamy fizyczny plik główny z serwera (jeśli istnieje)
    // Używamy __DIR__, żeby mieć pewność, że szukamy w dobrym miejscu
    if (!empty($asset['file_path']) && file_exists(__DIR__ . '/../' . $asset['file_path'])) {
        @unlink(__DIR__ . '/../' . $asset['file_path']);
    }

    // 2. Usuwamy fizyczne pliki miniaturek (Showcase) z serwera
    if (!empty($asset['images'])) {
        foreach ($asset['images'] as $img) {
            if (!empty($img['image_path']) && file_exists(__DIR__ . '/../' . $img['image_path'])) {
                @unlink(__DIR__ . '/../' . $img['image_path']);
            }
        }
    }
    
    // 3. Po usunięciu plików z dysku, usuwamy rekord z bazy danych
    $assetObj->delete($assetId, $userId, $isAdmin);
}

// Po wszystkim wracamy na stronę, z której przyszliśmy
header("Location: /{$returnTo}");
exit();
?>