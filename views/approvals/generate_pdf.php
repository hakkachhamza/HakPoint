<?php
require_once __DIR__.'/_helpers.php';
$pdo=db();
$id=(int)($_POST['id'] ?? $_GET['id'] ?? 0);
if($id>0){ ge_approval_generate_pdf_file($pdo,$id); audit_log('approval_pdf_generated','ID: '.$id); }
redirect_to('index.php?page=approval_show&id='.$id.'&pdf_generated=1');
