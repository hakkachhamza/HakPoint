<?php
require_once __DIR__.'/_helpers.php';
$id=(int)($_POST['id']??0); $orders=data_read('orders',[]); $idx=order_find_index($orders,$id); if($idx<0) redirect_to('index.php?page=orders');
$o=&$orders[$idx]; $client=order_client_from_post();
foreach(['client_ref','order_date','delivery_date','delivery_delay','payment_terms','payment_mode','shipping_method','channel','template','public_note','private_note'] as $f){ $o[$f]=$_POST[$f]??($o[$f]??''); }
$o['client_id']=$client['client_id']; $o['client']=$client['client'];
$o['lines']=order_normalize_lines_from_post(); $o['updated_at']=date('d/m/Y H:i'); order_recalculate($o); $syncLines=$o['lines'] ?? []; unset($o); data_write('orders',$orders); ge_sync_document_lines('order_lines','order_id',$id,$syncLines); redirect_to('index.php?page=order_show&id='.$id.'&updated=1');
