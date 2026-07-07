<?php
$title='Stocks produits';
include __DIR__.'/../layouts/header.php';
$products=data_read('products',[]);
[$products,$total,$productsPage,$productsPages]=ge_list_slice($products);
?>
<div class="stock-list-page">
  <div class="stock-toolbar">
    <div class="stock-count"><span class="cube">📦</span><span>(<?=e($total)?>)</span></div>
    <div class="stock-pager">
      <select><option>20</option></select>
      <a class="active"><?=e($productsPage)?></a><span>/</span><a><?=e($productsPages)?></a><b>›</b>
    </div>
  </div>

  <div class="stock-table-wrap">
    <table class="stock-list-table">
      <thead>
        <tr class="stock-filter-top">
          <th colspan="11">
            <label class="stock-insufficient">Stock insuffisant <input type="checkbox"></label>
          </th>
        </tr>
        <tr class="stock-filters">
          <th><input></th>
          <th><input></th>
          <th></th>
          <th></th>
          <th><input></th>
          <th></th>
          <th></th>
          <th></th>
          <th></th>
          <th colspan="2"><span class="stock-search">🔍</span> <span class="stock-close">✖</span></th>
        </tr>
        <tr>
          <th>▾ Réf.</th>
          <th>Libellé</th>
          <th>Limite stock pou...</th>
          <th>Stock désiré opt...</th>
          <th>Stock physique</th>
          <th>Stock virtuel <em>ⓘ</em></th>
          <th>Unité</th>
          <th></th>
          <th>En vente</th>
          <th>En achat</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($products as $p):
          $physical=(int)($p['physical_stock']??0);
          $virtual=(int)($p['virtual_stock']??0);
          $alert=(int)($p['alert_stock']??($p['id']==1?20:0));
          $desired=(int)($p['desired_stock']??0);
          $unit=$p['unit']??(($p['id']%2)?'F':'p');
          $shortLabel = mb_strlen($p['label'])>22 ? mb_substr($p['label'],0,22).'...' : $p['label'];
          $shortRef = mb_strlen($p['ref'])>18 ? mb_substr($p['ref'],0,18).'...' : $p['ref'];
        ?>
        <tr>
          <td><a class="stock-ref" href="<?=product_url($p['id'],'stock')?>">📦 <?=e($shortRef)?></a></td>
          <td><?=e($shortLabel)?></td>
          <td class="num"><?=e($alert)?></td>
          <td class="num"><?=e($desired)?></td>
          <td class="num"><?=($physical<=0?'⚠️ ':'')?><?=e($physical)?></td>
          <td class="num"><?=($virtual<0?'⚠️ ':'')?><?=e($virtual)?></td>
          <td><?=e($unit)?></td>
          <td><a class="move-link" href="index.php?page=warehouse_movements&product_id=<?=(int)$p['id']?>">Mouvements</a></td>
          <td><span class="badge green"><?=e($p['sale_status']??'En vente')?></span></td>
          <td><span class="badge green"><?=e($p['buy_status']??'En achat')?></span></td>
          <td></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?=ge_list_pager($total,$productsPage,$productsPages,'p',['page'=>'product_stock'])?>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
