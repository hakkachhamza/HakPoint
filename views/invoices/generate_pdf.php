<?php
require_once __DIR__.'/_helpers.php';
require_once __DIR__.'/../../app/pdf_docs.php';
$id=(int)($_GET['id']??0); $invoice=find_row_by_id(data_read('invoices',[]),$id); if(!$invoice) redirect_to('index.php?page=invoices');
$ref=$invoice['ref']??invoice_ref($id); $client=trim((string)($invoice['client']??'')); $lines=$invoice['lines']??[];
$dates=['Date facturation'=>($invoice['invoice_date']??''),'Date échéance'=>($invoice['due_date']??'')];
if(!empty($invoice['order_id'])) $dates['Commande source']=$invoice['order_ref'] ?? ('#'.$invoice['order_id']);
if(!empty($_GET['download'])){ $info=ge_pdf_money_doc($invoice,'Facture',$ref,$client,$dates,$lines,__DIR__.'/../../uploads/invoices','Facture','invoice_id','invoice_show','invoice_documents',$id,true); ge_send_file_download($info['path'] ?? '', $info['filename'] ?? 'facture.pdf', 'application/pdf'); }
ge_pdf_money_doc($invoice,'Facture',$ref,$client,$dates,$lines,__DIR__.'/../../uploads/invoices','Facture','invoice_id','invoice_show','invoice_documents',$id);
