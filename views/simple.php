<?php $map=['clients'=>'Clients','suppliers'=>'Fournisseurs','warehouses'=>'Entrepôts','quotes'=>'Devis','invoices'=>'Factures']; $title=$map[current_page()]??'Page'; include __DIR__.'/layouts/header.php'; ?>
<div class="panel"><h2><?=e($title)?></h2><p>Module séparé prêt à développer. Structure propre: chaque module a son fichier.</p></div>
<?php include __DIR__.'/layouts/footer.php'; ?>
