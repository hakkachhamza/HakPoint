<?php $title='Liste des produits'; include __DIR__.'/../layouts/header.php'; $products=data_read('products',[]); [$products,$productsTotal,$productsPage,$productsPages]=ge_list_slice($products); ?>
<div class="clean-list-page products-clean-list">
  <div class="clean-list-head">
    <div class="clean-title"><i class="fa-solid fa-box"></i><span>(<?=e($productsTotal)?>)</span></div>
    <div class="clean-tools">
      <select class="clean-select"><option>20</option><option>50</option><option>100</option></select>
      <span class="clean-page"><?=e($productsPage)?> / <?=e($productsPages)?></span>
      <span class="clean-next">›</span>
      <a class="clean-add" href="index.php?page=product_new"><i class="fa-solid fa-plus"></i></a>
    </div>
  </div>

  <div class="clean-table-box">
    <table class="clean-table products-table" id="productsTable">
      <thead>
        <tr class="clean-filters">
          <th></th>
          <th><input data-filter="1" placeholder="Réf."></th>
          <th><input data-filter="2" placeholder="Libellé"></th>
          <th><input data-filter="3" placeholder="Prix"></th>
          <th><input data-filter="4" placeholder="Prix achat"></th>
          <th><input data-filter="5" placeholder="Stock désiré"></th>
          <th><input data-filter="6" placeholder="Stock physique"></th>
          <th><input data-filter="7" placeholder="Stock virtuel"></th>
          <th><select data-filter="8"><option value="">Tous</option><option>En vente</option><option>Hors vente</option></select></th>
          <th><select data-filter="9"><option value="">Tous</option><option>En achat</option><option>Hors achat</option></select></th>
          <th><select data-filter="10"><option value="">Tous</option><option>Oui</option><option>Non</option></select></th>
          <th class="clean-row-tools"><button type="button" onclick="filterCleanTable('productsTable')"><i class="fa-solid fa-magnifying-glass"></i></button><button type="button" onclick="clearCleanFilters('productsTable')"><i class="fa-solid fa-xmark"></i></button></th>
        </tr>
        <tr>
          <th>Image</th>
          <th>Réf.</th>
          <th>Libellé</th>
          <th>Prix de vente</th>
          <th>Meilleur prix d'achat</th>
          <th>Stock désiré</th>
          <th>Stock physique</th>
          <th>Stock virtuel <i class="fa-solid fa-circle-info muted"></i></th>
          <th>État (Vente)</th>
          <th>État (Achat)</th>
          <th>Site</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if(!$products): ?><tr><td colspan="12" class="empty-row">Aucun produit pour le moment. Clique sur + pour ajouter ton premier produit.</td></tr><?php endif; ?>
        <?php foreach($products as $p): $warn=(($p['physical_stock']??0)<=($p['alert_stock']??0)||($p['virtual_stock']??0)<0); ?>
        <tr>
          <td><img class="table-product-img" src="<?=e(product_image_src($p))?>" alt=""></td>
          <td><a class="ref" href="<?=product_url($p['id'])?>"><i class="fa-solid fa-cube product-cube"></i> <?=e($p['ref'])?></a></td>
          <td class="product-label"><?=e($p['label'])?></td>
          <td class="price"><?=money($p['sale_price']??0)?> <?=e($p['sale_tax_mode']??'HT')?></td>
          <td class="price"><?=($p['buy_price']??0)?money($p['buy_price']).' HT':''?></td>
          <td class="num"><?=e($p['desired_stock']??0)?></td>
          <td class="num"><?=$warn?'⚠️ ':''?><?=e($p['physical_stock']??0)?></td>
          <td class="num"><?=($p['virtual_stock']??0)<0?'⚠️ ':''?><?=e($p['virtual_stock']??0)?></td>
          <td><span class="badge green"><?=e($p['sale_status']??'En vente')?></span></td>
          <td><span class="badge green"><?=e($p['buy_status']??'En achat')?></span></td>
          <td><span class="badge <?=strtolower((string)($p['site_visible']??'Oui'))==='non'?'gray':'blue'?>"><?=e($p['site_visible']??'Oui')?></span></td>
          <td class="actions-cell"><a class="mini-action" href="index.php?page=product_edit&id=<?=(int)$p['id']?>">Modifier</a><a class="mini-action danger" onclick="return confirm('Supprimer ce produit ?')" href="<?=csrf_url('index.php?page=product_delete&id='.(int)$p['id'])?>">Supprimer</a></td>
        </tr><?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?=ge_list_pager($productsTotal,$productsPage,$productsPages,'p',['page'=>'products'])?>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
