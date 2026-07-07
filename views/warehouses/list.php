<?php require __DIR__.'/_helpers.php'; warehouse_sync_products(); $title='Entrepôts'; include __DIR__.'/../layouts/header.php';
$warehouses=warehouses_all();
if(!$warehouses){ $warehouses=[['id'=>1,'ref'=>'DEPOT PRINCIPAL','name'=>'Principal','country'=>'Maroc (MA)','status'=>'Ouvert','created_at'=>date('d/m/Y H:i')]]; data_write('warehouses',$warehouses); }
$q=trim($_GET['q']??''); $status=$_GET['status']??'';
if($q!=='') $warehouses=array_values(array_filter($warehouses, fn($w)=>stripos(($w['ref']??'').' '.($w['name']??'').' '.($w['city']??''),$q)!==false));
if($status!=='') $warehouses=array_values(array_filter($warehouses, fn($w)=>($w['status']??'Ouvert')===$status));
[$warehouses,$warehousesTotal,$warehousesPage,$warehousesPages]=ge_list_slice($warehouses);
$totalBuy=0; $totalSale=0;
?>
<div class="warehouse-list-page clean-list-page dol-list-page">
  <div class="clean-list-head warehouse-head">
    <div class="clean-title"><i class="fa-solid fa-box-open text-gold"></i><span>(<?=e($warehousesTotal)?>)</span></div>
    <div class="clean-tools"><select class="clean-select"><option>20</option><option>50</option></select><a class="clean-add" href="index.php?page=warehouse_new"><i class="fa-solid fa-plus"></i></a></div>
  </div>
  <form id="warehouseFilter" method="get"><input type="hidden" name="page" value="warehouses"></form>
  <div class="clean-table-box warehouse-table-box">
    <table class="clean-table warehouse-table">
      <thead>
        <tr class="clean-filters"><th><input form="warehouseFilter" name="q" value="<?=e($q)?>"></th><th><input form="warehouseFilter" name="q2"></th><th></th><th></th><th><select form="warehouseFilter" name="status"><option value=""></option><option value="Ouvert" <?=$status==='Ouvert'?'selected':''?>>Ouvert</option><option value="Fermé" <?=$status==='Fermé'?'selected':''?>>Fermé</option></select></th><th class="clean-row-tools"><button form="warehouseFilter"><i class="fa-solid fa-magnifying-glass"></i></button><a href="index.php?page=warehouses"><i class="fa-solid fa-xmark"></i></a></th></tr>
        <tr><th>▾ Réf.</th><th>Nom court de l'emplacement</th><th>Valorisation à l'achat (PMP)</th><th>Valeur vente</th><th>État</th><th><i class="fa-solid fa-list"></i></th></tr>
      </thead>
      <tbody>
      <?php foreach($warehouses as $w): [$buy,$sale]=warehouse_values($w); $totalBuy+=$buy; $totalSale+=$sale; ?>
        <tr><td><i class="fa-solid fa-box-open text-gold"></i> <a class="ref" href="index.php?page=warehouse_show&id=<?=(int)$w['id']?>"><?=e($w['ref']??warehouse_ref($w['id'],$w['name']??''))?></a></td><td><?=e($w['name']??'')?></td><td class="num text-teal"><?=money($buy)?></td><td class="num text-teal"><?=money($sale)?></td><td><span class="badge green"><?=e($w['status']??'Ouvert')?></span></td><td></td></tr>
      <?php endforeach; ?>
        <tr class="total-row"><td>Total</td><td></td><td class="num muted"><?=money($totalBuy)?></td><td class="num muted"><?=money($totalSale)?></td><td></td><td></td></tr>
      </tbody>
    </table>
  </div>
  <?=ge_list_pager($warehousesTotal,$warehousesPage,$warehousesPages,'p',['page'=>'warehouses'])?>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
