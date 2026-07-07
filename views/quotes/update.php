<?php
require_once __DIR__.'/_helpers.php';
$id=(int)($_POST['id']??0); $quotes=data_read('quotes',[]); $idx=quote_find_index($quotes,$id); if($idx<0) redirect_to('index.php?page=quotes');
$q=&$quotes[$idx];
$clientPick=ge_client_from_post();
$q['client_id']=$clientPick['client_id'];
$q['client']=$clientPick['client'];
if(empty($_POST['client_ref']) && !empty($clientPick['client_ref'])) $_POST['client_ref']=$clientPick['client_ref'];
foreach(['client_ref','proposal_date','validity','payment_terms','payment_mode','origin','delivery_delay','shipping_method','delivery_date','template','public_note','private_note'] as $f){ $q[$f]=$_POST[$f]??($q[$f]??''); }
$q['lines']=quote_normalize_lines_from_post(); $q['end_date']=quote_validity_end($q['proposal_date']?:date('d/m/Y'),$q['validity']?:15); $q['updated_at']=date('d/m/Y H:i'); quote_recalculate($q); $syncLines=$q['lines'] ?? []; unset($q); data_write('quotes',$quotes); ge_sync_document_lines('quote_lines','quote_id',$id,$syncLines); redirect_to('index.php?page=quote_show&id='.$id.'&updated=1');
