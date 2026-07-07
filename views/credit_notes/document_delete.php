<?php
require_once __DIR__.'/_helpers.php';
$id=(int)($_POST['id'] ?? 0);
$docId=(int)($_POST['doc_id'] ?? 0);
$docs=data_read('credit_note_documents', []);
$keep=[];
foreach($docs as $d){
    if((int)($d['id'] ?? 0)===$docId && (int)($d['credit_note_id'] ?? 0)===$id){ ge_unlink_document_file($d); continue; }
    $keep[]=$d;
}
data_write('credit_note_documents',$keep);
audit_log('credit_note_document_deleted','Avoir: '.$id.' | Document: '.$docId);
redirect_to('index.php?page=credit_note_show&id='.$id.'&ok=1');
