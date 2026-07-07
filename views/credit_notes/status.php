<?php
require_once __DIR__.'/_helpers.php';
$pdo=db(); ge_credit_ensure_tables($pdo);
$id=(int)($_POST['id'] ?? 0);
$status=(string)($_POST['status'] ?? 'Brouillon');
if($id>0 && in_array($status, ge_credit_status_options(), true)){
    $refunded = $status === 'Remboursé' ? date('Y-m-d') : null;
    $stmt=$pdo->prepare('UPDATE ge_credit_notes SET status=?, refunded_at=? WHERE id=? AND tenant_id=?');
    $stmt->execute([$status,$refunded,$id,ge_current_tenant_id()]);
    if(function_exists('ge_erp_apply_credit_note')) ge_erp_apply_credit_note($pdo, $id);
    audit_log('credit_note_status','ID: '.$id.' | '.$status);
}
redirect_to('index.php?page=credit_note_show&id='.$id.'&ok=1');
