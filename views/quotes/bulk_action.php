<?php
require_once __DIR__.'/_helpers.php';
$ids=$_POST['quote_ids']??[]; $action=$_POST['bulk_action']??'';
if($action==='send_email' && $ids){ redirect_to('index.php?page=quote_email&id='.(int)$ids[0]); }
if($action==='regenerate_pdf' && $ids){ redirect_to(csrf_url('index.php?page=quote_pdf_generate&id='.(int)$ids[0])); }
$quotes=data_read('quotes',[]);
if($action==='delete'){ $quotes=array_values(array_filter($quotes,fn($q)=>!in_array((int)($q['id']??0),array_map('intval',$ids),true))); data_write('quotes',$quotes); redirect_to('index.php?page=quotes'); }
$status=quote_status_from_action($action);
if($status){ foreach($quotes as &$q){ if(in_array((int)($q['id']??0),array_map('intval',$ids),true)){ $q['status']=$status; $q['updated_at']=date('d/m/Y H:i'); }} unset($q); data_write('quotes',$quotes); }
redirect_to('index.php?page=quotes');
