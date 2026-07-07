<?php
require __DIR__.'/_helpers.php';
$wid=(int)($_GET['id']??0);
$pid=(int)($_GET['product_id']??0);
$w=$wid?warehouse_find($wid):null;
$title='Mouvements de stock';
include __DIR__.'/../layouts/header.php';
$moves=data_read('warehouse_movements',[]);
if($wid) $moves=array_values(array_filter($moves,fn($m)=>(int)($m['warehouse_id']??0)===$wid));
if($pid) $moves=array_values(array_filter($moves,fn($m)=>(int)($m['product_id']??0)===$pid));
$product=$pid?find_row_by_id(data_read('products',[]),$pid):null;
$moves=array_reverse($moves);
[$moves,$movesTotal,$movesPage,$movesPages]=ge_list_paginate_current($moves);
?>
<div class="clean-list-page warehouse-moves-page">
 <div class="clean-list-head"><div class="clean-title"><i class="fa-solid fa-right-left text-gold"></i><span><?=e($product ? (($product['ref']??'').' - '.($product['label']??'')) : ($w['name']??'Tous les entrepôts'))?> (<?=e($movesTotal)?>)</span></div><div class="clean-tools"><span class="clean-page"><?=e($movesPage)?> / <?=e($movesPages)?></span><a class="btn orange" href="index.php?page=warehouse_adjust<?= $wid?'&warehouse_id='.$wid:'' ?><?= $pid?'&product_id='.$pid:'' ?>">CORRIGER LE STOCK</a><a class="btn orange" href="index.php?page=warehouse_transfer<?= $wid?'&warehouse_id='.$wid:'' ?><?= $pid?'&product_id='.$pid:'' ?>">TRANSFÉRER STOCK</a></div></div>
 <div class="clean-table-box"><table class="clean-table"><thead><tr><th>Date</th><th>Type</th><th>Entrepôt</th><th>Produit</th><th>Qté</th><th>Note</th><th>Par</th></tr></thead><tbody><?php if(!$movesTotal): ?><tr><td colspan="7" class="empty-row">Aucun mouvement</td></tr><?php endif; foreach($moves as $m): $wh=warehouse_find($m['warehouse_id']??0); ?><tr><td><?=e($m['date']??'')?></td><td><?=e($m['type']??'')?></td><td><?=e($wh['name']??'')?></td><td><?=e($m['product_label']??'')?></td><td class="num"><?=e($m['qty']??0)?></td><td><?=e($m['note']??'')?></td><td><?=e(ge_record_author($m,'user'))?></td></tr><?php endforeach; ?></tbody></table></div>
 <?=ge_list_pager($movesTotal,$movesPage,$movesPages,'p',['page'=>'warehouse_movements'] + ($wid?['id'=>$wid]:[]) + ($pid?['product_id'=>$pid]:[]))?>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
