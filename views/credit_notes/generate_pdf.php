<?php
require_once __DIR__.'/_helpers.php';
$pdo=db();
$id=(int)($_POST['id'] ?? $_GET['id'] ?? 0);
if($id>0){ ge_credit_generate_pdf_file($pdo,$id); audit_log('credit_note_pdf_generated','ID: '.$id); }
redirect_to('index.php?page=credit_note_show&id='.$id.'&pdf_generated=1');
