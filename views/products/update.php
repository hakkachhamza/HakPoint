<?php
require __DIR__.'/_save_helpers.php';
$id=(int)($_GET['id']??0);
$products=data_read('products',[]);
$found=false;
foreach($products as &$p){
    if((int)$p['id']===$id){ $p=product_payload($id,$p); $found=true; break; }
}
unset($p);
if($found) data_write('products',$products);
redirect_to($found ? product_url($id) : 'index.php?page=products');
