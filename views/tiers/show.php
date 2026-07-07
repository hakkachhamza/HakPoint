<?php require __DIR__.'/_helpers.php'; $id=(int)($_GET['id']??0); $t=find_row_by_id(tiers_all(),$id); if(!$t){ redirect_to('index.php?page=tiers'); } $title=$t['name']??'Tiers'; include __DIR__.'/../layouts/header.php';
$quotes=array_values(array_filter(data_read('quotes',[]), fn($q)=>($q['client']??'')===($t['name']??'')));
$invoices=array_values(array_filter(data_read('invoices',[]), fn($q)=>($q['client']??'')===($t['name']??'')));
$addr=trim(($t['address']??'').' '.($t['zip']??'').' '.($t['city']??'').' '.($t['country']??''));
$logo=tier_logo_src($t);
?>
<div class="soc-show-page">
  <div class="soc-show-head">
    <div class="soc-logo-box"><?php if($logo): ?><img src="<?=e($logo)?>" alt="logo"><?php else: ?><i class="fa-solid fa-building"></i><?php endif; ?></div>
    <div class="soc-head-info">
      <h1><?=e($t['name']??'')?></h1>
      <div class="muted"><?=e($t['alias']??'')?></div>
      <?php if($addr): ?><div><i class="fa-solid fa-location-dot"></i> <?=e($addr)?></div><?php endif; ?>
      <?php if(!empty($t['phone'])): ?><div><i class="fa-solid fa-phone"></i> <?=e($t['phone'])?></div><?php endif; ?>
      <?php if(!empty($t['email'])): ?><div><i class="fa-solid fa-at"></i> <a href="mailto:<?=e($t['email'])?>"><?=e($t['email'])?></a></div><?php endif; ?>
    </div>
    <div class="soc-nav"><a class="link" href="index.php?page=tiers">Retour liste</a><span>‹</span><span>›</span><br><span class="badge green"><?=e($t['status']??'Ouvert')?></span></div>
  </div>
  <div class="soc-details-grid">
    <div class="soc-lines">
      <div><span>Nature de tiers</span><b><?=tier_nature_badges($t)?></b></div>
      <div><span>Code client</span><b><?=e($t['code_client']??'')?></b></div>
      <div><span>Id. prof. 1 (R.C.)</span><b><?=e($t['rc']??'')?></b></div>
      <div><span>Id. prof. 2 (Patente)</span><b><?=e($t['patente']??'')?></b></div>
      <div><span>Id. prof. 3 (I.F.)</span><b><?=e($t['tax_id']??'')?></b></div>
      <div><span>Id. prof. 4 (C.N.S.S.)</span><b><?=e($t['cnss']??'')?></b></div>
      <div><span>Identifiant Commun d’Entreprise (ICE)</span><b><?=e($t['ice']??'')?></b></div>
      <div><span>Numéro de TVA</span><b><?=e($t['vat_number']??'')?></b></div>
      <div><span>EUID</span><b><?=e($t['euid']??'')?></b></div>
    </div>
    <div class="soc-lines">
      <div><span>Type du tiers</span><b><?=e($t['tier_type']??'')?></b></div>
      <div><span>Effectifs</span><b><?=e($t['staff_size']??'')?></b></div>
      <div><span>Type d'entité légale</span><b><?=e($t['legal_form']??'')?></b></div>
      <div><span>Date de création de société</span><b><?=e($t['created_company']??'')?></b></div>
      <div><span>Capital</span><b><?=e($t['capital']??'')?></b></div>
      <div><span>Maison mère</span><b><?=e($t['parent_company']??'')?></b></div>
      <div><span>Commerciaux</span><b><i class="fa-solid fa-user"></i> <?=e(ge_record_author(['owner'=>$t['owner']??''],'owner'))?></b></div>
      <div><span>Conditions de règlement</span><b><?=e($t['payment_terms']??'')?></b></div>
      <div><span>Mode de règlement</span><b><?=e($t['payment_mode']??'')?></b></div>
    </div>
  </div>
  <div class="soc-detail-buttons">
    <a class="purple-btn" href="index.php?page=tiers_email&id=<?=$id?>">ENVOYER EMAIL</a>
    <a class="purple-btn" href="index.php?page=tiers_edit&id=<?=$id?>">MODIFIER</a>
    <a class="purple-btn" href="<?=csrf_url('index.php?page=tiers_clone&id='.$id)?>">CLONER</a>
    <a class="soft-btn" href="index.php?page=tiers_merge&id=<?=$id?>">FUSIONNER</a>
    <a onclick="return confirm('Supprimer ce tiers ?')" class="soft-btn" href="<?=csrf_url('index.php?page=tiers_delete&id='.$id)?>">SUPPRIMER</a>
  </div>
  <div class="soc-bottom-grid">
    <div class="mini-panel"><h4>Fichiers joints</h4><p>Aucun</p></div>
    <div class="mini-panel"><h4>Les 10 derniers événements <span><i class="fa-solid fa-comments"></i> <i class="fa-solid fa-list"></i></span></h4><table class="dol-table"><tr><th>Réf.</th><th>Date</th><th>Par</th><th>Titre</th></tr><tr><td>#<?=count($quotes)+count($invoices)?></td><td><?=date('d/m/Y H:i')?></td><td><i class="fa-solid fa-user"></i> <?=e(ge_current_author_name())?></td><td>Enregistrement <?=e(tier_best_code($t))?></td></tr></table></div>
  </div>
  <div class="soc-bottom-grid">
    <div class="mini-panel"><h4>Objets référents</h4><table class="dol-table"><tr><th>Type</th><th>Nombre</th></tr><tr><td>Devis</td><td><?=count($quotes)?></td></tr><tr><td>Factures</td><td><?=count($invoices)?></td></tr><tr><td>Commandes</td><td>0</td></tr></table></div>
    <div class="mini-panel"><h4>Notes</h4><p><?=nl2br(e($t['note']??'Aucune note.'))?></p></div>
  </div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
