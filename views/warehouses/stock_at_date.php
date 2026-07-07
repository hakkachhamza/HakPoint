<?php require __DIR__.'/_helpers.php'; $title='Stock à date'; include __DIR__.'/../layouts/header.php';
$warehouses=warehouses_all(); $products=data_read('products',[]); $wid=(int)($_GET['warehouse_id']??0); $pid=(int)($_GET['product_id']??0); $date=$_GET['date']??date('Y-m-d'); $future=!empty($_GET['future']);
function ge_stock_date_ts($value){
    $value=trim((string)$value); if($value==='') return 0;
    $formats=['Y-m-d H:i:s','Y-m-d H:i','Y-m-d','d/m/Y H:i','d/m/Y'];
    foreach($formats as $fmt){ $d=DateTime::createFromFormat($fmt,$value); if($d instanceof DateTime) return $d->getTimestamp(); }
    $ts=strtotime($value); return $ts ?: 0;
}
$targetTs=ge_stock_date_ts($date.' 23:59:59');
$currentTs=time();
$targetIsPast=$targetTs>0 && $targetTs<$currentTs;
$base=[];
foreach($products as $p){
    if($pid && (int)($p['id']??0)!==$pid) continue;
    foreach(product_warehouse_stock($p) as $mapWid=>$qty){
        if($wid && (int)$mapWid!==$wid) continue;
        $base[(int)($p['id']??0)][(int)$mapWid]=(float)$qty;
    }
}
if($targetIsPast){
    foreach(data_read('warehouse_movements',[]) as $m){
        $mPid=(int)($m['product_id']??0); $mWid=(int)($m['warehouse_id']??0);
        if($pid && $mPid!==$pid) continue; if($wid && $mWid!==$wid) continue;
        $mTs=ge_stock_date_ts($m['date']??'');
        if($mTs>$targetTs){
            if(!isset($base[$mPid][$mWid])) $base[$mPid][$mWid]=0;
            $base[$mPid][$mWid]-=(float)($m['qty']??0);
        }
    }
}
$rows=[];
foreach($products as $p){
    $pId=(int)($p['id']??0); if($pid && $pId!==$pid) continue;
    foreach(($base[$pId] ?? []) as $mapWid=>$qty){
        if($wid && (int)$mapWid!==$wid) continue;
        $w=warehouse_find($mapWid);
        $rows[]=['product'=>$p,'warehouse'=>$w,'qty'=>$qty];
    }
}
?>
<div class="panel"><h3><?= $future ? 'Stock virtuel à une date future' : 'Stock à date' ?></h3><p class="muted"><?= $targetIsPast ? 'Calculé depuis les mouvements de stock enregistrés jusqu’à la date choisie.' : 'Basé sur le stock actuel et les mouvements déjà enregistrés.' ?></p><form class="dol-form" method="get" action="index.php"><input type="hidden" name="page" value="warehouse_stock_at_date"><input type="hidden" name="future" value="<?= $future ? '1' : '' ?>"><div class="dol-line two"><label>Date</label><input type="date" name="date" value="<?=e($date)?>"><label>Entrepôt</label><select name="warehouse_id"><option value="0">Tous</option><?php foreach($warehouses as $w): ?><option value="<?=(int)$w['id']?>" <?= $wid===(int)$w['id']?'selected':'' ?>><?=e($w['name']??'')?></option><?php endforeach; ?></select></div><div class="dol-line"><label>Produit</label><select name="product_id"><option value="0">Tous</option><?php foreach($products as $p): ?><option value="<?=(int)$p['id']?>" <?= $pid===(int)$p['id']?'selected':'' ?>><?=e(($p['ref']??'').' - '.($p['label']??''))?></option><?php endforeach; ?></select></div><div class="dol-actions"><button class="btn orange" type="submit">RECHERCHER</button></div></form></div>
<div class="panel"><table class="clean-table"><thead><tr><th>Produit</th><th>Entrepôt</th><th class="num">Quantité</th><th class="num">Valeur achat</th><th class="num">Valeur vente</th></tr></thead><tbody><?php if(!$rows): ?><tr><td colspan="5" class="empty-row">Aucun stock trouvé.</td></tr><?php endif; foreach($rows as $r): $p=$r['product']; $qty=(float)$r['qty']; $buy=(float)($p['buy_price']??$p['purchase_price']??0); $sale=(float)($p['sale_price']??0); ?><tr><td><a class="ref" href="index.php?page=product_show&id=<?=(int)($p['id']??0)?>&tab=stock"><?=e(($p['ref']??'').' - '.($p['label']??''))?></a></td><td><?=e($r['warehouse']['name'] ?? '—')?></td><td class="num"><?=e($qty)?></td><td class="num"><?=money($buy*$qty)?></td><td class="num"><?=money($sale*$qty)?></td></tr><?php endforeach; ?></tbody></table></div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
