<?php
require __DIR__.'/_helpers.php';
require __DIR__.'/../warehouses/_helpers.php';

$id = (int)($_GET['id'] ?? 0);
$edit = $id ? expedition_find($id) : null;
$orderId = $edit ? (int)($edit['order_id'] ?? 0) : (int)($_GET['order_id'] ?? 0);
$order = null;
if ($orderId > 0) {
    $order = find_row_by_id(data_read('orders', []), $orderId);
}

$title = $edit ? 'Modifier expédition' : 'Nouvelle expédition';
include __DIR__.'/../layouts/header.php';

$tiers = tiers_all();
$products = data_read('products', []);
$warehouses = warehouses_all();
$lines = $edit ? expedition_lines($id) : [];

$orderLinesForShipment = [];
if (!$edit && $order) {
    if (!empty($order['lines']) && is_array($order['lines'])) {
        $orderLinesForShipment = $order['lines'];
    } else {
        $orderLinesForShipment = array_values(array_filter(data_read('order_lines', []), fn($l)=>(int)($l['order_id']??0)===(int)$orderId));
    }
}
if (!$edit && $order && $orderLinesForShipment) {
    foreach ($orderLinesForShipment as $ol) {
        $description = trim((string)($ol['description'] ?? ''));
        $matchedProductId = (int)($ol['product_id'] ?? 0);
        $hintRef = trim((string)($ol['product_ref'] ?? ''));
        $hintLabel = trim((string)($ol['product_label'] ?? ''));
        $searchText = trim($description.' '.$hintRef.' '.$hintLabel);
        if(!$matchedProductId){
            foreach ($products as $p) {
                $ref = trim((string)($p['ref'] ?? ''));
                $label = trim((string)($p['label'] ?? ''));
                if (($ref !== '' && stripos($searchText, $ref) !== false) || ($label !== '' && stripos($searchText, $label) !== false)) {
                    $matchedProductId = (int)($p['id'] ?? 0);
                    break;
                }
            }
        }
        $lines[] = [
            'product_id' => $matchedProductId,
            'qty' => $ol['qty'] ?? 1,
            'unit' => $ol['unit'] ?? 'u.',
            'description' => $description,
            'needs_product' => $matchedProductId ? 0 : 1,
        ];
    }
}
// Nouvelle expédition shows only one empty line by default.
// If the expédition is created from a commande, keep the real commande lines only.
while (count($lines) < 1) $lines[] = [];

$prefillTierId = (int)($edit['tier_id'] ?? 0);
if (!$prefillTierId && $order) {
    $prefillTierId = (int)($order['client_id'] ?? 0);
    if (!$prefillTierId) {
        $orderClient = trim((string)($order['client'] ?? ''));
        foreach ($tiers as $t) {
            if (trim((string)($t['name'] ?? '')) === $orderClient) {
                $prefillTierId = (int)($t['id'] ?? 0);
                break;
            }
        }
    }
}
?>
<div class="dol-page shipment-page"><div class="dol-icon-title"><i class="fa-solid fa-dolly text-green"></i></div><?php if(!empty($_GET['stock_error'])): ?><div class="alert" style="margin:8px 0 12px;"><b>Erreur stock :</b> <?=e($_GET['stock_error'])?></div><?php endif; ?><?php if($order): ?><div class="alert-inline" style="margin:10px 0;padding:8px 10px;background:#eefaf3;border:1px solid #bbf0ce;color:#075985;font-size:12px;border-radius:4px;">Expédition liée à la commande <b><?= e($order['ref'] ?? '') ?></b></div><?php endif; ?>
<form method="post" action="index.php?page=<?=$edit?'expedition_update':'expedition_store'?>" class="dol-form"><?=csrf_field()?><?php if($edit): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?><input type="hidden" name="order_id" value="<?= (int)$orderId ?>">
  <div class="dol-line"><label>Réf.</label><span class="muted"><?=e($edit['ref']??'Brouillon')?></span></div>
  <div class="dol-line"><label>Client</label><span class="field-icon"><i class="fa-solid fa-building text-purple"></i><select name="tier_id" required><option value="">Sélectionner un tiers</option><?php foreach($tiers as $t): if(($t['type']??'')!=='client') continue; ?><option value="<?=(int)$t['id']?>" <?=($prefillTierId===(int)$t['id'])?'selected':''?>><?=e(($t['ref']??'').' - '.($t['name']??''))?></option><?php endforeach; ?></select></span></div>
  <div class="dol-line two"><label>Date expédition</label><input type="date" name="date" value="<?=e($edit['date']??($order['order_date']??date('Y-m-d')))?>"><label>Date livraison prévue</label><input type="date" name="delivery_date" value="<?=e($edit['delivery_date']??($order['delivery_date']??date('Y-m-d')))?>"></div>
  <div class="dol-line"><label>Entrepôt source</label><span class="field-icon"><i class="fa-solid fa-box-open text-gold"></i><select name="warehouse_id"><?php foreach($warehouses as $w): ?><option value="<?=(int)$w['id']?>" <?=((int)($edit['warehouse_id']??0)===(int)$w['id'])?'selected':''?>><?=e($w['name']??'')?></option><?php endforeach; ?></select></span></div>
  <div class="dol-line"><label>Méthode d'expédition</label><select name="shipping_method"><?php foreach(['UPS','Generic transport service','In-Store Collection','Transporteur local','Retrait client'] as $m): ?><option <?= (($edit['shipping_method']??($order['shipping_method']??''))===$m)?'selected':'' ?>><?=e($m)?></option><?php endforeach; ?></select></div>
  <div class="dol-line"><label>Tracking / Numéro colis</label><input name="tracking" value="<?=e($edit['tracking']??'')?>"></div>
  <div class="dol-line"><label>Note (publique)</label><textarea name="note_public" rows="3"><?=e($edit['note_public']??'')?></textarea></div>
  <div class="panel clean-table-box"><div class="panel-head">Lignes d'expédition</div><table class="clean-table"><thead><tr><th>Produit</th><th>Qté</th><th>Unité</th></tr></thead><tbody><?php foreach($lines as $l): ?><tr><td><?php if(!empty($l['needs_product'])): ?><div class="alert-inline" style="margin-bottom:5px;color:#b45309;">Produit non reconnu: <?=e($l['description']??'ligne commande')?>. Choisis le produit.</div><?php endif; ?><select name="product_id[]"><option value="">Sélectionner un produit</option><?php foreach($products as $p): ?><option value="<?=(int)$p['id']?>" <?=((int)($l['product_id']??0)===(int)$p['id'])?'selected':''?>><?=e(($p['ref']??'').' - '.($p['label']??''))?></option><?php endforeach; ?></select></td><td><input type="number" name="qty[]" value="<?=e($l['qty']??0)?>" min="0.001" step="0.001"></td><td><input name="unit[]" value="<?=e($l['unit']??'u.')?>"></td></tr><?php endforeach; ?></tbody></table></div>
  <div class="dol-actions"><button class="btn orange" type="submit"><?=$edit?'ENREGISTRER':'CRÉER EXPÉDITION'?></button><a class="btn orange" href="index.php?page=expeditions">ANNULER</a></div>
</form></div><?php include __DIR__.'/../layouts/footer.php'; ?>
