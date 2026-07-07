<?php
$id=(int)($_GET['id']??0);
$orders=array_values(array_filter(data_read('orders',[]), fn($o)=>(int)($o['id']??0)!==$id));
data_write('orders',$orders);
ge_delete_document_lines('order_lines','order_id',$id);
$docs=data_read('order_documents',[]); $kept=[];
foreach($docs as $d){ if((int)($d['order_id']??0)===$id){ ge_unlink_document_file($d); continue; } $kept[]=$d; }
data_write('order_documents',$kept);
redirect_to('index.php?page=orders');
