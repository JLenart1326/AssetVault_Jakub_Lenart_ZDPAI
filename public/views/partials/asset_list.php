<?php
// Zabezpieczenie: jeśli zmienna $asset nie została przekazana, przerywamy (nie ma co wyświetlać)
if (!isset($asset)) return;

// Domyślna ścieżka do miniatury (jeśli asset nie ma własnych zdjęć)
$thumb = '/images/default-thumb.png';

// Jeśli asset ma wgrane obrazki, używamy pierwszego z nich jako miniatury
if (!empty($asset['images'])) {
    $thumb = '/' . htmlspecialchars($asset['images'][0]['image_path']);
}

// Pobieramy rozszerzenie pliku (np. png, fbx), żeby wyświetlić je ładnie na karcie
$extension = pathinfo($asset['file_path'], PATHINFO_EXTENSION);
?>

<div class="asset-card-link-js" data-href="/asset?id=<?= $asset['id'] ?>&from=<?= $source ?>" style="cursor:pointer;">
  <div class="asset-card">
    
    <div class="asset-preview">
        <img src="<?= $thumb ?>" alt="Preview" />
    </div>
    
    <div class="asset-meta">
      <div class="asset-title"><?= htmlspecialchars($asset['name']) ?></div>
      <div class="asset-description"><?= htmlspecialchars($asset['description']) ?></div>
      
      <div class="asset-footer">
        <span class="asset-type">.<?= strtolower($extension) ?></span>
        
        <a href="/<?= htmlspecialchars($asset['file_path']) ?>" download class="download-btn" onclick="event.stopPropagation();">Download</a>
      </div>
    </div>
  </div>
</div>

<script>
// Skrypt obsługujący klikanie w kafelki
document.querySelectorAll('.asset-card-link-js').forEach(card => {
  card.onclick = function(event) {
    // Jeśli użytkownik kliknął w przycisk pobierania (lub jego wnętrze), ignorujemy przekierowanie
    if (event.target.closest('.download-btn')) {
      return;
    }
    // W przeciwnym razie przenosimy użytkownika do strony szczegółów assetu
    window.location.href = this.dataset.href;
  };
});
</script>