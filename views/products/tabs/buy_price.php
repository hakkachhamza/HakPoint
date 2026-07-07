<?php include __DIR__.'/../../../app/tab_vars.php';
$purchaseRows=$product['purchase_prices']??[];
if(!is_array($purchaseRows)) $purchaseRows=[];
$supplierName = $purchaseRows[0]['supplier_name'] ?? ($product['supplier_name'] ?? '—');
$supplierRef = $purchaseRows[0]['supplier_ref'] ?? ($product['supplier_ref'] ?? '');
if(!$purchaseRows){
  $purchaseRows[]=[
    'date'=>$product['updated_at'] ?? $product['created_at'] ?? date('d/m/Y H:i'),
    'supplier_name'=>$supplierName,
    'supplier_ref'=>$supplierRef,
    'min_qty'=>1,
    'tax'=>$taxRate,
    'price'=>$buy,
    'unit_price'=>$buy,
    'discount'=>0
  ];
}
?>
<div class="dol-section two-cols">
  <div class="dol-lines">
    <div><span>Type</span><b><?=e($product['type'] ?? 'Produit')?></b></div>
    <div><span>Prix de revient <em>ⓘ</em></span><b><?=money($buy)?> MAD HT</b></div>
    <div><span>Prix moyen pondéré (PMP) <em>ⓘ</em></span><b><?=money($buy)?> MAD HT</b></div>
    <div><span>Meilleur prix d'achat</span><b><?=money($buy)?> MAD HT <small>(Fournisseur: 🏢 <?=e($supplierName)?><?= $supplierRef!=='' ? ' / Réf. produit fournisseur: '.e($supplierRef) : '' ?>)</small></b></div>
  </div>
</div>
<div class="right-actions"><button type="button" class="orange" data-open-modal="purchasePriceModal">AJOUTER UN PRIX D'ACHAT</button></div>
<div class="dol-icon-line"><span class="small-icon money">$=</span><span class="count">(<?=count($purchaseRows)?>)</span></div>
<table class="dol-table">
  <thead><tr><th>Pratiqués à partir du</th><th>Fournisseurs</th><th>Réf. produit fournisseur</th><th>Qté achat minimum</th><th>Taux TVA</th><th>Prix quantité min. HT</th><th>Prix unitaire HT</th><th>Remise pour cette qté.</th><th></th></tr></thead>
  <tbody>
    <?php foreach($purchaseRows as $r): ?>
      <tr><td><?=e($r['date']??'')?></td><td>🏢 <?=e($r['supplier_name']??'—')?></td><td><?=e($r['supplier_ref']??'')?></td><td><?=e($r['min_qty']??1)?> <?=e($unit)?></td><td><?=e($r['tax']??$taxRate)?>%</td><td><?=money($r['price']??0)?></td><td><?=money($r['unit_price']??0)?></td><td><?=e($r['discount']??0)?>%</td><td></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__.'/../modals/purchase_price_modal.php'; ?>
