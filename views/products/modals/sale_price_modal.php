<?php
$productId = (int)($product['id'] ?? 0);
$currentTax = (float)($product['tax_rate'] ?? 20);
$currentBase = $product['base_price'] ?? 'HT';
$currentSale = (float)($product['sale_price'] ?? 0);
$currentMin = (float)($product['sale_price_min'] ?? 0);
?>
<div class="crm-modal" id="salePriceModal" aria-hidden="true">
  <div class="crm-modal-box small-form-modal">
    <div class="modal-title-line"><span class="doc-icon">📄</span><button type="button" class="modal-x" data-close-modal="salePriceModal">×</button></div>
    <form method="post" action="index.php?page=product_save_sale_price" class="dol-edit-form"><?=csrf_field()?>
      <input type="hidden" name="id" value="<?=e($productId)?>">
      <div class="edit-grid">
        <label>Taux de taxe par défaut</label>
        <div class="inline-field"><select name="tax_rate"><option <?= $currentTax==20?'selected':'' ?>>20</option><option <?= $currentTax==14?'selected':'' ?>>14</option><option <?= $currentTax==10?'selected':'' ?>>10</option><option <?= $currentTax==7?'selected':'' ?>>7</option><option <?= $currentTax==0?'selected':'' ?>>0</option></select><span>%</span></div>
        <label>Base de prix</label>
        <div class="inline-field"><select name="base_price"><option <?= $currentBase==='HT'?'selected':'' ?>>HT</option><option <?= $currentBase==='TTC'?'selected':'' ?>>TTC</option></select></div>
        <label>Prix de vente <em>ⓘ</em></label>
        <input name="sale_price" type="number" step="0.001" value="<?=e($currentSale)?>">
        <label>Prix de vente min. <em>ⓘ</em></label>
        <input name="sale_price_min" type="number" step="0.001" value="<?=e($currentMin)?>">
      </div>
      <div class="modal-actions"><button class="orange" type="submit">ENREGISTRER</button><button class="orange" type="button" data-close-modal="salePriceModal">ANNULER</button></div>
    </form>
  </div>
</div>
