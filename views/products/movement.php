<?php
require_once __DIR__.'/../warehouses/_helpers.php';
$id=(int)($_GET['id']??0);
$products=data_read('products',[]);
$idx=-1;
foreach($products as $k=>$p){ if((int)($p['id']??0)===$id){$idx=$k;break;} }
if($idx<0) redirect_to('index.php?page=products');
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    $qty=(float)($_POST['qty']??0);
    $reason=trim((string)($_POST['reason']??'Mouvement manuel'));
    $wid=(int)($_POST['warehouse_id'] ?? ($products[$idx]['warehouse_id'] ?? 0));
    try{
        $result=ge_apply_product_stock_delta($products[$idx], $qty, $wid);
        data_write('products',$products);
        warehouse_record_movement([
            'warehouse_id'=>(int)($result['warehouse_id'] ?? $wid),
            'product_id'=>$id,
            'product_ref'=>$products[$idx]['ref'] ?? '',
            'product_label'=>trim(($products[$idx]['ref']??'').' - '.($products[$idx]['label']??''),' -'),
            'qty'=>$qty,
            'type'=>'Mouvement manuel',
            'note'=>$reason
        ]);
        redirect_to('index.php?page=product_show&id='.$id.'&tab=stock');
    }catch(Throwable $e){
        redirect_to('index.php?page=product_show&id='.$id.'&tab=stock&stock_error='.urlencode($e->getMessage()));
    }
}
$title='Mouvement stock'; include __DIR__.'/../layouts/header.php'; $p=$products[$idx]; $stockMap=product_warehouse_stock($p); $warehouses=warehouses_all(); ?>
<div class="panel"><h2>Mouvement de stock — <?=e($p['label']??'Produit')?></h2><form method="post" class="form-grid"><?=csrf_field()?><label>Entrepôt</label><select name="warehouse_id" required><?php foreach($warehouses as $w): ?><option value="<?=(int)$w['id']?>" <?=((int)($p['warehouse_id']??0)===(int)$w['id'])?'selected':''?>><?=e($w['name']??'')?></option><?php endforeach; ?></select><label>Quantité (+ entrée / - sortie)</label><input type="number" step="0.001" name="qty" required><label>Motif</label><input name="reason" value="Correction stock"><div></div><button class="btn orange">ENREGISTRER</button></form></div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
