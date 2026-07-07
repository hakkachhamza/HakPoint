<?php
require_once __DIR__.'/_helpers.php';
$invoices=data_read('invoices',[]);
$id=(int)($_GET['id']??0);
$invoice=find_row_by_id($invoices,$id);
if(!$invoice) redirect_to('index.php?page=invoices');
$new=$invoice;
$new['id']=next_id($invoices);
$new['ref']=invoice_ref($new['id']);
$new['status']='Brouillon';
$new['created_at']=date('d/m/Y H:i');
$new['updated_at']=date('d/m/Y H:i');
$new['cloned_from']=$invoice['ref']??'';
$new=array_merge($new, ge_author_fields('created_by'));
$invoices[]=$new;
data_write('invoices',$invoices);
redirect_to('index.php?page=invoice_show&id='.$new['id']);
