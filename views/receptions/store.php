<?php
require __DIR__.'/_helpers.php';
require __DIR__.'/../warehouses/_helpers.php';
$products=data_read('products', []);
$draftLines=[];
foreach(($_POST['product_id']??[]) as $k=>$pid){
    $pid=(int)$pid;
    $qty=ge_parse_number(($_POST['qty']??[])[$k]??0);
    if(!$pid && $qty<=0) continue;
    if(!$pid || $qty<=0) redirect_to('index.php?page=reception_new&error='.urlencode('Chaque ligne doit avoir un produit et une quantité positive.'));
    $p=find_row_by_id($products,$pid);
    if(!$p) redirect_to('index.php?page=reception_new&error='.urlencode('Produit introuvable dans une ligne.'));
    $draftLines[]=['product_id'=>$pid,'product_ref'=>$p['ref']??'','product_label'=>$p['label']??'','qty'=>$qty,'unit'=>(($_POST['unit']??[])[$k]??($p['unit']??'u.'))];
}
if(!$draftLines) redirect_to('index.php?page=reception_new&error='.urlencode('Ajoute au moins un vrai produit dans les lignes de réception.'));
$warehouseId=(int)($_POST['warehouse_id']??0);
if($warehouseId<=0) redirect_to('index.php?page=reception_new&error='.urlencode('Choisis un entrepôt de destination.'));

$rows=receptions_all();
$id=next_id($rows);
$tiers=tiers_all();
$tier=find_row_by_id($tiers,(int)($_POST['tier_id']??0));
$warehouses=warehouses_all();
$warehouse=find_row_by_id($warehouses,$warehouseId);
$row=['id'=>$id,'ref'=>reception_ref($id),'tier_id'=>(int)($_POST['tier_id']??0),'tier_name'=>$tier['name']??'','warehouse_id'=>$warehouseId,'warehouse_name'=>$warehouse['name']??'','date'=>$_POST['date']??date('Y-m-d'),'order_date'=>$_POST['order_date']??'','method'=>$_POST['method']??'','supplier_doc'=>trim($_POST['supplier_doc']??''),'status'=>'Brouillon','note_public'=>trim($_POST['note_public']??''),'created_by'=>ge_current_author_name(),'created_by_id'=>ge_current_author_id(),'created_by_username'=>ge_current_author_username(),'created_at'=>date('d/m/Y H:i')];
$rows[]=$row;
$lines=data_read('reception_lines', []);
foreach($draftLines as $line){ $line['id']=next_id($lines); $line['reception_id']=$id; $lines[]=$line; }
if(function_exists('data_write_batch')) data_write_batch(['receptions'=>$rows,'reception_lines'=>$lines]);
else { data_write('receptions',$rows); data_write('reception_lines',$lines); }
redirect_to('index.php?page=reception_show&id='.$id);
