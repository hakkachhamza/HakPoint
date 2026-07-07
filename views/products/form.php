<?php
$products=data_read('products',[]);
$id=(int)($_GET['id']??0);
$isEdit = current_page()==='product_edit' && $id>0;
$product = $isEdit ? find_row_by_id($products,$id) : null;
if($isEdit && !$product) redirect_to('index.php?page=products');
$title=$isEdit ? 'Modifier produit' : 'Nouveau produit';
include __DIR__.'/../layouts/header.php';
$warehouses=data_read('warehouses',[]);
$countries=['Afghanistan','Afrique du Sud','Albanie','Algérie','Allemagne','Andorre','Angola','Anguilla','Antarctique','Arabie saoudite','Argentine','Arménie','Australie','Autriche','Belgique','Bénin','Brésil','Canada','Chili','Chine','Corée du Sud','Côte d’Ivoire','Danemark','Égypte','Espagne','États-Unis','Finlande','France','Gabon','Ghana','Grèce','Inde','Indonésie','Irlande','Italie','Japon','Jordanie','Kenya','Luxembourg','Maroc','Mauritanie','Mexique','Norvège','Pays-Bas','Portugal','Qatar','Royaume-Uni','Sénégal','Suisse','Tunisie','Turquie','Ukraine'];
function oldp($key,$default=''){ global $product; return e($product[$key] ?? $default); }
$action=$isEdit ? 'index.php?page=product_update&id='.$id : 'index.php?page=product_store';
?>
<form class="product-form panel real-product-form" method="post" action="<?=$action?>" enctype="multipart/form-data">
  <?=csrf_field()?>
  <div class="product-form-top">
    <div class="image-uploader">
      <img id="productPreview" src="<?= $isEdit ? e(product_image_src($product)) : 'assets/images/product-placeholder.svg' ?>" alt="Image produit">
      <label class="btn muted upload-btn">Ajouter image<input type="file" name="product_image" accept="image/*" onchange="previewProductImage(this)"></label>
      <?php if($isEdit && !empty($product['image'])): ?><small><?=e($product['image'])?></small><?php endif; ?>
    </div>
    <div class="form-grid flex-grow">
      <label>Réf.</label><input name="ref" required value="<?=oldp('ref')?>">
      <label>Libellé</label><input name="label" required value="<?=oldp('label')?>">
      <label>État (Vente)</label><select name="sale_status"><option <?=oldp('sale_status','En vente')==='En vente'?'selected':''?>>En vente</option><option <?=oldp('sale_status')==='Hors vente'?'selected':''?>>Hors vente</option></select>
      <label>État (Achat)</label><select name="buy_status"><option <?=oldp('buy_status','En achat')==='En achat'?'selected':''?>>En achat</option><option <?=oldp('buy_status')==='Hors achat'?'selected':''?>>Hors achat</option></select>
      <label>Description</label><textarea name="description"><?=oldp('description')?></textarea>
      <label>URL publique</label><input name="public_url" value="<?=oldp('public_url')?>">
      <label>Afficher ce produit sur le site ?</label>
      <select name="site_visible">
        <option value="Oui" <?=oldp('site_visible','Oui')==='Oui'?'selected':''?>>Oui</option>
        <option value="Non" <?=oldp('site_visible')==='Non'?'selected':''?>>Non</option>
      </select>
    </div>
  </div>
  <hr>
  <div class="form-grid">
    <label>Entrepôt par défaut</label><select name="warehouse_id"><?php foreach($warehouses as $w): $sel=(int)($product['warehouse_id']??0)===(int)($w['id']??0) || ((int)($product['warehouse_id']??0)<=0 && oldp('warehouse','Entrepôt principal')===$w['name']);?><option value="<?=(int)($w['id']??0)?>" data-name="<?=e($w['name']??'')?>" <?=$sel?'selected':''?>><?=e($w['name'])?></option><?php endforeach;?></select><input type="hidden" name="warehouse" value="<?=oldp('warehouse','Entrepôt principal')?>">
    <label>Limite stock pour alerte</label><input name="alert_stock" type="number" value="<?=oldp('alert_stock',0)?>">
    <label>Stock désiré optimal</label><input name="desired_stock" type="number" value="<?=oldp('desired_stock',0)?>">
    <?php
    $productNatures = [
      'Matière première',
      'Produit manufacturé',
      'Accessoires',
      'Accessoires de Suspension',
      'Diffusion D’air',
      'Isolat',
      'Tubes en cuivre',
      'Black miroir',
      'Mono split',
      'Armoire',
      'Cassette',
      'Console',
      'Gainable',
      'Mobile',
      'Mural',
      'Multi Split',
      'Multi muraux'
    ];
    $selectedNature = (string)($product['type'] ?? 'Produit manufacturé');
    ?>
    <label>Nature de produit</label>
    <select name="type">
      <option value=""></option>
      <?php foreach($productNatures as $nature): ?>
        <option value="<?=e($nature)?>" <?=$selectedNature===$nature?'selected':''?>><?=e($nature)?></option>
      <?php endforeach; ?>
    </select>
    <label>Marque / Type de produit</label>
    <?php $brandValue = strtolower($product['product_type'] ?? ''); ?>
    <div class="brand-combo" data-brand-combo>
      <input type="hidden" name="product_type" class="brand-combo-value" value="<?=e($brandValue)?>">
      <div class="brand-combo-control">
        <input type="text" class="brand-combo-input" placeholder="Tapez ou choisissez une marque" value="<?=e($brandValue)?>" autocomplete="off">
        <button type="button" class="brand-combo-btn" aria-label="Afficher les marques"><i class="fa-solid fa-chevron-down"></i></button>
      </div>
      <div class="brand-combo-list" role="listbox">
        <button type="button" class="brand-combo-option" data-value="midea" data-label="Midea"><img src="assets/images/brands/brand-midea.png" alt="Midea"><span>Midea</span></button>
        <button type="button" class="brand-combo-option" data-value="ingelec" data-label="Ingelec"><img src="assets/images/brands/brand-ingelec.png" alt="Ingelec"><span>Ingelec</span></button>
        <button type="button" class="brand-combo-option" data-value="fitco" data-label="Fitco"><img src="assets/images/brands/brand-fitco.png" alt="Fitco"><span>Fitco</span></button>
        <button type="button" class="brand-combo-option" data-value="carrier" data-label="Carrier"><img src="assets/images/brands/brand-carrier.png" alt="Carrier"><span>Carrier</span></button>
      </div>
    </div>
    <label>Poids</label><input name="weight" value="<?=oldp('weight')?>">
    <label>Longueur x Largeur x Hauteur</label><div class="triple"><input name="length" value="<?=oldp('length')?>"><span>x</span><input name="width" value="<?=oldp('width')?>"><span>x</span><input name="height" value="<?=oldp('height')?>"></div>
    <label>Surface</label><input name="surface" value="<?=oldp('surface')?>">
    <label>Volume</label><input name="volume" value="<?=oldp('volume')?>">
    <label>Unité</label><select name="unit"><option <?=oldp('unit','unitF')==='unitF'?'selected':''?>>unitF</option><option <?=oldp('unit')==='Pièce'?'selected':''?>>Pièce</option><option <?=oldp('unit')==='m²'?'selected':''?>>m²</option><option <?=oldp('unit')==='m³'?'selected':''?>>m³</option><option <?=oldp('unit')==='Forfait'?'selected':''?>>Forfait</option></select>
    <label>Nomenclature douanière ou Code SH</label><input name="customs_code" value="<?=oldp('customs_code')?>">
    <label>Pays d'origine</label><select name="country"><?php foreach($countries as $c):?><option <?=$c==($product['country']??'Maroc')?'selected':''?>><?=e($c)?></option><?php endforeach;?></select>
    <label>Note</label><textarea name="note"><?=oldp('note')?></textarea>
  </div>
  <hr>
  <div class="form-grid">
    <label>Prix de vente</label><div><input name="sale_price" type="number" step="0.001" value="<?=oldp('sale_price',0)?>"> <select name="sale_tax_mode"><option <?=oldp('sale_tax_mode','HT')==='HT'?'selected':''?>>HT</option><option <?=oldp('sale_tax_mode')==='TTC'?'selected':''?>>TTC</option></select></div>
    <label>Prix de vente min.</label><input name="min_sale_price" type="number" step="0.001" value="<?=oldp('min_sale_price',0)?>">
    <label>Taux TVA</label><select name="tax"><option <?=oldp('tax','20%')==='20%'?'selected':''?>>20%</option><option <?=oldp('tax')==='14%'?'selected':''?>>14%</option><option <?=oldp('tax')==='10%'?'selected':''?>>10%</option><option <?=oldp('tax')==='0%'?'selected':''?>>0%</option></select>
    <label>Prix d'achat</label><input name="buy_price" type="number" step="0.001" value="<?=oldp('buy_price',0)?>">
    <label>Stock physique</label><input name="physical_stock" type="number" value="<?=oldp('physical_stock',0)?>">
  </div>
  <div class="form-actions"><button class="btn orange"><?= $isEdit ? 'ENREGISTRER' : 'CRÉER' ?></button><a class="btn muted" href="index.php?page=products">ANNULER</a></div>
</form>
<script>
function previewProductImage(input){ const f=input.files && input.files[0]; if(!f) return; document.getElementById('productPreview').src=URL.createObjectURL(f); }
</script>
<?php include __DIR__.'/../layouts/footer.php'; ?>
