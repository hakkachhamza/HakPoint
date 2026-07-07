<?php
require_once __DIR__.'/_helpers.php';
$orders=data_read('orders',[]); $id=(int)($_GET['id']??0); $isEdit=current_page()==='order_edit' && $id>0; $order=$isEdit?find_row_by_id($orders,$id):null; if($isEdit && !$order) redirect_to('index.php?page=orders');
$title=$isEdit?'Modifier commande':'Nouvelle commande'; include __DIR__.'/../layouts/header.php';
$clients=order_available_clients(); $products=order_products_options(); $today=date('d/m/Y');
$paymentTerms=['','50/50 Fin T','à la commande','120 j','A réception','30 jours','30 jours fin de mois','60 jours','60 jours fin de mois','A commande','A livraison','50/50','10 jours','10 jours fin de mois','15 jours','45 jours','90 jours'];
$paymentModes=['','Carte bancaire','Chèque','Espèce','Ordre de prélèvement','Virement bancaire'];
$deliveryDelays=['','Immédiate','1 semaine','2 semaines','3 semaines','4 weeks','5 weeks','6 weeks','8 weeks','10 weeks','12 weeks','14 weeks'];
$shippingMethods=['','Generic transport service','In-Store Collection','UPS'];
$channels=['','Web','Téléphone','Email','Boutique','Commercial','Partenaire'];
function ov($k,$d=''){ global $order; return e($order[$k]??$d); } $action=$isEdit?'index.php?page=order_update':'index.php?page=order_store'; $lines=$order['lines']??[order_default_line()];
?>
<div class="devis-create panel dol-page order-create-page">
  <div class="dol-title-icon"><i class="fa-solid fa-file-invoice text-green"></i></div>
  <form method="post" action="<?=$action?>" class="devis-form order-form">
    <?=csrf_field()?>
    <?php if($isEdit): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?>
    <div class="devis-grid order-grid">
      <label>Réf.</label><div><b><?= $isEdit?e($order['ref']):'Brouillon' ?></b></div>
      <label>Ref. client</label><input name="client_ref" value="<?=ov('client_ref')?>" class="dol-input">
      <label>Client</label><div class="field-with-icons"><i class="fa-solid fa-building text-blue"></i><select name="client_id" class="dol-select wide"><option value="">Sélectionner un client</option><?php foreach($clients as $c): $cid=(int)($c['id']??0); $name=$c['name']??'Client'; $selected=((int)($order['client_id']??0)===$cid || (($order['client']??'')===$name));?><option value="<?=$cid?>" <?=$selected?'selected':''?>><?=e($name)?></option><?php endforeach;?></select><a class="tiny-plus" href="index.php?page=client_new">+</a><?php if(!$clients): ?><small class="muted">Aucun client créé</small><?php endif; ?></div>
      <label>Date</label><div class="inline-help"><input name="order_date" value="<?=ov('order_date',$today)?>" class="dol-input small"><small>Maintenant</small></div>
      <label>Date prévue de livraison</label><div class="inline-help"><input name="delivery_date" value="<?=ov('delivery_date')?>" class="dol-input small"><small><i class="fa-regular fa-calendar"></i></small></div>
      <label>Délai de livraison</label><div class="field-with-icons"><i class="fa-regular fa-clock"></i><select name="delivery_delay" class="dol-select"><?php foreach($deliveryDelays as $o):?><option <?=($order['delivery_delay']??'')===$o?'selected':''?>><?=e($o)?></option><?php endforeach;?></select></div>
      <label>Conditions de règlement</label><div class="field-with-icons"><i class="fa-solid fa-money-check-dollar text-olive"></i><select name="payment_terms" class="dol-select"><?php foreach($paymentTerms as $o):?><option <?=($order['payment_terms']??'')===$o?'selected':''?>><?=e($o)?></option><?php endforeach;?></select></div>
      <label>Mode de règlement</label><div class="field-with-icons"><i class="fa-solid fa-building-columns"></i><select name="payment_mode" class="dol-select"><?php foreach($paymentModes as $o):?><option <?=($order['payment_mode']??'')===$o?'selected':''?>><?=e($o)?></option><?php endforeach;?></select></div>
      <label>Méthode d'expédition</label><div class="field-with-icons"><i class="fa-solid fa-dolly text-cyan"></i><select name="shipping_method" class="dol-select"><?php foreach($shippingMethods as $o):?><option <?=($order['shipping_method']??'')===$o?'selected':''?>><?=e($o)?></option><?php endforeach;?></select></div>
      <label>Channel</label><div class="field-with-icons"><i class="fa-solid fa-question"></i><select name="channel" class="dol-select"><?php foreach($channels as $o):?><option <?=($order['channel']??'')===$o?'selected':''?>><?=e($o)?></option><?php endforeach;?></select></div>
      <label>Modèle de document par défaut</label><div class="field-with-icons"><i class="fa-regular fa-file-lines"></i><select name="template" class="dol-select"><option <?=ov('template','einstein')==='einstein'?'selected':''?>>einstein</option><option <?=ov('template')==='azur'?'selected':''?>>azur</option></select></div>
      <label>Note (publique)</label><textarea name="public_note" class="dol-textarea"><?=ov('public_note')?></textarea>
      <label>Note (privée)</label><textarea name="private_note" class="dol-textarea"><?=ov('private_note')?></textarea>
    </div>
    <hr>
    <h3 class="section-title"><i class="fa-solid fa-list"></i> Lignes de commande</h3>
    <div class="responsive-table"><table class="edit-lines-table product-lines-editor" id="orderLines"><thead><tr><th>Produit</th><th>Description</th><th>TVA</th><th>P.U. HT</th><th>Qté</th><th>Unité</th><th>Réduc %</th><th>Prix revient</th><th></th></tr></thead><tbody>
    <?php foreach($lines as $l): ?><tr>
      <td><select name="line_product_id[]" class="line-product-select" data-product-line-select><option value="">Produit libre</option><?php foreach($products as $p): $pid=(int)($p['id']??0); ?><option value="<?=$pid?>" data-ref="<?=e($p['ref']??'')?>" data-label="<?=e($p['label']??'')?>" data-price="<?=e($p['sale_price']??0)?>" data-vat="<?=e($p['vat']??$p['tax_rate']??20)?>" data-cost="<?=e($p['buy_price']??$p['purchase_price']??0)?>" data-unit="<?=e($p['unit']??'u.')?>" <?=((int)($l['product_id']??0)===$pid)?'selected':''?>><?=e(trim(($p['ref']??'').' - '.($p['label']??''),' -'))?></option><?php endforeach;?></select></td>
      <td><textarea name="line_description[]"><?=e($l['description']??'')?></textarea></td><td><input name="line_tva[]" value="<?=e($l['tva']??20)?>"></td><td><input name="line_pu_ht[]" value="<?=e($l['pu_ht']??0)?>"></td><td><input name="line_qty[]" value="<?=e($l['qty']??1)?>"></td><td><input name="line_unit[]" value="<?=e($l['unit']??'u.')?>"></td><td><input name="line_reduction[]" value="<?=e(str_replace('%','',$l['reduction']??''))?>"></td><td><input name="line_cost_price[]" value="<?=e($l['cost_price']??0)?>"></td><td><button type="button" class="small-gray-btn" onclick="this.closest('tr').remove()">×</button></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <button type="button" class="small-gray-btn" onclick="addOrderLine()"><i class="fa-solid fa-plus"></i> Ajouter une ligne</button>
    <div class="devis-actions"><button class="btn orange"><?= $isEdit?'ENREGISTRER':'CRÉER BROUILLON' ?></button><a href="index.php?page=orders" class="btn orange">ANNULER</a></div>
  </form>
</div>
<template id="orderLineTemplate"><tr><td><select name="line_product_id[]" class="line-product-select" data-product-line-select><option value="">Produit libre</option><?php foreach($products as $p): $pid=(int)($p['id']??0); ?><option value="<?=$pid?>" data-ref="<?=e($p['ref']??'')?>" data-label="<?=e($p['label']??'')?>" data-price="<?=e($p['sale_price']??0)?>" data-vat="<?=e($p['vat']??$p['tax_rate']??20)?>" data-cost="<?=e($p['buy_price']??$p['purchase_price']??0)?>" data-unit="<?=e($p['unit']??'u.')?>"><?=e(trim(($p['ref']??'').' - '.($p['label']??''),' -'))?></option><?php endforeach;?></select></td><td><textarea name="line_description[]"></textarea></td><td><input name="line_tva[]" value="20"></td><td><input name="line_pu_ht[]" value="0"></td><td><input name="line_qty[]" value="1"></td><td><input name="line_unit[]" value="u."></td><td><input name="line_reduction[]" value=""></td><td><input name="line_cost_price[]" value="0"></td><td><button type="button" class="small-gray-btn" onclick="this.closest('tr').remove()">×</button></td></tr></template><script>function addOrderLine(){var t=document.getElementById('orderLineTemplate');document.querySelector('#orderLines tbody').appendChild(t.content.cloneNode(true)); if(window.geInitProductLineEditors) window.geInitProductLineEditors(document.querySelector('#orderLines tbody'));}</script>
<?php include __DIR__.'/../layouts/footer.php'; ?>
