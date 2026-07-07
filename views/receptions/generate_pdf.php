<?php
require __DIR__.'/_helpers.php';
require_once __DIR__.'/../../app/pdf_docs.php';
$id=(int)($_GET['id']??0); $r=reception_find($id); if(!$r) redirect_to('index.php?page=receptions');
ge_pdf_stock_doc($r, reception_lines($id), 'Bon de réception', 'Fournisseur', 'Entrepôt destination', __DIR__.'/../../uploads/receptions', 'Reception', 'reception_id', 'reception_show', 'reception_documents', $id);
