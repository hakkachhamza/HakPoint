<?php
require_once __DIR__.'/_helpers.php';
$pdo=db(); ge_credit_ensure_tables($pdo);
$id=(int)($_POST['id'] ?? 0);
if($id>0){
    foreach(ge_credit_docs($id) as $d) ge_unlink_document_file($d);
    $docs=array_values(array_filter(data_read('credit_note_documents', []), fn($d)=>(int)($d['credit_note_id'] ?? 0)!==$id));
    data_write('credit_note_documents',$docs);
    $stmt=$pdo->prepare('DELETE FROM ge_credit_notes WHERE id=? AND tenant_id=?');
    $stmt->execute([$id, ge_current_tenant_id()]);
    audit_log('credit_note_deleted','ID: '.$id);
}
redirect_to('index.php?page=credit_notes&ok=1');
