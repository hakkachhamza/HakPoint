<?php
$type = ($_POST['type'] ?? $_GET['type'] ?? 'customer') === 'supplier' ? 'supplier' : 'customer';
$collection = $type === 'supplier' ? 'supplier_payments' : 'payments';
$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$status = trim((string)($_POST['status'] ?? $_GET['status'] ?? ''));
$back = $type === 'supplier' ? 'supplier_payments' : 'payments';
if ($id > 0 && $status !== '') {
    ge_finance_set_payment_status($collection, $id, $status);
    redirect_to('index.php?page='.($type === 'supplier' ? 'supplier_payment_show' : 'payment_show').'&id='.$id.'&status_ok=1');
}
redirect_to('index.php?page='.$back);
