<?php
require_once __DIR__.'/_helpers.php';
$id=(int)($_POST['id'] ?? 0);
$docId=(int)($_POST['doc_id'] ?? 0);
$docs=data_read('approval_documents', []);
$keep=[];
foreach($docs as $d){
    if((int)($d['id'] ?? 0)===$docId && (int)($d['approval_id'] ?? 0)===$id){ ge_unlink_document_file($d); continue; }
    $keep[]=$d;
}
data_write('approval_documents',$keep);
audit_log('approval_document_deleted','Validation: '.$id.' | Document: '.$docId);
redirect_to('index.php?page=approval_show&id='.$id.'&ok=1');
