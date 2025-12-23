<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../classes/Asset.php';

$typeFilter = $_GET['type'] ?? 'All';

$assetService = new Asset();

$assets = $assetService->findAllWithImages();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assets - AssetVault</title>
    <link rel="stylesheet" href="/styles/assets.css">
    <link rel="stylesheet" href="/styles/asset_list.css">
</head>
<body>

<header class="assets-header">
    <div class="assets-header-left">
        <img src="/images/logo-black.png" alt="AssetVault Logo" class="logo-icon">
        <div class="logo">AssetVault</div>
    </div>
    <div class="assets-header-right">
        <a href="/upload?from=assets" class="upload-button"><img src="/images/upload-icon.png" class="upload-icon">Upload</a>
        <a href="/dashboard?from=assets">
            <img src="/images/user.png" alt="User Icon" class="user-icon">
        </a>
    </div>
</header>

<main class="assets-main">
    <div class="filter-bar">
        <a href="?type=All" class="filter-btn <?= $typeFilter === 'All' ? 'active' : '' ?>">All</a>
        <a href="?type=Model 3D" class="filter-btn <?= $typeFilter === 'Model 3D' ? 'active' : '' ?>">3D Model</a>
        <a href="?type=Audio" class="filter-btn <?= $typeFilter === 'Audio' ? 'active' : '' ?>">Audio</a>
        <a href="?type=Texture" class="filter-btn <?= $typeFilter === 'Texture' ? 'active' : '' ?>">Textures</a>
    </div>

    <div class="assets-grid">
        <?php $source = 'assets'; ?>
        <?php foreach ($assets as $asset): ?>
            <?php if ($typeFilter === 'All' || $asset['type'] === $typeFilter): ?>
                <?php include __DIR__ . '/partials/asset_list.php'; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</main>

<footer class="mobile-footer">
    <a href="?type=All" class="mobile-nav-item <?= $typeFilter === 'All' ? 'active' : '' ?>"><span>All</span></a>
    <a href="?type=Model 3D" class="mobile-nav-item <?= $typeFilter === 'Model 3D' ? 'active' : '' ?>"><span>Models</span></a>
    <a href="?type=Audio" class="mobile-nav-item <?= $typeFilter === 'Audio' ? 'active' : '' ?>"><span>Audio</span></a>
    <a href="?type=Texture" class="mobile-nav-item <?= $typeFilter === 'Texture' ? 'active' : '' ?>"><span>Textures</span></a>
</footer>

</body>
</html>