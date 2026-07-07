<?php
require __DIR__.'/_helpers.php';
require_once __DIR__.'/../../app/pdf_docs.php';
$id=(int)($_GET['id']??0); $r=expedition_find($id); if(!$r) redirect_to('index.php?page=expeditions');
ge_pdf_stock_doc($r, expedition_lines($id), 'Bon d\'expédition', 'Client', 'Entrepôt source', __DIR__.'/../../uploads/expeditions', 'Expedition', 'expedition_id', 'expedition_show', 'expedition_documents', $id);
