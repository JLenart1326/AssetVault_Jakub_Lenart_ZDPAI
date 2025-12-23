<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../classes/Asset.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: /assets");
    exit();
}

$assetId = (int)$_POST['id'];
$returnTo = 'assets';
if (isset($_POST['from']) && in_array($_POST['from'], ['dashboard', 'assets'])) {
    $returnTo = $_POST['from'];
}

$userId = $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] === 'admin');

$assetObj = new Asset();
$asset = $assetObj->getById($assetId);

if (!$asset) {
    header("Location: /{$returnTo}");
    exit();
}

if ($isAdmin || $asset['user_id'] == $userId) {
    if (!empty($asset['file_path']) && file_exists(__DIR__ . '/../' . $asset['file_path'])) {
        @unlink(__DIR__ . '/../' . $asset['file_path']);
    }
    if (!empty($asset['images'])) {
        foreach ($asset['images'] as $img) {
            if (!empty($img['image_path']) && file_exists(__DIR__ . '/../' . $img['image_path'])) {
                @unlink(__DIR__ . '/../' . $img['image_path']);
            }
        }
    }
    $assetObj->delete($assetId, $userId, $isAdmin);
}

header("Location: /{$returnTo}");
exit();
?>
