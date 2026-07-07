<?php
require_once __DIR__.'/_helpers.php';
require_once __DIR__.'/../../app/pdf_docs.php';
$id=(int)($_GET['id']??0); $order=find_row_by_id(data_read('orders',[]),$id); if(!$order) redirect_to('index.php?page=orders');
$ref=$order['ref']??('SO'.date('ym').'-'.$id); $client=trim((string)($order['client']??'')); $lines=$order['lines']??[];
$dates=['Date de commande'=>($order['order_date']??$order['date']??''),'Date livraison'=>($order['delivery_date']??'')];
if(!empty($_GET['download'])){ $info=ge_pdf_money_doc($order,'Commande',$ref,$client,$dates,$lines,__DIR__.'/../../uploads/orders','Commande','order_id','order_show','order_documents',$id,true); ge_send_file_download($info['path'] ?? '', $info['filename'] ?? 'commande.pdf', 'application/pdf'); }
ge_pdf_money_doc($order,'Commande',$ref,$client,$dates,$lines,__DIR__.'/../../uploads/orders','Commande','order_id','order_show','order_documents',$id);
