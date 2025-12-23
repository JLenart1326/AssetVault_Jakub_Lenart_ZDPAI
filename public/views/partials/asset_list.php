<?php
if (!isset($asset)) return;

$thumb = '/images/default-thumb.png';
if (!empty($asset['images'])) {
    $thumb = '/' . htmlspecialchars($asset['images'][0]['image_path']);
}

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
document.querySelectorAll('.asset-card-link-js').forEach(card => {
  card.onclick = function(event) {
    if (event.target.closest('.download-btn')) {
      return;
    }
    window.location.href = this.dataset.href;
  };
});
</script>