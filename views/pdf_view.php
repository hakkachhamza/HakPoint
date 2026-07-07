<?php
$file = $_GET['file'] ?? '';
$file = str_replace(['\\', "\0"], ['/', ''], $file);
$file = ltrim($file, '/');
$real = realpath(__DIR__.'/../'.$file);
$base = realpath(__DIR__.'/../uploads');
if (!$file || str_contains($file, '..') || !$real || !$base || !str_starts_with($real, $base.DIRECTORY_SEPARATOR) || !is_file($real) || strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'pdf') {
    http_response_code(404);
    echo '<div class="panel"><h3>PDF introuvable</h3><p>Le document demandé est introuvable ou non autorisé.</p></div>';
    return;
}
$name = basename($real);
$url = e($file);
?>
<div class="pdf-viewer-page">
    <div class="pdf-viewer-top">
        <div><i class="fa-regular fa-file-pdf"></i> <strong><?=e($name)?></strong></div>
        <div class="pdf-viewer-actions">
            <a class="btn small" href="<?= $url ?>" target="_blank" rel="noopener">Ouvrir</a>
            <a class="btn small" href="index.php?page=pdf_download&file=<?=urlencode($file)?>">Télécharger</a>
        </div>
    </div>
    <iframe class="pdf-frame" src="<?= $url ?>"></iframe>
</div>
