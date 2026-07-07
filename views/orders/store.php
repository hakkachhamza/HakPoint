<?php
require_once __DIR__.'/_helpers.php';
$orders=data_read('orders',[]); $id=next_id($orders); $client=order_client_from_post();
$order=[
 'id'=>$id,'ref'=>order_ref($id),'client_ref'=>$_POST['client_ref']??'','client_id'=>$client['client_id'],'client'=>$client['client'],
 'order_date'=>$_POST['order_date']??date('d/m/Y'),'delivery_date'=>$_POST['delivery_date']??'','delivery_delay'=>$_POST['delivery_delay']??'',
 'payment_terms'=>$_POST['payment_terms']??'','payment_mode'=>$_POST['payment_mode']??'','shipping_method'=>$_POST['shipping_method']??'',
 'channel'=>$_POST['channel']??'','template'=>$_POST['template']??'einstein','public_note'=>$_POST['public_note']??'','private_note'=>$_POST['private_note']??'',
 'status'=>'Brouillon','expediable'=>'Oui','facture'=>'Non','created_at'=>date('d/m/Y H:i'),'lines'=>order_normalize_lines_from_post()
];
$order=array_merge($order, ge_author_fields('author'));
order_recalculate($order); $orders[]=$order; data_write('orders',$orders); ge_sync_document_lines('order_lines','order_id',$id,$order['lines'] ?? []); redirect_to('index.php?page=order_show&id='.$id);
