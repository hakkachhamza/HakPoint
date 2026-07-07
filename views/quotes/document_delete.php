<?php
$id=(int)($_GET['id']??0); $docId=(int)($_GET['doc_id']??0); $docs=data_read('quote_documents',[]); $new=[]; foreach($docs as $d){ if((int)($d['id']??0)===$docId && (int)($d['quote_id']??0)===$id){ if(!empty($d['path']) && file_exists($d['path'])) @unlink($d['path']); continue; } $new[]=$d; } data_write('quote_documents',$new); redirect_to('index.php?page=quote_show&id='.$id);
