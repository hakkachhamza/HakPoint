<?php
$id = (int)($_GET['id'] ?? 0);
$docId = (int)($_GET['doc_id'] ?? 0);
$docs = data_read('order_documents', []);
$new = [];
foreach ($docs as $d) {
    if ((int)($d['id'] ?? 0) === $docId && (int)($d['order_id'] ?? 0) === $id) {
        $path = $d['path'] ?? (__DIR__.'/../../'.($d['url'] ?? ''));
        if ($path && file_exists($path)) @unlink($path);
        continue;
    }
    $new[] = $d;
}
data_write('order_documents', $new);
redirect_to('index.php?page=order_show&id='.$id);
