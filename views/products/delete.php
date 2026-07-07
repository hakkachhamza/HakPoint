<?php
$id=(int)($_GET['id']??0);
$products=data_read('products',[]);
$new=[];$img='';
foreach($products as $p){ if((int)$p['id']===$id){ $img=$p['image']??''; continue; } $new[]=$p; }
if($img){ $path=__DIR__.'/../../uploads/products/'.$img; if(is_file($path)) @unlink($path); }
data_write('products',$new);
redirect_to('index.php?page=products');
