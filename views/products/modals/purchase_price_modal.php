<?php
$productId = (int)($product['id'] ?? 0);
$suppliers = data_read('suppliers', []);
$currentTax = (float)($product['tax_rate'] ?? 20);
?>
<div class="crm-modal" id="purchasePriceModal" aria-hidden="true">
  <div class="crm-modal-box large-form-modal">
    <div class="modal-title-line"><span class="doc-icon">📄</span><button type="button" class="modal-x" data-close-modal="purchasePriceModal">×</button></div>
    <form method="post" action="index.php?page=product_save_purchase_price" class="dol-edit-form"><?=csrf_field()?>
      <input type="hidden" name="id" value="<?=e($productId)?>">
      <div class="edit-grid buy-form-grid">
        <label>Fournisseur</label>
        <div class="select-with-icon"><span>🏢</span><select name="supplier_id"><option value="">Sélectionner un tiers</option><?php foreach($suppliers as $supplier): ?><option value="<?=e($supplier['id'] ?? '')?>"><?=e($supplier['name'] ?? $supplier['company'] ?? 'Fournisseur')?></option><?php endforeach; ?></select></div>
        <label>Réf. produit fournisseur</label>
        <input name="supplier_ref" type="text" value="">
        <label>Qté achat minimum</label>
        <div class="inline-field"><input name="min_qty" type="number" min="1" step="1" value="1"><span>unitF</span></div>
        <label>Taux TVA (pour ce produit/fournisseur)</label>
        <input name="tax_rate" type="number" step="0.001" value="<?=e($currentTax)?>">
        <label>Prix quantité min.</label>
        <div class="inline-field"><input name="purchase_price" type="number" step="0.001" value=""><select name="base_price"><option>HT</option><option>TTC</option></select></div>
        <label>Remise pour cette qté.</label>
        <div class="inline-field"><input name="discount" type="number" step="0.01" value=""><span>%</span></div>
        <label>Délai de livraison en jours</label>
        <div class="inline-field"><input name="delivery_days" type="number" step="1" value=""><span>jours</span></div>
        <label>Réputation</label>
        <select name="reputation"><option value=""></option><option>Excellent</option><option>Bon</option><option>Moyen</option><option>Faible</option></select>
      </div>
      <div class="modal-actions"><button class="orange" type="submit">ENREGISTRER</button><button class="orange" type="button" data-close-modal="purchasePriceModal">ANNULER</button></div>
    </form>
  </div>
</div>
