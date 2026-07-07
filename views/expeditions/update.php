<?php
require __DIR__.'/_helpers.php';
require __DIR__.'/../warehouses/_helpers.php';
$id=(int)($_POST['id']??0);
$rows=expeditions_all();
$idx=-1;
foreach($rows as $k=>$r){ if((int)($r['id']??0)===$id){ $idx=$k; break; } }
if($idx<0) redirect_to('index.php?page=expeditions');
if(!empty($rows[$idx]['stock_done'])) redirect_to('index.php?page=expedition_show&id='.$id.'&stock_error='.urlencode('Stock déjà traité. Annule l’expédition avant de modifier les lignes.'));

$products=data_read('products', []);
$newLines=[];
foreach(($_POST['product_id']??[]) as $k=>$pid){
    $pid=(int)$pid;
    $qty=ge_parse_number(($_POST['qty']??[])[$k]??0);
    if(!$pid && $qty<=0) continue;
    if(!$pid || $qty<=0) redirect_to('index.php?page=expedition_edit&id='.$id.'&stock_error='.urlencode('Chaque ligne doit avoir un produit et une quantité positive.'));
    $p=find_row_by_id($products,$pid);
    if(!$p) redirect_to('index.php?page=expedition_edit&id='.$id.'&stock_error='.urlencode('Produit introuvable dans une ligne.'));
    $newLines[]=['product_id'=>$pid,'product_ref'=>$p['ref']??'','product_label'=>$p['label']??'','qty'=>$qty,'unit'=>(($_POST['unit']??[])[$k]??'u.')];
}
if(!$newLines) redirect_to('index.php?page=expedition_edit&id='.$id.'&stock_error='.urlencode('Ajoute au moins un vrai produit dans les lignes d’expédition.'));

$warehouseId=(int)($_POST['warehouse_id']??0);
if($warehouseId<=0) redirect_to('index.php?page=expedition_edit&id='.$id.'&stock_error='.urlencode('Choisis un entrepôt source.'));
$warehouses=warehouses_all();
$warehouse=find_row_by_id($warehouses,$warehouseId);
$tiers=tiers_all();
$tier=find_row_by_id($tiers,(int)($_POST['tier_id']??0));
foreach(['date','delivery_date','shipping_method','tracking','note_public'] as $f){ $rows[$idx][$f]=$_POST[$f]??($rows[$idx][$f]??''); }
$rows[$idx]['tier_id']=(int)($_POST['tier_id']??0);
$rows[$idx]['tier_name']=$tier['name']??($rows[$idx]['tier_name']??'');
$rows[$idx]['warehouse_id']=$warehouseId;
$rows[$idx]['warehouse_name']=$warehouse['name']??'';
$rows[$idx]['updated_at']=date('d/m/Y H:i');
data_write('expeditions',$rows);
$all=array_values(array_filter(data_read('expedition_lines', []), fn($l)=>(int)($l['expedition_id']??0)!==$id));
foreach($newLines as $line){ $line['id']=next_id($all); $line['expedition_id']=$id; $all[]=$line; }
data_write('expedition_lines',$all);
redirect_to('index.php?page=expedition_show&id='.$id);
