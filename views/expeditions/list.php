<?php
require __DIR__.'/_helpers.php';
$title='Expéditions';
include __DIR__.'/../layouts/header.php';
$rows=expeditions_all();
$tiers=tiers_all();
$q=trim($_GET['q']??''); $clientRef=trim($_GET['client_ref']??''); $city=trim($_GET['city']??''); $zip=trim($_GET['zip']??''); $method=trim($_GET['method']??''); $tracking=trim($_GET['tracking']??''); $status=$_GET['status']??''; $from=$_GET['from']??''; $to=$_GET['to']??'';
$rows=array_map(function($r) use($tiers){ $t=find_row_by_id($tiers,(int)($r['tier_id']??0)); $r['tier_ref']=$t['code_client']??$t['ref']??$r['tier_ref']??''; $r['city']=$t['city']??$r['city']??''; $r['zip']=$t['zip']??$t['postal_code']??$r['zip']??''; return $r; },$rows);
if($q!=='') $rows=array_values(array_filter($rows,fn($r)=>stripos(($r['ref']??'').' '.($r['tier_name']??'').' '.($r['tier_ref']??''),$q)!==false));
if($clientRef!=='') $rows=array_values(array_filter($rows,fn($r)=>stripos(($r['tier_ref']??''),$clientRef)!==false));
if($city!=='') $rows=array_values(array_filter($rows,fn($r)=>stripos(($r['city']??''),$city)!==false));
if($zip!=='') $rows=array_values(array_filter($rows,fn($r)=>stripos(($r['zip']??''),$zip)!==false));
if($method!=='') $rows=array_values(array_filter($rows,fn($r)=>stripos(($r['shipping_method']??''),$method)!==false));
if($tracking!=='') $rows=array_values(array_filter($rows,fn($r)=>stripos(($r['tracking']??''),$tracking)!==false));
if($status!=='') $rows=array_values(array_filter($rows,fn($r)=>($r['status']??'')===$status));
if($from!=='') $rows=array_values(array_filter($rows,fn($r)=>($r['delivery_date']??$r['date']??'')>=$from));
if($to!=='') $rows=array_values(array_filter($rows,fn($r)=>($r['delivery_date']??$r['date']??'')<=$to));
[$rows,$rowsTotal,$rowsPage,$rowsPages]=ge_list_paginate_current($rows);
?>
<div class="dol-list-page shipment-list-page">
  <div class="dol-list-top">
    <div class="dol-list-icon"><i class="fa-solid fa-dolly text-green"></i></div>
    <span class="muted count-spacer">(<?=e($rowsTotal)?>)</span>
    <form id="bulkExp" action="index.php?page=expedition_bulk_action" method="post" class="bulk-actions" style="display:none"><?=csrf_field()?>
      <select name="action" required>
        <option value="">-- Sélectionner l'action --</option>
        <option value="Validée">Valider</option>
        <option value="Préparée">Marquer préparée</option>
        <option value="Expédiée">Expédier et déstocker</option>
        <option value="Livrée">Marquer livrée</option>
        <option value="Annulée">Annuler</option>
        <option value="delete">Supprimer</option>
      </select>
      <button class="btn small orange">CONFIRMER</button>
    </form>
    <div class="dol-pager"><select><option>20</option></select><b><?=e($rowsPage)?></b><span>/</span><span><?=e($rowsPages)?></span><i class="fa-solid fa-chevron-right"></i><a class="round" href="index.php?page=expedition_new"><i class="fa-solid fa-plus"></i></a></div>
  </div>
  <form id="expFilter" method="get"><input type="hidden" name="page" value="expeditions"></form>
  <div class="dol-table-card">
    <table class="dol-erp-table expedition-table">
      <thead>
        <tr class="filter-line top-filter">
          <th colspan="2"><span class="field-icon"><i class="fa-solid fa-user"></i><select form="expFilter" name="tier_kind"><option>Tiers ayant pour commercial</option></select></span></th>
          <th colspan="2"><span class="field-icon"><i class="fa-solid fa-user"></i><select form="expFilter" name="contact"><option>Liés à un contact utilisateur</option></select></span></th>
          <th colspan="5"></th>
        </tr>
        <tr class="filter-line">
          <th><input form="expFilter" name="q" value="<?=e($q)?>"></th>
          <th><input form="expFilter" name="client_ref" value="<?=e($clientRef)?>"></th>
          <th><input form="expFilter" name="tier" value=""></th>
          <th><input form="expFilter" name="city" value="<?=e($city)?>"></th>
          <th><input form="expFilter" name="zip" value="<?=e($zip)?>"></th>
          <th><input form="expFilter" name="from" type="date" value="<?=e($from)?>"><input form="expFilter" name="to" type="date" value="<?=e($to)?>"></th>
          <th><select form="expFilter" name="method"><option value=""></option><?php foreach(['UPS','Generic transport service','In-Store Collection','Transporteur local','Retrait client'] as $m): ?><option value="<?=e($m)?>" <?=$method===$m?'selected':''?>><?=e($m)?></option><?php endforeach; ?></select></th>
          <th><input form="expFilter" name="tracking" value="<?=e($tracking)?>"></th>
          <th><select form="expFilter" name="status"><option value=""></option><?php foreach(expedition_statuses() as $s): ?><option value="<?=e($s)?>" <?=$status===$s?'selected':''?>><?=e($s)?></option><?php endforeach; ?></select><button form="expFilter" class="icon-btn"><i class="fa-solid fa-magnifying-glass"></i></button><a class="icon-btn" href="index.php?page=expeditions"><i class="fa-solid fa-xmark"></i></a></th>
        </tr>
        <tr>
          <th>▾ Réf.</th><th>Réf. client</th><th>Tiers</th><th>Ville</th><th>Code postal</th><th>Date prévue de livraison</th><th>Méthode d'expédition</th><th>Numéro de suivi</th><th>État &nbsp; <i class="fa-solid fa-list"></i> <input type="checkbox" id="checkAllExp"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($rows as $r): $expRef=$r['ref']??''; $shipRef = (substr($expRef,0,2)==='SH' ? $expRef : preg_replace('/^EXP-?/','SH',$expRef)); ?>
        <tr>
          <td><i class="fa-solid fa-dolly text-green"></i> <a class="ref" href="index.php?page=expedition_show&id=<?=(int)$r['id']?>"><?=e($shipRef)?></a></td>
          <td><?=e($r['tier_ref']??'')?></td>
          <td><i class="fa-solid fa-building text-purple"></i> <a class="ref" href="index.php?page=tiers_show&id=<?=(int)($r['tier_id']??0)?>"><?=e($r['tier_name']??'')?></a></td>
          <td><?=e($r['city']??'')?></td>
          <td><?=e($r['zip']??'')?></td>
          <td><?=e(format_date_fr($r['delivery_date']??$r['date']??''))?></td>
          <td><?=e($r['shipping_method']??'')?></td>
          <td><?=e($r['tracking']??'')?></td>
          <td><span class="badge <?=e(expedition_status_badge($r['status']??''))?>"><?=e($r['status']??'')?></span> <input form="bulkExp" class="rowCheckExp" type="checkbox" name="ids[]" value="<?=(int)$r['id']?>"></td>
        </tr>
        <?php endforeach; if(!$rows): ?><tr><td colspan="9" class="empty-row">Aucune expédition</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?=ge_list_pager($rowsTotal,$rowsPage,$rowsPages,'p',['page'=>'expeditions'])?>
</div>
<script>
function syncExpBulk(){document.getElementById('bulkExp').style.display=document.querySelectorAll('.rowCheckExp:checked').length?'flex':'none'}
document.querySelectorAll('.rowCheckExp,#checkAllExp').forEach(el=>el.addEventListener('change',()=>{if(el.id==='checkAllExp')document.querySelectorAll('.rowCheckExp').forEach(c=>c.checked=el.checked);syncExpBulk();}));
</script>
<?php include __DIR__.'/../layouts/footer.php'; ?>
