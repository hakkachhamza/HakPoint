<?php
$id=(int)($_GET['id']??0); $docId=(int)($_GET['doc_id']??0);
$docs=data_read('invoice_documents',[]);
foreach($docs as $d){ if((int)($d['id']??0)===$docId){ $path=__DIR__.'/../../'.($d['url']??''); if(is_file($path)) @unlink($path); }}
$docs=array_values(array_filter($docs,fn($d)=>(int)($d['id']??0)!==$docId));
data_write('invoice_documents',$docs);
redirect_to('index.php?page=invoice_show&id='.$id);
