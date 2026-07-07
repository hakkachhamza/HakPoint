<?php
require_once __DIR__.'/_helpers.php';
$id=(int)($_POST['id']??0);
$invoices=data_read('invoices',[]);
$idx=invoice_find_index($invoices,$id);
if($idx<0) redirect_to('index.php?page=invoices');
$invoice=&$invoices[$idx];
$clientPick=ge_client_from_post();
if(trim((string)($clientPick['client'] ?? ''))==='') {
    $postedClient=trim((string)($_POST['client'] ?? ''));
    if($postedClient!=='') $clientPick=['client_id'=>(int)($_POST['client_id'] ?? 0),'client'=>$postedClient,'client_ref'=>$_POST['client_ref'] ?? ''];
}
$invoice['client_id']=$clientPick['client_id'];
$invoice['client']=$clientPick['client'];
if(!empty($clientPick['client_ref'])) $invoice['client_ref']=$clientPick['client_ref'];
$invoice['type']=$_POST['type']??'Facture standard';
$invoice['invoice_date']=$_POST['invoice_date']??date('d/m/Y');
$invoice['due_date']=$_POST['due_date']??$invoice['invoice_date'];
$invoice['payment_terms']=$_POST['payment_terms']??'';
$invoice['payment_mode']=$_POST['payment_mode']??'';
$invoice['bank_account']=$_POST['bank_account']??'';
$invoice['template']=$_POST['template']??'crabe';
$invoice['public_note']=$_POST['public_note']??'';
$invoice['private_note']=$_POST['private_note']??'';
$invoice['lines']=invoice_normalize_lines_from_post();
$invoice['updated_at']=date('d/m/Y H:i');
invoice_recalculate($invoice);
$syncLines=$invoice['lines'] ?? [];
unset($invoice);
data_write('invoices',$invoices);
ge_sync_document_lines('invoice_lines','invoice_id',$id,$syncLines);
redirect_to('index.php?page=invoice_show&id='.$id.'&updated=1');
