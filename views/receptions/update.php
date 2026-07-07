<?php
require __DIR__.'/_helpers.php';
require __DIR__.'/../warehouses/_helpers.php';
$id=(int)($_POST['id']??0);
$row=reception_find($id);
if(!$row) redirect_to('index.php?page=receptions');
if(!empty($row['stock_done'])) redirect_to('index.php?page=reception_show&id='.$id.'&locked=1');
$warehouseId=(int)($_POST['warehouse_id']??0);
if($warehouseId<=0) redirect_to('index.php?page=reception_edit&id='.$id.'&error='.urlencode('Choisis un entrepôt de destination.'));
$products=data_read('products', []);
$newLines=[];
foreach(($_POST['product_id']??[]) as $k=>$pid){
    $pid=(int)$pid;
    $qty=ge_parse_number(($_POST['qty']??[])[$k]??0);
    if(!$pid && $qty<=0) continue;
    if(!$pid || $qty<=0) redirect_to('index.php?page=reception_edit&id='.$id.'&error='.urlencode('Chaque ligne doit avoir un produit et une quantité positive.'));
    $p=find_row_by_id($products,$pid);
    if(!$p) redirect_to('index.php?page=reception_edit&id='.$id.'&error='.urlencode('Produit introuvable dans une ligne.'));
    $newLines[]=['product_id'=>$pid,'product_ref'=>$p['ref']??'','product_label'=>$p['label']??'','qty'=>$qty,'unit'=>(($_POST['unit']??[])[$k]??($p['unit']??'u.'))];
}
if(!$newLines) redirect_to('index.php?page=reception_edit&id='.$id.'&error='.urlencode('Ajoute au moins un vrai produit dans les lignes de réception.'));
$tiers=tiers_all(); $tier=find_row_by_id($tiers,(int)($_POST['tier_id']??0));
$warehouses=warehouses_all(); $warehouse=find_row_by_id($warehouses,$warehouseId);
$row['tier_id']=(int)($_POST['tier_id']??0);
$row['tier_name']=$tier['name']??'';
$row['warehouse_id']=$warehouseId;
$row['warehouse_name']=$warehouse['name']??'';
$row['date']=$_POST['date']??date('Y-m-d');
$row['order_date']=$_POST['order_date']??'';
$row['method']=$_POST['method']??'';
$row['supplier_doc']=trim($_POST['supplier_doc']??'');
$row['note_public']=trim($_POST['note_public']??'');
$row['updated_at']=date('d/m/Y H:i');
$rows=receptions_all();
foreach($rows as &$r){ if((int)($r['id']??0)===$id){ $r=$row; break; } }
unset($r);
$all=array_values(array_filter(data_read('reception_lines', []), fn($l)=>(int)($l['reception_id']??0)!==$id));
foreach($newLines as $line){ $line['id']=next_id($all); $line['reception_id']=$id; $all[]=$line; }
if(function_exists('data_write_batch')) data_write_batch(['receptions'=>$rows,'reception_lines'=>$all]);
else { data_write('receptions',$rows); data_write('reception_lines',$all); }
redirect_to('index.php?page=reception_show&id='.$id);
