<?php
require __DIR__.'/_helpers.php';
$wid=(int)($_POST['warehouse_id']??0); $pid=(int)($_POST['product_id']??0); $newQty=max(0,(int)($_POST['qty']??0));
$products=data_read('products',[]); $label=''; $delta=0; $ok=false;
foreach($products as &$p){
    if((int)($p['id']??0)===$pid){
        $label=($p['ref']??'').' - '.($p['label']??'');
        $stock=product_warehouse_stock($p);
        $old=(int)($stock[$wid] ?? 0);
        $delta=$newQty-$old;
        $stock[$wid]=$newQty;
        save_product_warehouse_stock($p,$stock);
        $ok=true; break;
    }
}
if($ok){ data_write('products',$products); warehouse_record_movement(['warehouse_id'=>$wid,'product_id'=>$pid,'product_label'=>$label,'qty'=>$delta,'type'=>'Correction','note'=>trim($_POST['note']??'')]); }
redirect_to('index.php?page=warehouse_show&id='.$wid);
