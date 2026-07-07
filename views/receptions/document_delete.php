<?php
$id=(int)($_GET['id']??0); $rid=(int)($_GET['reception_id']??0);
$docs=data_read('reception_documents', []); $new=[];
foreach($docs as $d){
    if((int)($d['id']??0)===$id){
        $path=$d['path'] ?? (__DIR__.'/../../'.($d['url'] ?? ''));
        if($path && is_file($path)) @unlink($path);
        continue;
    }
    $new[]=$d;
}
data_write('reception_documents', $new);
redirect_to('index.php?page=reception_show&id='.$rid);
