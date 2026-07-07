<?php require __DIR__.'/_helpers.php';
$id=(int)($_GET['id']??0); $editing=$id>0; $w=$editing?warehouse_find($id):null; if($editing && !$w) redirect_to('index.php?page=warehouses');
$title=$editing?'Modifier entrepôt':'Nouvel entrepôt'; include __DIR__.'/../layouts/header.php';
$warehouses=warehouses_all();
?>
<div class="warehouse-form-page dol-page">
  <div class="dol-icon-title"><i class="fa-solid fa-box-open text-gold"></i></div>
  <form method="post" action="index.php?page=<?=$editing?'warehouse_update':'warehouse_store'?>" class="dol-form">
    <?=csrf_field()?>
    <?php if($editing): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?>
    <div class="dol-line"><label>Réf.</label><input name="ref" value="<?=e($w['ref']??'')?>"></div>
    <div class="dol-line"><label>Nom court de l'emplacement</label><input name="name" value="<?=e($w['name']??'')?>" required></div>
    <div class="dol-line"><label>Ajouter dans</label><span class="field-icon"><i class="fa-solid fa-box-open text-gold"></i><select name="parent_id"><option value="0"></option><?php foreach($warehouses as $wh): if((int)($wh['id']??0)===$id) continue; ?><option value="<?=(int)$wh['id']?>" <?=((int)($w['parent_id']??0)===(int)$wh['id'])?'selected':''?>><?=e($wh['name']??'')?></option><?php endforeach; ?></select></span></div>
    <div class="dol-line"><label>Description</label><textarea name="description" rows="5"><?=e($w['description']??'')?></textarea></div>
    <div class="dol-line"><label>Adresse</label><textarea name="address" rows="3"><?=e($w['address']??'')?></textarea></div>
    <div class="dol-line two"><label>Code postal</label><input name="zip" value="<?=e($w['zip']??'')?>"><label>Ville</label><input name="city" value="<?=e($w['city']??'')?>"></div>
    <div class="dol-line"><label>Pays</label><span class="field-icon"><i class="fa-solid fa-globe text-muted"></i><select name="country"><option>Maroc (MA)</option><option>France (FR)</option><option>Espagne (ES)</option><option>Algérie (DZ)</option><option>Tunisie (TN)</option></select></span></div>
    <div class="dol-line"><label>Téléphone</label><span class="field-icon"><i class="fa-solid fa-phone text-olive"></i><input name="phone" value="<?=e($w['phone']??'')?>"></span></div>
    <div class="dol-line"><label>Fax</label><span class="field-icon"><i class="fa-solid fa-fax text-olive"></i><input name="fax" value="<?=e($w['fax']??'')?>"></span></div>
    <div class="dol-line"><label>État</label><select name="status"><?php foreach(warehouse_statuses() as $s): ?><option value="<?=e($s)?>" <?=($w['status']??'Ouvert')===$s?'selected':''?>><?=e($s)?></option><?php endforeach; ?></select></div>
    <div class="dol-actions"><button class="btn orange" type="submit"><?=$editing?'ENREGISTRER':'CRÉER'?></button><a class="btn orange" href="index.php?page=warehouses">ANNULER</a></div>
  </form>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
