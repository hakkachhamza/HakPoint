<?php
require_once __DIR__.'/_helpers.php';
$id=(int)($_POST['id']??0);
$invoices=data_read('invoices',[]);
$idx=invoice_find_index($invoices,$id);
if($idx<0) redirect_to('index.php?page=invoices');
$inv=&$invoices[$idx];
$amount=ge_parse_number($_POST['amount'] ?? 0);
if($amount>0){
    if(!isset($inv['payments']) || !is_array($inv['payments'])) $inv['payments']=[];
    $inv['payments'][]=[
        'ref'=>'PAY'.date('ym').'-'.str_pad(count($inv['payments'])+1,4,'0',STR_PAD_LEFT),
        'date'=>$_POST['date']??date('d/m/Y H:i'),
        'type'=>$_POST['type']??($inv['payment_mode']??'Chèque'),
        'bank'=>$_POST['bank']??($inv['bank_account']??'001'),
        'amount'=>$amount
    ];
    [$ht,$tva,$ttc]=invoice_totals($inv);
    $paid=0; foreach($inv['payments'] as $p){ $paid+=(float)($p['amount']??0); }
    if($paid >= $ttc && $ttc>0) $inv['status']='Payée'; else $inv['status']='Impayée';
    $inv['updated_at']=date('d/m/Y H:i');
}
$invoiceCopy = $inv;
unset($inv); data_write('invoices',$invoices);
// Mirror the internal payment into the global finance module so bank/accounting reports are connected.
if($amount>0){
    $payments=data_read('payments', []);
    $pid=next_id($payments);
    $payments[]=[
        'id'=>$pid,
        'ref'=>'PC'.date('ymd').'-'.str_pad((string)$pid,5,'0',STR_PAD_LEFT),
        'invoice_id'=>$id,
        'invoice_ref'=>$invoiceCopy['ref'] ?? '',
        'client_id'=>(int)($invoiceCopy['client_id'] ?? $invoiceCopy['tier_id'] ?? 0) ?: null,
        'client_name'=>$invoiceCopy['client_name'] ?? $invoiceCopy['tier_name'] ?? $invoiceCopy['client'] ?? '',
        'date'=>date('Y-m-d'),
        'amount'=>$amount,
        'mode'=>$_POST['type'] ?? ($invoiceCopy['payment_mode'] ?? 'Manuel'),
        'status'=>'Confirmé',
        'note'=>'Paiement ajouté depuis la facture client',
        'created_at'=>date('Y-m-d H:i:s')
    ];
    data_write('payments',$payments,false);
    if(function_exists('ge_finance_update_invoice_payment')) ge_finance_update_invoice_payment($id);
    if(function_exists('ge_erp_sync_finance_payment_side_effects')) ge_erp_sync_finance_payment_side_effects('payments', end($payments));
}
redirect_to('index.php?page=invoice_show&id='.$id.'&payment_added=1');
