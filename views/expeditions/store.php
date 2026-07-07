<?php
require __DIR__.'/_helpers.php';
require __DIR__.'/../warehouses/_helpers.php';
$products=data_read('products', []);
$draftLines=[];
foreach(($_POST['product_id']??[]) as $k=>$pid){
    $pid=(int)$pid;
    $qty=ge_parse_number(($_POST['qty']??[])[$k]??0);
    if(!$pid && $qty<=0) continue;
    if(!$pid || $qty<=0) redirect_to('index.php?page=expedition_new&order_id='.(int)($_POST['order_id']??0).'&stock_error='.urlencode('Chaque ligne doit avoir un produit et une quantité positive.'));
    $p=find_row_by_id($products,$pid);
    if(!$p) redirect_to('index.php?page=expedition_new&order_id='.(int)($_POST['order_id']??0).'&stock_error='.urlencode('Produit introuvable dans une ligne.'));
    $draftLines[]=['product_id'=>$pid,'product_ref'=>$p['ref']??'','product_label'=>$p['label']??'','qty'=>$qty,'unit'=>(($_POST['unit']??[])[$k]??'u.')];
}
if(!$draftLines) redirect_to('index.php?page=expedition_new&order_id='.(int)($_POST['order_id']??0).'&stock_error='.urlencode('Ajoute au moins un vrai produit dans les lignes d’expédition.'));

$warehouseId=(int)($_POST['warehouse_id']??0);
if($warehouseId<=0) redirect_to('index.php?page=expedition_new&order_id='.(int)($_POST['order_id']??0).'&stock_error='.urlencode('Choisis un entrepôt source.'));
$rows=expeditions_all();
$id=next_id($rows);
$tiers=tiers_all();
$tier=find_row_by_id($tiers,(int)($_POST['tier_id']??0));
$warehouses=warehouses_all();
$warehouse=find_row_by_id($warehouses,$warehouseId);
$row=['id'=>$id,'ref'=>expedition_ref($id),'order_id'=>(int)($_POST['order_id']??0),'tier_id'=>(int)($_POST['tier_id']??0),'tier_name'=>$tier['name']??'','tier_ref'=>$tier['code_client']??$tier['ref']??'','city'=>$tier['city']??'','zip'=>$tier['zip']??$tier['postal_code']??'','warehouse_id'=>$warehouseId,'warehouse_name'=>$warehouse['name']??'','date'=>$_POST['date']??date('Y-m-d'),'delivery_date'=>$_POST['delivery_date']??'','shipping_method'=>$_POST['shipping_method']??'','tracking'=>trim($_POST['tracking']??''),'status'=>'Brouillon','note_public'=>trim($_POST['note_public']??''),'created_by'=>ge_current_author_name(),'created_by_id'=>ge_current_author_id(),'created_by_username'=>ge_current_author_username(),'created_at'=>date('d/m/Y H:i')];
$rows[]=$row;
data_write('expeditions',$rows);
$lines=data_read('expedition_lines', []);
foreach($draftLines as $line){ $line['id']=next_id($lines); $line['expedition_id']=$id; $lines[]=$line; }
data_write('expedition_lines',$lines);
redirect_to('index.php?page=expedition_show&id='.$id);
