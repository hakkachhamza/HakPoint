<?php include __DIR__.'/../../../app/tab_vars.php';
$pid=(int)($product['id'] ?? 0); $soldQty=0; $soldAmount=0; $orderedQty=0; $quotedQty=0;
foreach(data_read('quotes',[]) as $doc){ foreach(($doc['lines']??[]) as $l){ if((int)($l['product_id']??0)===$pid){ $quotedQty+=(float)($l['qty']??0); } } }
foreach(data_read('orders',[]) as $doc){ foreach(($doc['lines']??[]) as $l){ if((int)($l['product_id']??0)===$pid){ $orderedQty+=(float)($l['qty']??0); } } }
foreach(data_read('invoices',[]) as $doc){ foreach(($doc['lines']??[]) as $l){ if((int)($l['product_id']??0)===$pid){ $soldQty+=(float)($l['qty']??0); $soldAmount+=(float)($l['total_ht']??0); } } }
$potentialValue=$physical*$sale;
?>
<div class="stats-grid four">
  <div class="stat-card"><span>Quantité devisée</span><b><?=e($quotedQty)?></b></div>
  <div class="stat-card"><span>Quantité commandée</span><b><?=e($orderedQty)?></b></div>
  <div class="stat-card"><span>Quantité facturée</span><b><?=e($soldQty)?></b></div>
  <div class="stat-card"><span>CA facturé HT</span><b><?=money($soldAmount)?> MAD</b></div>
</div>
<table class="dol-table"><thead><tr><th>Indicateur</th><th class="num">Valeur</th></tr></thead><tbody><tr><td>Stock physique</td><td class="num"><?=e($physical)?></td></tr><tr><td>Valeur de vente du stock</td><td class="num"><?=money($potentialValue)?> MAD</td></tr><tr><td>Prix de vente moyen</td><td class="num"><?=money($sale)?> MAD</td></tr><tr><td>Prix d’achat moyen</td><td class="num"><?=money($buy)?> MAD</td></tr></tbody></table>
