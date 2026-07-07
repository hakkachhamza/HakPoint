<?php
require_once __DIR__.'/_helpers.php';
$invoices=data_read('invoices',[]);
$id=next_id($invoices);
$invoiceDate=$_POST['invoice_date']??date('d/m/Y');
$clientPick=ge_client_from_post();
if(trim((string)($clientPick['client'] ?? ''))==='') {
    $postedClient=trim((string)($_POST['client'] ?? ''));
    if($postedClient!=='') $clientPick=['client_id'=>(int)($_POST['client_id'] ?? 0),'client'=>$postedClient,'client_ref'=>$_POST['client_ref'] ?? ''];
}
$sourceOrderId=(int)($_POST['source_order_id'] ?? $_POST['order_id'] ?? 0);
$sourceOrderRef=trim((string)($_POST['source_order_ref'] ?? ''));
$sourceExpeditionId=(int)($_POST['source_expedition_id'] ?? $_POST['expedition_id'] ?? 0);
$sourceExpeditionRef=trim((string)($_POST['source_expedition_ref'] ?? ''));
$invoiceRef=invoice_ref($id);
$invoice=[
 'id'=>$id,
 'ref'=>$invoiceRef,
 'client_id'=>$clientPick['client_id'],
 'client'=> $clientPick['client'],
 'client_ref'=> $clientPick['client_ref'] ?: ($_POST['client_ref'] ?? ''),
 'type'=>$_POST['type']??'Facture standard',
 'invoice_date'=>$invoiceDate,
 'due_date'=>$_POST['due_date']??$invoiceDate,
 'payment_terms'=>$_POST['payment_terms']??'',
 'payment_mode'=>$_POST['payment_mode']??'',
 'bank_account'=>$_POST['bank_account']??'',
 'template'=>$_POST['template']??'crabe',
 'public_note'=>$_POST['public_note']??'',
 'private_note'=>$_POST['private_note']??'',
 'status'=>'Brouillon',
 'lines'=>invoice_normalize_lines_from_post(),
 'payments'=>[],
 'order_id'=>$sourceOrderId,
 'order_ref'=>$sourceOrderRef,
 'expedition_id'=>$sourceExpeditionId,
 'expedition_ref'=>$sourceExpeditionRef,
 'created_at'=>date('d/m/Y H:i')
];
$invoice=array_merge($invoice, ge_author_fields('created_by'));
if($sourceOrderId<=0){ unset($invoice['order_id'], $invoice['order_ref']); }
if($sourceExpeditionId<=0){ unset($invoice['expedition_id'], $invoice['expedition_ref']); }
invoice_recalculate($invoice);
$invoices[]=$invoice;
data_write('invoices',$invoices);
ge_sync_document_lines('invoice_lines','invoice_id',$id,$invoice['lines'] ?? []);
if($sourceOrderId>0) invoice_mark_order_link($sourceOrderId,$id,$invoiceRef);
if($sourceExpeditionId>0) invoice_mark_expedition_link($sourceExpeditionId,$id,$invoiceRef);
redirect_to('index.php?page=invoice_show&id='.$id.'&created_from_order='.($sourceOrderId>0?'1':'0').'&created_from_expedition='.($sourceExpeditionId>0?'1':'0'));
