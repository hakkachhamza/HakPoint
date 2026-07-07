<?php require __DIR__.'/_helpers.php'; $filter=tier_filter_from_page();
$title = $filter ? 'Liste '.tier_type_label($filter).'s' : 'Tiers'; include __DIR__.'/../layouts/header.php';
$tiers=tiers_all();
if($filter) $tiers=array_values(array_filter($tiers, fn($t)=>($t['type']??'')===$filter || !empty($t['is_'.$filter])));
$q=trim($_GET['q']??''); $status=$_GET['status']??'';
if($q!=='') $tiers=array_values(array_filter($tiers, fn($t)=>stripos(($t['name']??'').' '.($t['alias']??'').' '.($t['email']??'').' '.($t['code_client']??'').' '.($t['code_supplier']??'').' '.($t['phone']??''),$q)!==false));
if($status!=='') $tiers=array_values(array_filter($tiers, fn($t)=>($t['status']??'Ouvert')===$status));
[$tiers,$tiersTotal,$tiersPage,$tiersPages]=ge_list_slice($tiers);
$current=current_page();
?>
<div class="soc-list-page">
  <form method="post" action="index.php?page=tiers_bulk_action" id="tiersBulkForm"><?=csrf_field()?>
    <input type="hidden" name="return_page" value="<?=e($current)?>">
    <div class="soc-list-top">
      <div class="soc-list-title"><i class="fa-solid fa-building"></i> <span>Tiers</span> <em>(<?=e($tiersTotal)?>)</em></div>
      <div class="quote-bulkbar" id="tiersBulkBar" aria-hidden="true">
        <select name="bulk_action" class="dol-select action-select" required>
          <option value="">-- Sélectionner l'action --</option>
          <option value="email">@ Envoyer email</option>
          <option value="open">● Définir sur le statut Ouvert</option>
          <option value="closed">● Définir sur le statut Clos</option>
          <option value="assign">♟ Affecter un commercial</option>
          <option value="delete">🗑 Supprimer</option>
        </select>
        <button type="submit" class="bulk-confirm">CONFIRMER</button>
      </div>
      <div class="soc-list-actions"><select><option>8</option><option>20</option></select><button type="button" class="view-btn active"><i class="fa-solid fa-list"></i></button><button type="button" class="view-btn"><i class="fa-solid fa-grip"></i></button><a class="round-plus" href="index.php?page=tiers_new"><i class="fa-solid fa-plus"></i></a></div>
    </div>
    <div class="soc-table-card">
      <div class="soc-filters">
        <div class="commercial-filter"><i class="fa-solid fa-user-tie"></i><input form="tiersFilterForm" name="q" placeholder="Commerciaux" value="<?=e($q)?>"></div>
        <div class="filter-icons"><button form="tiersFilterForm"><i class="fa-solid fa-magnifying-glass"></i></button><a href="index.php?page=<?=e($current)?>"><i class="fa-solid fa-xmark"></i></a></div>
        <input form="tiersFilterForm" placeholder="Nom" name="q2" value="">
        <input placeholder="Nom alternatif" value="">
        <input placeholder="Code" value="">
        <input placeholder="Code postal" value="">
        <select><option></option><option>Client</option><option>Prospect</option><option>Fournisseur</option></select>
        <input placeholder="Téléphone" value="">
        <select><option></option><option>Client</option><option>Prospect</option><option>Fournisseur</option></select>
        <select form="tiersFilterForm" name="status"><option value="">Ouvert</option><?php foreach(tier_statuses() as $st): ?><option value="<?=e($st)?>" <?=$status===$st?'selected':''?>><?=e($st)?></option><?php endforeach; ?><option value="Clos" <?=$status==='Clos'?'selected':''?>>Clos</option></select>
      </div>
      <div class="table-responsive"><table class="soc-table"><thead><tr><th><input type="checkbox" id="tiersCheckAll"></th><th>Nom du tiers</th><th>Nom alternatif</th><th>Code client</th><th>Code postal</th><th>Type du tiers</th><th>Téléphone</th><th>Nature de tiers</th><th>Commerciaux</th><th>État</th></tr></thead><tbody>
        <?php if(!$tiers): ?><tr><td colspan="10" class="muted">Aucun tiers</td></tr><?php endif; ?>
        <?php foreach($tiers as $t): ?><tr>
          <td><input type="checkbox" class="tiers-row-check" name="ids[]" value="<?=(int)$t['id']?>"></td>
          <td><i class="fa-solid fa-building text-purple"></i> <a class="ref" href="index.php?page=tiers_show&id=<?=(int)$t['id']?>"><?=e(tier_best_code($t))?> - <?=e($t['name']??'')?></a></td>
          <td><?=e($t['alias']??'')?></td>
          <td><?=e($t['code_client']??'')?></td>
          <td><?=e($t['zip']??'')?></td>
          <td><?=e($t['tier_type']??'')?></td>
          <td><?php if(!empty($t['phone'])): ?><i class="fa-solid fa-phone text-olive"></i> <?=e($t['phone'])?><?php endif; ?></td>
          <td><?=tier_nature_badges($t)?></td>
          <td><i class="fa-solid fa-user"></i> <?=e(ge_record_author(['owner'=>$t['owner']??''],'owner'))?></td>
          <td><span class="badge <?=($t['status']??'Ouvert')==='Clos'?'gray':'green'?>"><?=e($t['status']??'Ouvert')?></span></td>
        </tr><?php endforeach; ?>
      </tbody></table></div>
    </div>
  <?=ge_list_pager($tiersTotal,$tiersPage,$tiersPages,'p',['page'=>$current])?>
  </form>
  <form id="tiersFilterForm" method="get"><input type="hidden" name="page" value="<?=e($current)?>"></form>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
