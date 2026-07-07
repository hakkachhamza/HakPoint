<?php
require __DIR__.'/_helpers.php';
$from=(int)($_POST['from_warehouse_id']??0); $to=(int)($_POST['to_warehouse_id']??0); $pid=(int)($_POST['product_id']??0); $qty=max(0,(int)($_POST['qty']??0));
if($from<=0 || $to<=0 || $pid<=0 || $qty<=0 || $from===$to) redirect_to('index.php?page=warehouse_transfer&warehouse_id='.$from.'&product_id='.$pid.'&error=1');
$products=data_read('products',[]); $label=''; $ok=false;
foreach($products as &$p){
    if((int)($p['id']??0)===$pid){
        $label=($p['ref']??'').' - '.($p['label']??'');
        $stock=product_warehouse_stock($p);
        $available=(int)($stock[$from] ?? 0);
        if($available < $qty) redirect_to('index.php?page=warehouse_transfer&warehouse_id='.$from.'&product_id='.$pid.'&error=stock');
        $stock[$from]=$available-$qty;
        $stock[$to]=(int)($stock[$to] ?? 0)+$qty;
        save_product_warehouse_stock($p,$stock);
        $ok=true; break;
    }
}
if($ok){
    data_write('products',$products);
    warehouse_record_movement(['warehouse_id'=>$from,'product_id'=>$pid,'product_label'=>$label,'qty'=>-$qty,'type'=>'Transfert sortie','note'=>trim($_POST['note']??'')]);
    warehouse_record_movement(['warehouse_id'=>$to,'product_id'=>$pid,'product_label'=>$label,'qty'=>$qty,'type'=>'Transfert entrée','note'=>trim($_POST['note']??'')]);
}
redirect_to('index.php?page=warehouse_show&id='.$to);
