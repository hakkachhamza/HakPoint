<?php
$id=(int)($_GET['id']??0);
$invoices=array_values(array_filter(data_read('invoices',[]),fn($i)=>(int)($i['id']??0)!==$id));
data_write('invoices',$invoices);
ge_delete_document_lines('invoice_lines','invoice_id',$id);
$docs=data_read('invoice_documents',[]); $kept=[];
foreach($docs as $d){ if((int)($d['invoice_id']??0)===$id){ ge_unlink_document_file($d); continue; } $kept[]=$d; }
data_write('invoice_documents',$kept);
redirect_to('index.php?page=invoices');
