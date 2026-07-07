<?php
require_once __DIR__.'/_helpers.php';
$quotes=data_read('quotes',[]); $id=next_id($quotes);
$clientPick=ge_client_from_post();
$quote=[
 'id'=>$id,'ref'=>quote_ref($id),'client_id'=>$clientPick['client_id'],'client_ref'=>($_POST['client_ref']??'') ?: $clientPick['client_ref'],'client'=>$clientPick['client'],
 'proposal_date'=>$_POST['proposal_date']??date('d/m/Y'),'validity'=>$_POST['validity']??'15','payment_terms'=>$_POST['payment_terms']??'',
 'payment_mode'=>$_POST['payment_mode']??'','origin'=>$_POST['origin']??'','delivery_delay'=>$_POST['delivery_delay']??'',
 'shipping_method'=>$_POST['shipping_method']??'','delivery_date'=>$_POST['delivery_date']??'','template'=>$_POST['template']??'azur',
 'public_note'=>$_POST['public_note']??'','private_note'=>$_POST['private_note']??'','status'=>'Brouillon','created_at'=>date('d/m/Y H:i'),
 'lines'=>quote_normalize_lines_from_post()
];
$quote=array_merge($quote, ge_author_fields('author'));
$quote['end_date']=quote_validity_end($quote['proposal_date'],$quote['validity']); quote_recalculate($quote); $quotes[]=$quote; data_write('quotes',$quotes); ge_sync_document_lines('quote_lines','quote_id',$id,$quote['lines'] ?? []); redirect_to('index.php?page=quote_show&id='.$id);
