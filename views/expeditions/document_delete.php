<?php
$id=(int)($_GET['id']??0); $eid=(int)($_GET['expedition_id']??0);
$docs=data_read('expedition_documents', []); $new=[];
foreach($docs as $d){
    if((int)($d['id']??0)===$id){
        $path=$d['path'] ?? (__DIR__.'/../../'.($d['url'] ?? ''));
        if($path && is_file($path)) @unlink($path);
        continue;
    }
    $new[]=$d;
}
data_write('expedition_documents', $new);
redirect_to('index.php?page=expedition_show&id='.$eid);
