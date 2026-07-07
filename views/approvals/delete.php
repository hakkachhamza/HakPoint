<?php
require_once __DIR__.'/_helpers.php';
$pdo=db(); ge_approval_ensure_tables($pdo);
$id=(int)($_POST['id'] ?? 0);
if($id>0){
    foreach(ge_approval_docs($id) as $d) ge_unlink_document_file($d);
    $docs=array_values(array_filter(data_read('approval_documents', []), fn($d)=>(int)($d['approval_id'] ?? 0)!==$id));
    data_write('approval_documents',$docs);
    $stmt=$pdo->prepare('DELETE FROM ge_approval_requests WHERE id=? AND tenant_id=?');
    $stmt->execute([$id, ge_current_tenant_id()]);
    audit_log('approval_deleted','ID: '.$id);
}
redirect_to('index.php?page=approvals&ok=1');
