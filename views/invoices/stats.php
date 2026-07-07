<?php
require_once __DIR__.'/_helpers.php';
$title='Statistiques factures'; include __DIR__.'/../layouts/header.php';
$invoices=data_read('invoices',[]);
$counts=['Brouillon'=>0,'Impayée'=>0,'Payée'=>0,'Abandonnée'=>0]; $total=0;
foreach($invoices as $i){ $s=$i['status']??'Brouillon'; if(!isset($counts[$s])) $counts[$s]=0; $counts[$s]++; [$h,$t,$ttc]=invoice_totals($i); $total+=$ttc; }
?>
<div class="panel"><h2>Statistiques factures</h2><div class="dashboard-grid">
<?php foreach($counts as $label=>$n): ?><div class="dash-card"><div class="dash-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div><div><span><?=e($label)?></span><b><?=$n?></b><small>Factures</small></div></div><?php endforeach; ?>
<div class="dash-card"><div class="dash-icon"><i class="fa-solid fa-coins"></i></div><div><span>Total TTC</span><b><?=money($total)?></b><small>MAD</small></div></div>
</div></div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
