<?php
$id=(int)($_GET['id']??0);
$quotes=array_values(array_filter(data_read('quotes',[]),fn($q)=>(int)($q['id']??0)!==$id));
data_write('quotes',$quotes);
ge_delete_document_lines('quote_lines','quote_id',$id);
$docs=data_read('quote_documents',[]); $kept=[];
foreach($docs as $d){ if((int)($d['quote_id']??0)===$id){ ge_unlink_document_file($d); continue; } $kept[]=$d; }
data_write('quote_documents',$kept);
redirect_to('index.php?page=quotes');
