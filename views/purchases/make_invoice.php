<?php
require_once __DIR__.'/_helpers.php';
require_csrf();
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$invoiceId = ge_purchase_make_supplier_invoice($pdo, $id);
if ($invoiceId > 0) redirect_to('index.php?page=supplier_invoice_show&id='.$invoiceId.'&invoice_created=1');
redirect_to('index.php?page=purchases');
