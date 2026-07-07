<?php $title='Statistiques Commandes'; include __DIR__.'/../layouts/header.php'; $orders=data_read('orders',[]); $total=count($orders); $amount=array_sum(array_map(fn($o)=>(float)($o['total_ht']??0),$orders)); ?>
<div class="dashboard-grid"><div class="card"><h3>Total commandes</h3><div class="big-num"><?=$total?></div></div><div class="card"><h3>Montant HT</h3><div class="big-num"><?=money($amount)?></div></div></div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
