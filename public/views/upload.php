<?php
// Dołączamy niezbędne pliki: sprawdzanie logowania, konfiguracja i klasa obsługująca Assety
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../classes/Asset.php';

$msg = "";
$errors = [];

// Sprawdzamy skąd przyszedł użytkownik, żeby wiedzieć gdzie go cofnąć po sukcesie
$fromPage = isset($_GET['from']) ? $_GET['from'] : 'dashboard';

// Jeśli formularz został wysłany (kliknięto Upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $userId = $_SESSION['user_id'];
    $file = $_FILES['asset_file'] ?? null;
    $thumbnails = $_FILES['thumbnails'] ?? null;

    $assetObj = new Asset();

    // Próbujemy zapisać asset razem z miniaturkami
    list($success, $errorArr) = $assetObj->uploadWithThumbnails($userId, $name, $description, $type, $file, $thumbnails);

    if ($success) {
        // Udało się - przekieruj na odpowiednią stronę (assets lub dashboard)
        header('Location: ' . ($fromPage === 'assets' ? '/assets' : '/dashboard'));
        exit();
    } else {
        // Coś poszło nie tak, zapisujemy błędy do wyświetlenia
        $errors = $errorArr;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload Asset - AssetVault</title>
    <link rel="stylesheet" href="/styles/upload_edit.css">
</head>
<body>
<div class="upload-wrapper">
    <h2>Upload Asset</h2>

    <?php if (!empty($errors)): ?>
        <ul class="error-list">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php elseif (!empty($msg)): ?>
        <p class="success-msg"><?= $msg ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="upload-form" id="uploadForm">
        
        <div class="drop-zone" id="dropZone">
            <p class="drop-zone-p">Drag and drop your file here</p>
            <p class="drop-zone-p">or</p>
            <label class="upload-btn">
                Browse Files
                <input type="file" name="asset_file" id="assetFileInput" style="display: none;">
            </label>
            <p class="max-size">Maximum file size: 1GB</p>
            <div id="fileNameDisplay"></div>
        </div>

        <label>Asset Name
            <input type="text" name="name" required>
        </label>

        <label>Description
            <textarea name="description" rows="4" required></textarea>
        </label>

        <label>Type
            <select name="type" id="typeSelect" required onchange="updateAcceptedExtensions()">
                <option value="Model 3D">Model 3D</option>
                <option value="Texture">Texture</option>
                <option value="Audio">Audio</option>
            </select>
        </label>

        <div class="add-showcase-wrapper">
            <div class="add-showcase-sub-wrapper">
                <label>Add Showcase</label>
                <small>(up to 3 images)</small>
            </div>
            <label class="upload-btn add-showcase-btn">
                Browse Files
                <input type="file" id="showcaseInput" name="thumbnails[]" multiple accept="image/png,image/jpeg" hidden>
            </label>
        </div>
        <div id="showcaseFilesList" style="margin-top: 10px;"></div>

        <div class="button-row">
            <button type="submit" class="upload-main-btn">Upload Asset</button>
            <a href="<?= ($fromPage === 'assets' ? '/assets' : '/dashboard') ?>" class="cancel-upload-btn">Cancel Upload</a>
        </div>
    </form>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const assetFileInput = document.getElementById('assetFileInput');
const fileNameDisplay = document.getElementById('fileNameDisplay');
const typeSelect = document.getElementById('typeSelect');
const showcaseInput = document.getElementById('showcaseInput');
const showcaseFilesList = document.getElementById('showcaseFilesList');

// Definiujemy jakie rozszerzenia są dozwolone dla konkretnego typu assetu
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

// Aktualizuje pole pliku, żeby przeglądarka wiedziała co użytkownik może wybrać
function updateAcceptedExtensions() {
    const acceptList = getAcceptedExtensions().join(",");
    assetFileInput.setAttribute("accept", acceptList);

    // Czyścimy wybrany plik przy zmianie typu, żeby uniknąć błędów
    assetFileInput.value = "";
    fileNameDisplay.innerText = "";
}

typeSelect.addEventListener('change', updateAcceptedExtensions);

// Kliknięcie w dowolne miejsce strefy drop otwiera okno wyboru plików
dropZone.addEventListener('click', (e) => {
    if (e.target === dropZone) {
        assetFileInput.click();
    }
});

// Efekty wizualne: podświetlenie strefy jak przeciągasz nad nią plik
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('dragover');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('dragover');
});

// Obsługa wyświetlania nazw wybranych miniaturek (Showcase)
showcaseInput.addEventListener('change', function() {
    showcaseFilesList.innerHTML = '';
    for (const file of showcaseInput.files) {
        const item = document.createElement('div');
        item.textContent = file.name;
        item.style.fontSize = '14px';
        item.style.marginTop = '5px';
        showcaseFilesList.appendChild(item);
    }
});

// Obsługa upuszczenia pliku (DROP)
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('dragover');

    const files = e.dataTransfer.files;
    const acceptedExtensions = getAcceptedExtensions();

    if (files.length && acceptedExtensions.length) {
        const file = files[0];
        const fileExt = "." + file.name.split('.').pop().toLowerCase();

        // Sprawdzamy czy rozszerzenie pasuje do wybranego typu (np. czy to .fbx dla modelu 3D)
        if (acceptedExtensions.includes(fileExt)) {
            assetFileInput.files = files;
            fileNameDisplay.innerText = file.name;
        } else {
            alert(`Unsupported file format: ${fileExt}. Allowed: ${acceptedExtensions.join(", ")}`);
        }
    }
});

// Aktualizacja nazwy pliku pod strefą po wybraniu go klasycznie przyciskiem
assetFileInput.addEventListener('change', () => {
    fileNameDisplay.innerText = assetFileInput.files.length ? assetFileInput.files[0].name : '';
});

// Inicjalizacja przy załadowaniu strony
updateAcceptedExtensions();

// Walidacja formularza przed wysłaniem
const uploadForm = document.getElementById('uploadForm');
uploadForm.addEventListener('submit', function(e) {
    let oldError = document.getElementById('mainFileErrorMsg');
    if (oldError) oldError.remove();

    // Nie pozwól wysłać formularza, jeśli nie wybrano głównego pliku
    if (!assetFileInput.files.length) {
        const msg = document.createElement('div');
        msg.id = 'mainFileErrorMsg';
        msg.className = 'error-list';
        msg.style.marginBottom = '10px';
        msg.innerHTML = "<li>Main file is required.</li>";
        uploadForm.parentNode.insertBefore(msg, uploadForm);
        e.preventDefault();
    }
});
</script>
</body>
</html>