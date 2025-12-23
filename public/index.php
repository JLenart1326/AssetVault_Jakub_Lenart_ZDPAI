<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

// 1. Załaduj autoloader Composera
require __DIR__ . '/vendor/autoload.php';

// 2. Start sesji (Twoja aplikacja na nich polega)
session_start();

// 3. Utwórz aplikację
$app = AppFactory::create();

// Obsługa błędów Slima
$app->addErrorMiddleware(true, true, true);

// --- FUNKCJA POMOCNICZA (WRAPPER) ---
// Pozwala wczytać stary plik .php do wnętrza Slima bez zmieniania kodu pliku
function renderLegacyView(Response $response, $file) {
    ob_start(); // Buforujemy wyjście
    require __DIR__ . '/views/' . $file;
    $content = ob_get_clean(); // Pobieramy wygenerowany HTML
    $response->getBody()->write($content);
    return $response;
}

// --- ROUTING (MAPOWANIE ADRESÓW) ---

// Strona główna - przekierowanie logiczne z Twojego starego index.php
$app->get('/', function (Request $request, Response $response) {
    if (isset($_SESSION['user_id'])) {
        return $response->withHeader('Location', '/dashboard')->withStatus(302);
    } else {
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
});

// Logowanie
$app->any('/login', function (Request $request, Response $response) {
    // any() obsługuje i GET i POST, bo Twój plik login.php obsługuje oba
    return renderLegacyView($response, 'login.php');
});

// Rejestracja
$app->any('/register', function (Request $request, Response $response) {
    return renderLegacyView($response, 'register.php');
});

// Wylogowanie
$app->any('/logout', function (Request $request, Response $response) {
    return renderLegacyView($response, 'logout.php');
});

// Dashboard
$app->get('/dashboard', function (Request $request, Response $response) {
    return renderLegacyView($response, 'dashboard.php');
});

// Lista Assetów
$app->get('/assets', function (Request $request, Response $response) {
    return renderLegacyView($response, 'assets.php');
});

// Szczegóły Assetu (np. /asset?id=1 - Twój kod obsługuje $_GET['id'])
$app->get('/asset', function (Request $request, Response $response) {
    return renderLegacyView($response, 'asset.php');
});

// Upload
$app->any('/upload', function (Request $request, Response $response) {
    return renderLegacyView($response, 'upload.php');
});

// Edycja
$app->any('/edit_asset', function (Request $request, Response $response) {
    return renderLegacyView($response, 'edit_asset.php');
});

// Usuwanie
$app->any('/delete_asset', function (Request $request, Response $response) {
    return renderLegacyView($response, 'delete_asset.php');
});

$app->run();