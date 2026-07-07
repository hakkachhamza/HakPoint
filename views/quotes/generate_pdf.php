<?php
require_once __DIR__.'/_helpers.php';
require_once __DIR__.'/../../app/pdf_docs.php';
$id=(int)($_GET['id']??0); $quote=find_row_by_id(data_read('quotes',[]),$id); if(!$quote) redirect_to('index.php?page=quotes');
$ref=$quote['ref']??quote_ref($id); $client=trim((string)($quote['client']??'')); $lines=$quote['lines']??[];
$dates=['Date de proposition'=>($quote['proposal_date']??''),'Date de fin de validité'=>($quote['end_date']??$quote['validity_end']??'')];
if(!empty($_GET['download'])){ $info=ge_pdf_money_doc($quote,'Devis',$ref,$client,$dates,$lines,__DIR__.'/../../uploads/quotes','Devis','quote_id','quote_show','quote_documents',$id,true); ge_send_file_download($info['path'] ?? '', $info['filename'] ?? 'devis.pdf', 'application/pdf'); }
ge_pdf_money_doc($quote,'Devis',$ref,$client,$dates,$lines,__DIR__.'/../../uploads/quotes','Devis','quote_id','quote_show','quote_documents',$id);
