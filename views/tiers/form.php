<?php require __DIR__.'/_helpers.php';
$tiers=tiers_all(); $id=(int)($_GET['id']??0); $editing=current_page()==='tiers_edit';
$t=$editing ? find_row_by_id($tiers,$id) : null;
$type=$t['type']??tier_form_type();
$defaults=tier_default_codes($type, $editing?$id:next_id($tiers));
$title=$editing?'Modifier tiers':($type==='client'?'Nouveau client':($type==='supplier'?'Nouveau fournisseur':($type==='prospect'?'Nouveau prospect':'Nouveau tiers')));
include __DIR__.'/../layouts/header.php'; ?>
<div class="dol-page plain-page">
  <div class="soc-form-icon"><i class="fa-solid fa-building"></i></div>
  <form class="societe-form" method="post" enctype="multipart/form-data" action="index.php?page=<?=$editing?'tiers_update&id='.$id:'tiers_store'?>">
    <?=csrf_field()?>
    <input type="hidden" name="type" value="<?=e($type)?>">
    <div class="soc-grid top-soc-grid">
      <label>Nom du tiers</label>
      <div><input class="wide-input" name="name" required value="<?=e($t['name']??'')?>"><br><input class="wide-input light-placeholder" name="alias" placeholder="Nom alternatif (commercial, marque, ...)" value="<?=e($t['alias']??'') ?>"></div>
      <label></label>
      <div class="nature-checks">
        <label class="pill-check">Prospect <input type="checkbox" name="is_prospect" <?=(!empty($t['is_prospect'])||$type==='prospect')?'checked':''?>></label>
        <label class="pill-check">Client <input type="checkbox" name="is_client" <?=(!empty($t['is_client'])||$type==='client')?'checked':''?>></label>
        <label class="pill-check">Fournisseur <input type="checkbox" name="is_supplier" <?=(!empty($t['is_supplier'])||$type==='supplier')?'checked':''?>></label>
      </div>
      <label>Code client</label><div><input name="code_client" value="<?=e($t['code_client']??$defaults['code_client'])?>"> <span class="info-dot">i</span></div>
      <label class="right-label">Code fournisseur</label><div><input name="code_supplier" value="<?=e($t['code_supplier']??$defaults['code_supplier'])?>"> <span class="info-dot">i</span></div>
      <label>Adresse</label><div class="span-3"><textarea name="address"><?=e($t['address']??'')?></textarea></div>
      <label>Code postal</label><div><input name="zip" value="<?=e($t['zip']??'')?>"></div>
      <label class="right-label">Ville</label><div><input name="city" value="<?=e($t['city']??'')?>"></div>
      <label>Pays</label><div><select name="country"><?php foreach(tier_countries() as $c): ?><option <?=$c===($t['country']??'Maroc (MA)')?'selected':''?>><?=e($c)?></option><?php endforeach; ?></select> <span class="info-dot">i</span></div>
      <label>Département / Canton</label><div><select name="state"><?php foreach(tier_morocco_departments() as $code=>$name): ?><option value="<?=e($code)?>" <?=($code===($t['state']??''))?'selected':''?>><?=e($code ? $code.' - '.$name : $name)?></option><?php endforeach; ?></select> <span class="info-dot">i</span></div>
      <label>Téléphone</label><div><input name="phone" value="<?=e($t['phone']??'')?>"></div>
      <label class="right-label">Tél portable</label><div><input name="mobile" value="<?=e($t['mobile']??'')?>"></div>

      <label>Fax</label><div><input name="fax" value="<?=e($t['fax']??'')?>"></div>
      <label class="right-label">Web</label><div><input name="web" value="<?=e($t['web']??'')?>"></div>

      <label>Email</label><div class="span-3 full-row-field"><input type="email" name="email" value="<?=e($t['email']??'')?>"></div>

      <label>Id. prof. 1 (R.C.)</label><div><input name="rc" value="<?=e($t['rc']??'')?>"></div>
      <label class="right-label">Id. prof. 2 (Patente)</label><div><input name="patente" value="<?=e($t['patente']??'')?>"></div>

      <label>Id. prof. 3 (I.F.)</label><div><input name="tax_id" value="<?=e($t['tax_id']??'')?>"></div>
      <label class="right-label">Id. prof. 4 (C.N.S.S.)</label><div><input name="cnss" value="<?=e($t['cnss']??'')?>"></div>

      <label>Identifiant Commun d’Entreprise (ICE)</label><div><input name="ice" value="<?=e($t['ice']??'')?>"></div>
      <label class="right-label">Assujetti à la TVA</label><div class="checkbox-field"><input type="checkbox" name="vat_enabled" <?=($t['vat_enabled']??true)?'checked':''?>></div>

      <label>Numéro de TVA</label><div><input name="vat_number" value="<?=e($t['vat_number']??'')?>"></div>
      <label class="right-label">EUID</label><div><input name="euid" value="<?=e($t['euid']??'')?>"></div>
    </div>
    <div class="soc-more-title">Plus <span>...</span></div>
    <div class="soc-grid more-grid">
      <label>Type du tiers</label><div><select name="tier_type"><?php foreach(tier_types() as $v): ?><option <?=$v===($t['tier_type']??'')?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select> <span class="info-dot">i</span></div>
      <label class="right-label">Effectifs</label><div><select name="staff_size"><?php foreach(tier_staff_sizes() as $v): ?><option <?=$v===($t['staff_size']??'')?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select> <span class="info-dot">i</span></div>
      <label>Type d'entité légale</label><div><select name="legal_form"><?php foreach(tier_legal_forms() as $v): ?><option <?=$v===($t['legal_form']??'')?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select> <span class="info-dot">i</span></div>
      <label>Date de création de société</label><div><input type="date" name="created_company" value="<?=e($t['created_company']??'')?>"></div>
      <label>Capital</label><div><input name="capital" value="<?=e($t['capital']??'')?>"> <b>€</b></div>
      <label>Conditions de règlement</label><div><select name="payment_terms"><?php foreach(tier_payment_terms() as $v): ?><option <?=$v===($t['payment_terms']??'')?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select></div>
      <label>Mode de règlement</label><div><select name="payment_mode"><?php foreach(tier_payment_modes() as $v): ?><option <?=$v===($t['payment_mode']??'')?'selected':''?>><?=e($v)?></option><?php endforeach; ?></select></div>
      <label>Maison mère</label><div class="span-3 short-wide"><input name="parent_company" placeholder="Sélectionner un tiers" value="<?=e($t['parent_company']??'')?>"></div>
      <label>Assigner des commerciaux</label><div class="span-3 short-wide"><input name="owner" value="<?=e(($t['owner']??'')!=='' ? $t['owner'] : ge_current_author_name())?>"></div>
      <label>Logo</label><div><input type="file" name="logo" accept="image/*"><?php if(!empty($t['logo'])): ?><small>Actuel: <?=e($t['logo'])?></small><?php endif; ?></div>
    </div>
    <div class="soc-form-actions"><button class="purple-btn"><?= $editing?'ENREGISTRER':'CRÉER TIERS' ?></button><a class="purple-btn secondary" href="index.php?page=tiers">ANNULER</a></div>
  </form>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
