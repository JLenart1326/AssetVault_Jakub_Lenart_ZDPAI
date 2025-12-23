<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Asset.php';

$errors = [];
$msg = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /assets");
    exit();
}
$assetId = (int)$_GET['id'];

$returnTo = (isset($_GET['from']) && in_array($_GET['from'], ['dashboard', 'assets'])) ? $_GET['from'] : 'assets';
$returnUrl = "/asset?id={$assetId}&from=" . htmlspecialchars($returnTo);

$assetService = new Asset();
$asset = $assetService->getById($assetId);

if (!$asset) {
    header("Location: /{$returnTo}");
    exit();
}

$userId = $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] === 'admin');
$isOwner = ($asset['user_id'] == $userId);

if (!$isAdmin && !$isOwner) {
    header("Location: {$returnUrl}");
    exit();
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? $asset['type']);

    $updateMain = !empty($_POST['update_main']);
    $mainFile = $updateMain ? ($_FILES['asset_file'] ?? null) : null;
    
    $updateShowcase = !empty($_POST['update_screenshots']);
    $thumbnails = $updateShowcase ? ($_FILES['new_showcase_files'] ?? null) : null;
    
    list($success, $errorArr) = $assetService->updateWithFiles(
        $assetId,
        $name,
        $description,
        $type,
        $userId,
        $isAdmin,
        $updateMain,
        $mainFile,
        $updateShowcase,
        $thumbnails
    );

    if ($success) {
        header("Location: {$returnUrl}");
        exit();
    } else {
        $errors = $errorArr;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Asset - AssetVault</title>
    <link rel="stylesheet" href="/styles/upload_edit.css">
</head>
<body>

<div class="upload-wrapper">
    <h2>Edit Asset</h2>
    <h4 class="asset-name"><?= htmlspecialchars($asset['name']) ?></h4>

    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php elseif (!empty($msg)): ?>
        <p class="success-msg"><?= $msg ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="upload-form">
        <div class="checkbox-wrapper">
            <label for="updateMainAsset">Update Main Asset</label>
            <input type="checkbox" id="updateMainAsset" name="update_main">
        </div>
        <div id="updateMainAssetFields" style="display: none; margin-top: 15px;">
            <label class="upload-btn">
                Browse Files
                <input type="file" id="newMainAssetFile" name="asset_file" style="display: none;">
            </label>
            <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">Maximum file size: 1GB</div>
            <div id="mainAssetFileName" style="font-size: 14px; margin-top: 5px;"></div>
        </div>

        <label>Asset Name
            <input type="text" name="name" value="<?= htmlspecialchars($asset['name']) ?>" required>
        </label>

        <label>Description
            <textarea name="description" rows="4" required><?= htmlspecialchars($asset['description']) ?></textarea>
        </label>

        <label>Type
            <select name="type" id="typeSelect" required disabled data-original-type="<?= htmlspecialchars($asset['type']) ?>">
                <option value="Model 3D" <?= $asset['type'] == 'Model 3D' ? 'selected' : '' ?>>Model 3D</option>
                <option value="Texture" <?= $asset['type'] == 'Texture' ? 'selected' : '' ?>>Texture</option>
                <option value="Audio" <?= $asset['type'] == 'Audio' ? 'selected' : '' ?>>Audio</option>
            </select>
        </label>

        <div class="checkbox-wrapper">
            <label for="update_screenshots">Update Showcase</label>
            <input type="checkbox" id="update_screenshots" name="update_screenshots">
        </div>
        <div id="updateShowcaseFields" style="display: none; margin-top: 15px;">
            <label class="upload-btn">
                Browse Files
                <input type="file" id="newShowcaseFiles" name="new_showcase_files[]" multiple style="display: none;" accept=".jpg,.jpeg,.png">
            </label>
            <div style="font-size: 12px; color: #6b7280; margin-top: 5px;">You can upload up to 3 images.</div>
            <div id="showcaseFilesList" style="font-size: 14px; margin-top: 5px;"></div>
        </div>

        <div class="button-row">
            <button type="submit" class="upload-main-btn">Save Changes</button>
            <a href="/asset?id=<?= $assetId ?>&from=<?= htmlspecialchars($returnTo) ?>" class="cancel-upload-btn">Cancel Changes</a>
        </div>
    </form>
</div>
<script>
    const updateMainAssetCheckbox = document.getElementById('updateMainAsset');
    const updateMainAssetFields = document.getElementById('updateMainAssetFields');
    const newMainAssetFile = document.getElementById('newMainAssetFile');
    const mainAssetFileName = document.getElementById('mainAssetFileName');
    const typeSelect = document.getElementById('typeSelect');
    const updateShowcaseCheckbox = document.getElementById('update_screenshots');
    const updateShowcaseFields = document.getElementById('updateShowcaseFields');
    const newShowcaseFiles = document.getElementById('newShowcaseFiles');
    const showcaseFilesList = document.getElementById('showcaseFilesList');

    updateShowcaseCheckbox.addEventListener('change', () => {
        if (updateShowcaseCheckbox.checked) {
            updateShowcaseFields.style.display = 'block';
        } else {
            updateShowcaseFields.style.display = 'none';
            newShowcaseFiles.value = '';
            showcaseFilesList.innerText = '';
        }
    });

    newShowcaseFiles.addEventListener('change', () => {
        showcaseFilesList.innerHTML = '';
        const files = Array.from(newShowcaseFiles.files);
        files.forEach(file => {
            const ext = "." + file.name.split('.').pop().toLowerCase();
            if ([".jpg", ".jpeg", ".png"].includes(ext)) {
                const item = document.createElement('div');
                item.textContent = file.name;
                showcaseFilesList.appendChild(item);
            } else {
                alert(`Unsupported file format: ${ext}. Only JPG and PNG allowed.`);
                newShowcaseFiles.value = '';
                showcaseFilesList.innerText = '';
            }
        });
    });

    function getAcceptedExtensions() {
        const selectedType = typeSelect.value;
        if (selectedType === "Model 3D") {
            return [".fbx", ".obj", ".blend"];
        } else if (selectedType === "Texture") {
            return [".jpg", ".jpeg", ".png", ".tga"];
        } else if (selectedType === "Audio") {
            return [".mp3", ".wav", ".ogg"];
        }
        return [];
    }

     updateMainAssetCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        if (isChecked) {
            typeSelect.disabled = false;
            updateMainAssetFields.style.display = 'block';
            updateNewMainAssetAccept();
        } else {
            typeSelect.disabled = true;
            typeSelect.value = typeSelect.dataset.originalType;
            updateMainAssetFields.style.display = 'none';
            newMainAssetFile.value = '';
            mainAssetFileName.innerText = '';
        }
    });
    
    newMainAssetFile.addEventListener('change', () => {
        const file = newMainAssetFile.files[0];
        if (file) {
            const accepted = getAcceptedExtensions();
            const fileExt = "." + file.name.split('.').pop().toLowerCase();
            if (accepted.includes(fileExt)) {
                mainAssetFileName.innerText = file.name;
            } else {
                alert(`Unsupported file format: ${fileExt}. Allowed: ${accepted.join(", ")}`);
                newMainAssetFile.value = '';
                mainAssetFileName.innerText = '';
            }
        } else {
            mainAssetFileName.innerText = '';
        }
    });

    typeSelect.addEventListener('change', () => {
        if (updateMainAssetCheckbox.checked) {
            updateNewMainAssetAccept();
        }
    });

    function updateNewMainAssetAccept() {
        const accepted = getAcceptedExtensions().join(",");
        newMainAssetFile.setAttribute('accept', accepted);
    }
</script>

</body>
</html>