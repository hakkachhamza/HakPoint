<?php
$ids=array_map('intval',$_POST['invoice_ids']??[]);
$action=$_POST['bulk_action']??'';
$invoices=data_read('invoices',[]);
if($ids && $action){
    if($action==='send_email' && count($ids)===1) redirect_to('index.php?page=invoice_email&id='.$ids[0]);
    if($action==='generate_pdf' && count($ids)===1) redirect_to(csrf_url('index.php?page=invoice_pdf_generate&id='.$ids[0]));
    if($action==='delete'){
        $invoices=array_values(array_filter($invoices,fn($i)=>!in_array((int)($i['id']??0),$ids,true)));
    } else {
        foreach($invoices as &$i){ if(in_array((int)($i['id']??0),$ids,true)){
            if($action==='mark_unpaid') $i['status']='Impayée';
            if($action==='mark_paid') $i['status']='Payée';
            if($action==='abandon') $i['status']='Abandonnée';
            $i['updated_at']=date('d/m/Y H:i');
        }} unset($i);
    }
    data_write('invoices',$invoices);
}
redirect_to('index.php?page=invoices');
