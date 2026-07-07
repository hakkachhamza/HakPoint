<?php include __DIR__.'/../../../app/tab_vars.php'; require_once __DIR__.'/../../warehouses/_helpers.php';
$warehouses=warehouses_all(); $stockMap=product_warehouse_stock($product); $firstWid=(int)($product['warehouse_id'] ?? 0); if($firstWid<=0 && $stockMap){ $keys=array_keys($stockMap); $firstWid=(int)$keys[0]; } if($firstWid<=0 && $warehouses){ $firstWid=(int)($warehouses[0]['id'] ?? 0); }
$movements=data_read('warehouse_movements',[]); $lastMove=null;
foreach(array_reverse($movements) as $m){ if((int)($m['product_id']??0)===(int)($product['id']??0)){ $lastMove=$m; break; }}
$supplierName=$product['supplier_name'] ?? (($product['purchase_prices'][0]['supplier_name'] ?? '—'));
$supplierRef=$product['supplier_ref'] ?? (($product['purchase_prices'][0]['supplier_ref'] ?? ''));
?>
<div class="dol-section two-cols">
  <div class="dol-lines">
    <div><span>Type</span><b><?=e($product['type'] ?? 'Produit')?></b></div>
    <div><span>Prix de revient <em>ⓘ</em></span><b><?=money($buy)?> MAD HT</b></div>
    <div><span>Prix moyen pondéré (PMP) <em>ⓘ</em></span><b><?=money($buy)?> MAD HT</b></div>
    <div><span>Meilleur prix d'achat</span><b><?=money($buy)?> MAD HT <small>(Fournisseur: 🏢 <?=e($supplierName)?><?= $supplierRef!=='' ? ' / Réf. produit fournisseur: '.e($supplierRef) : '' ?>)</small></b></div>
    <div><span>Prix de vente</span><b><?=money($sale)?> HT</b></div>
    <div><span>Prix de vente min.</span><b><?=money($product['sale_price_min'] ?? 0)?> HT</b></div>
  </div>
  <div class="dol-lines">
    <div><span>Limite stock pour alerte <em>ⓘ</em></span><b><?=e($alert)?></b></div>
    <div><span>Stock désiré optimal <em>ⓘ</em></span><b><?=e($desired)?></b></div>
    <div><span>Stock physique <em>ⓘ</em></span><b><?=e($physical)?> <?= $physical<=0?'⚠️':'' ?> <a href="index.php?page=warehouse_stock_at_date&product_id=<?=(int)$product['id']?><?= $firstWid ? '&warehouse_id='.$firstWid : '' ?>">Stock à date</a></b></div>
    <div><span>Stock virtuel <em>ⓘ</em></span><b><?=e($virtual)?> <?= $virtual<0?'⚠️':'' ?> <a href="index.php?page=warehouse_stock_at_date&future=1&product_id=<?=(int)$product['id']?><?= $firstWid ? '&warehouse_id='.$firstWid : '' ?>">Stock virtuel à une date future</a></b></div>
    <div><span>Dernier mouvement</span><b><?php if($lastMove): ?><?=e($lastMove['date']??'')?> <a href="index.php?page=warehouse_movements&product_id=<?=(int)$product['id']?>">Liste complète</a><?php else: ?>Aucun mouvement<?php endif; ?></b></div>
  </div>
</div>
<div class="right-actions"><a class="btn orange" href="index.php?page=warehouse_transfer&product_id=<?=(int)$product['id']?><?= $firstWid ? '&warehouse_id='.$firstWid : '' ?>">TRANSFÉRER STOCK</a><a class="btn orange" href="index.php?page=warehouse_adjust&product_id=<?=(int)$product['id']?><?= $firstWid ? '&warehouse_id='.$firstWid : '' ?>">CORRIGER LE STOCK</a></div>
<table class="dol-table stock-table">
  <thead><tr><th>Entrepôt</th><th>Nombre de pièces</th><th>Prix moyen pondéré (PMP) ⓘ</th><th>Valorisation achat (PMP)</th><th>Prix de vente unitaire</th><th>Valeur à la vente</th></tr></thead>
  <tbody>
    <?php if(!$stockMap): ?><tr><td colspan="6" class="empty-row">Aucun stock enregistré pour ce produit.</td></tr><?php endif; ?>
    <?php foreach($stockMap as $wid=>$qty): $w=warehouse_find($wid); $qty=(float)$qty; ?>
      <tr><td><i class="fa-solid fa-warehouse"></i> <?=e($w['name'] ?? ('Entrepôt #'.$wid))?></td><td><?=e($qty)?> <?= $qty<=0?'⚠️':'' ?></td><td><?=money($buy)?></td><td><?=money($buy*$qty)?></td><td><?=money($sale)?></td><td><?=money($sale*$qty)?></td></tr>
    <?php endforeach; ?>
    <tr class="total"><td>Total:</td><td><?=e($physical)?></td><td></td><td><?=money($buy*$physical)?></td><td></td><td><?=money($sale*$physical)?></td></tr>
  </tbody>
</table>
