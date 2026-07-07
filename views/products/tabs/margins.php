<?php include __DIR__.'/../../../app/tab_vars.php';
$margin=$sale-$buy; $rate=$sale>0 ? ($margin/$sale)*100 : 0; $stockMargin=$margin*$physical;
?>
<div class="dol-section"><div class="dol-lines full"><div><span>Marge unitaire HT</span><b><?=money($margin)?> MAD</b></div><div><span>Taux de marge</span><b><?=number_format($rate,2,',',' ')?>%</b></div><div><span>Marge potentielle sur stock</span><b><?=money($stockMargin)?> MAD</b></div></div></div>
<table class="dol-table"><thead><tr><th>Prix de vente HT</th><th>Prix de revient HT</th><th>Marge HT</th><th>Taux</th><th>Stock</th><th>Marge stock</th></tr></thead><tbody><tr><td><?=money($sale)?></td><td><?=money($buy)?></td><td><?=money($margin)?></td><td><?=number_format($rate,2,',',' ')?>%</td><td><?=e($physical)?></td><td><?=money($stockMargin)?></td></tr></tbody></table>
