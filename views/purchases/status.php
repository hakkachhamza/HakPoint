<?php
require_once __DIR__.'/_helpers.php';
require_csrf();
$pdo = db();
ge_purchase_ensure_tables($pdo);
$type = ($_GET['type'] ?? 'order') === 'invoice' ? 'invoice' : 'order';
$id = (int)($_GET['id'] ?? 0);
$status = trim((string)($_GET['status'] ?? ''));
if ($id <= 0 || $status === '') redirect_to('index.php?page=purchases');

if ($type === 'invoice') {
    $allowed = ge_purchase_status_options('invoice');
    if (!in_array($status, $allowed, true)) $status = 'À payer';
    $paidAt = $status === 'Payée' ? date('Y-m-d') : null;
    $stmt = $pdo->prepare('UPDATE ge_supplier_invoices SET status=?, paid_at=? WHERE id=? AND tenant_id=?');
    $stmt->execute([$status, $paidAt, $id, ge_current_tenant_id()]);
    if(function_exists('ge_erp_post_supplier_invoice_accounting')) ge_erp_post_supplier_invoice_accounting($pdo, $id);
    if($status === 'Payée' && function_exists('ge_erp_mark_supplier_invoice_paid')) ge_erp_mark_supplier_invoice_paid($pdo, $id);
    elseif(function_exists('ge_finance_update_supplier_invoice_payment')) ge_finance_update_supplier_invoice_payment($id);
    audit_log('supplier_invoice_status', 'ID '.$id.' => '.$status);
    redirect_to('index.php?page=supplier_invoice_show&id='.$id.'&updated=1');
}

$allowed = ge_purchase_status_options('order');
if (!in_array($status, $allowed, true)) $status = 'Brouillon';
$order = ge_purchase_order_row($pdo, $id);
if (!$order) redirect_to('index.php?page=purchases');

// Once stock was applied, do not allow returning to an earlier/annulled status from this shortcut,
// because that would leave stock movements already created.
if (!empty($order['receipt_applied_at']) && in_array($status, ['Brouillon','Validée','Commandée','Annulée'], true)) {
    redirect_to('index.php?page=purchase_order_show&id='.$id.'&stock_applied=1');
}

if ($status === 'Reçue' && function_exists('ge_erp_apply_purchase_receipt')) {
    ge_erp_apply_purchase_receipt($pdo, $id);
} else {
    $stmt = $pdo->prepare('UPDATE ge_purchase_orders SET status=? WHERE id=? AND tenant_id=?');
    $stmt->execute([$status, $id, ge_current_tenant_id()]);
}

if ($status === 'Facturée') ge_purchase_make_supplier_invoice($pdo, $id);

audit_log('purchase_order_status', 'ID '.$id.' => '.$status);
redirect_to('index.php?page=purchase_order_show&id='.$id.'&updated=1');
