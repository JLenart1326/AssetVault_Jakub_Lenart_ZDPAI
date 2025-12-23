<?php
// Dołączamy wymagane pliki: autoryzację, config i klasę Asset
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Asset.php';

$errors = [];
$msg = '';

// Sprawdzamy, czy podano ID assetu do edycji. Jak nie, to wyrzucamy do listy assetów.
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /assets");
    exit();
}
$assetId = (int)$_GET['id'];

// Ustalamy, skąd użytkownik przyszedł (dashboard czy assets), żeby wiedzieć gdzie go cofnąć po edycji
$returnTo = (isset($_GET['from']) && in_array($_GET['from'], ['dashboard', 'assets'])) ? $_GET['from'] : 'assets';
$returnUrl = "/asset?id={$assetId}&from=" . htmlspecialchars($returnTo);

// Pobieramy dane assetu z bazy
$assetService = new Asset();
$asset = $assetService->getById($assetId);

// Jeśli taki asset nie istnieje, to wracamy do poprzedniej strony
if (!$asset) {
    header("Location: /{$returnTo}");
    exit();
}

$userId = $_SESSION['user_id'];
$isAdmin = ($_SESSION['role'] === 'admin');
$isOwner = ($asset['user_id'] == $userId);

// Zabezpieczenie: Edytować może tylko właściciel pliku albo administrator
if (!$isAdmin && !$isOwner) {
    header("Location: {$returnUrl}");
    exit();
}

// Obsługa zapisu zmian (gdy kliknięto przycisk Save Changes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Jeśli nie zmieniamy typu pliku, to zostaje stary typ z bazy
    $type = trim($_POST['type'] ?? $asset['type']);

    // Sprawdzamy czy użytkownik chce podmienić główny plik assetu
    $updateMain = !empty($_POST['update_main']);
    $mainFile = $updateMain ? ($_FILES['asset_file'] ?? null) : null;
    
    // Sprawdzamy czy użytkownik chce podmienić obrazki podglądowe (showcase)
    $updateShowcase = !empty($_POST['update_screenshots']);
    $thumbnails = $updateShowcase ? ($_FILES['new_showcase_files'] ?? null) : null;
    
    // Próbujemy zaktualizować dane w bazie i ew. podmienić pliki na dysku
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
        // Udało się - wracamy do widoku szczegółów assetu
        header("Location: {$returnUrl}");
        exit();
    } else {
        // Coś poszło nie tak - wyświetlimy listę błędów
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

    // Pokazywanie/ukrywanie pola wgrywania miniaturek po kliknięciu checkboxa
    updateShowcaseCheckbox.addEventListener('change', () => {
        if (updateShowcaseCheckbox.checked) {
            updateShowcaseFields.style.display = 'block';
        } else {
            updateShowcaseFields.style.display = 'none';
            // Czyścimy wybrane pliki, jeśli użytkownik się rozmyślił
            newShowcaseFiles.value = '';
            showcaseFilesList.innerText = '';
        }
    });

    // Wyświetlanie listy wybranych plików (miniaturek)
    newShowcaseFiles.addEventListener('change', () => {
        showcaseFilesList.innerHTML = '';
        const files = Array.from(newShowcaseFiles.files);
        files.forEach(file => {
            const ext = "." + file.name.split('.').pop().toLowerCase();
            // Pozwalamy tylko na obrazki
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

    // Funkcja pomocnicza zwracająca dozwolone rozszerzenia dla wybranego typu assetu
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

     // Pokazywanie/ukrywanie pola wgrywania głównego pliku
     updateMainAssetCheckbox.addEventListener('change', function() {
        const isChecked = this.checked;
        if (isChecked) {
            // Jeśli wymieniamy plik, odblokowujemy zmianę typu assetu
            typeSelect.disabled = false;
            updateMainAssetFields.style.display = 'block';
            updateNewMainAssetAccept();
        } else {
            // Jeśli nie wymieniamy pliku, blokujemy zmianę typu i przywracamy oryginalny typ
            typeSelect.disabled = true;
            typeSelect.value = typeSelect.dataset.originalType;
            updateMainAssetFields.style.display = 'none';
            newMainAssetFile.value = '';
            mainAssetFileName.innerText = '';
        }
    });
    
    // Walidacja wybranego głównego pliku (czy pasuje do typu)
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

    // Jeśli zmieniamy typ assetu, musimy zaktualizować filtr dozwolonych plików w przeglądarce
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