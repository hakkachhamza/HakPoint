<?php
require_once __DIR__.'/_helpers.php';
require_csrf();
$pdo = db();
$type = ($_GET['type'] ?? 'order') === 'invoice' ? 'invoice' : 'order';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) redirect_to('index.php?page=purchases');
$doc = ge_purchase_generate_pdf_file($pdo, $type, $id);
if (!$doc) redirect_to('index.php?page=purchases');
if(!empty($_GET['download'])){ ge_send_file_download($doc['path'] ?? (__DIR__.'/../../'.($doc['url'] ?? '')), $doc['filename'] ?? 'document.pdf', 'application/pdf'); }
$page = $type === 'invoice' ? 'supplier_invoice_show' : 'purchase_order_show';
redirect_to('index.php?page='.$page.'&id='.$id.'&pdf_generated=1');
