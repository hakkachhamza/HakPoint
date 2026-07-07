<?php
$id=(int)($_GET['id']??0);
$status=$_GET['status']??'Impayée';
$allowed=['Brouillon','Impayée','Payée','Abandonnée'];
if(!in_array($status,$allowed,true)) $status='Impayée';
$invoices=data_read('invoices',[]);
foreach($invoices as &$i){ if((int)($i['id']??0)===$id){ $i['status']=$status; $i['updated_at']=date('d/m/Y H:i'); }} unset($i);
data_write('invoices',$invoices);
if(function_exists('ge_erp_post_customer_invoice_accounting')) ge_erp_post_customer_invoice_accounting($id);
if($status==='Payée' && function_exists('ge_erp_mark_customer_invoice_paid')) ge_erp_mark_customer_invoice_paid($id);
elseif(function_exists('ge_erp_recalculate_invoice_balance')) ge_erp_recalculate_invoice_balance($id);
redirect_to('index.php?page=invoice_show&id='.$id);
