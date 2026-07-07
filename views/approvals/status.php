<?php
require_once __DIR__.'/_helpers.php';
$pdo=db(); ge_approval_ensure_tables($pdo);
$id=(int)($_POST['id'] ?? 0);
$status=(string)($_POST['status'] ?? 'En attente');
$reason=trim((string)($_POST['decision_reason'] ?? ''));
$apply=!empty($_POST['apply_target']);
if($id>0){
    ge_approval_decide($pdo,$id,$status,$reason,$apply);
    audit_log('approval_decision','ID: '.$id.' | '.$status.($apply?' | applied':''));
}
redirect_to('index.php?page=approval_show&id='.$id.'&ok=1');
