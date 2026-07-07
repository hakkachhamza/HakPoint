<?php
require_once __DIR__.'/_helpers.php';
require_csrf();
$type = ($_GET['type'] ?? 'order') === 'invoice' ? 'invoice' : 'order';
$id = (int)($_GET['id'] ?? 0);
$docId = (int)($_GET['doc_id'] ?? 0);
$collection = $type === 'invoice' ? 'supplier_invoice_documents' : 'purchase_order_documents';
$key = $type === 'invoice' ? 'supplier_invoice_id' : 'purchase_order_id';
$docs = data_read($collection, []);
$new = [];
foreach ($docs as $d) {
    if ((int)($d['id'] ?? 0) === $docId && (int)($d[$key] ?? 0) === $id) {
        ge_unlink_document_file($d);
        continue;
    }
    $new[] = $d;
}
data_write($collection, $new);
$page = $type === 'invoice' ? 'supplier_invoice_show' : 'purchase_order_show';
redirect_to('index.php?page='.$page.'&id='.$id);
