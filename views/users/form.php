<?php
$title = current_page()==='user_edit' ? 'Modifier utilisateur' : 'Nouvel utilisateur';
include __DIR__.'/../layouts/header.php';
require_once __DIR__.'/_helpers.php';
require_once __DIR__.'/../tiers/_helpers.php';
$users=data_read('users',[]);
$id=(int)($_GET['id']??0);
$u=$id?find_row_by_id($users,$id):null;
$generatedPass='';
if(!$u){ $newId=next_id($users); $generatedPass=random_password(); $u=['id'=>$newId,'employee'=>true,'external_user'=>'Interne','country'=>'Maroc (MA)','status'=>'Actif','role'=>'Utilisateur']; }
?>
<div class="dol-page users-form-page">
  <div class="module-icon"><i class="fa-solid fa-user-plus"></i></div>
  <form class="dol-form" method="post" action="index.php?page=<?= $id?'user_update&id='.$id:'user_store' ?>">
    <?=csrf_field()?>
    <div class="dol-section">
      <div class="form-grid two-col">
        <label>Titre civilité</label><div><select name="civility" class="select-search"><?php foreach(user_titles() as $v=>$l): ?><option value="<?=e($v)?>" <?=($v===($u['civility']??''))?'selected':''?>><?=e($l)?></option><?php endforeach; ?></select></div>
        <label class="required">Nom</label><div><input name="name" value="<?=e($u['name']??'') ?>" required></div>
        <label>Prénom</label><div><input name="firstname" value="<?=e($u['firstname']??'') ?>"></div>
        <label class="required">Identifiant</label><div><input name="username" value="<?=e($u['username']??'') ?>" required></div>
        <label>Genre</label><div><select name="gender" class="select-search"><?php foreach(user_genders() as $v=>$l): ?><option value="<?=e($v)?>" <?=($v===($u['gender']??''))?'selected':''?>><?=e($l)?></option><?php endforeach; ?></select></div>
        <label>Salarié</label><div><input type="checkbox" name="employee" value="1" <?=!empty($u['employee'])?'checked':''?>></div>
        <label>Responsable hiérarchique</label><div><select name="manager_id"><option value="">-- Sélectionner --</option><?php foreach($users as $m): if((int)($m['id']??0)===(int)($u['id']??0)) continue; ?><option value="<?=e($m['id'])?>" <?=((string)($u['manager_id']??'')===(string)($m['id']??''))?'selected':''?>><?=e(user_display_name($m))?></option><?php endforeach; ?></select></div>
        <label>Forcer le valideur des notes de frais <span class="info-dot">i</span></label><div><select name="expense_validator"><option value="">-- Aucun --</option><?php foreach($users as $m): ?><option value="<?=e($m['id'])?>" <?=((string)($u['expense_validator']??'')===(string)($m['id']??''))?'selected':''?>><?=e(user_display_name($m))?></option><?php endforeach; ?></select></div>
        <label>Utilisateur externe ?</label><div><select name="external_user"><option <?=($u['external_user']??'')==='Interne'?'selected':''?>>Interne</option><option <?=($u['external_user']??'')==='Externe'?'selected':''?>>Externe</option></select> <span class="info-dot">i</span></div>
      </div>
    </div>

    <div class="dol-section">
      <div class="form-grid two-col">
        <label>Période de validité de l'identifiant</label><div><input type="date" name="valid_from" value="<?=e($u['valid_from']??'')?>"> <small>Maintenant</small> - <input type="date" name="valid_to" value="<?=e($u['valid_to']??'')?>"></div>
        <label class="required">Mot de passe</label><div><input class="password-field" name="password_plain" value="<?=e($generatedPass)?>" <?= $id?'placeholder="Laisser vide pour ne pas changer"':'required' ?>> <button class="mini-btn" type="button" onclick="generateUserPass()"><i class="fa-solid fa-rotate"></i></button></div>
      </div>
    </div>

    <div class="dol-section">
      <div class="form-grid two-col">
        <label>Adresse</label><div class="wide"><textarea name="address" rows="3"><?=e($u['address']??'')?></textarea></div>
        <label>Code postal</label><div><input name="zip" value="<?=e($u['zip']??'')?>"></div>
        <label>Ville</label><div><input name="city" value="<?=e($u['city']??'')?>"></div>
        <label>Pays</label><div><select name="country"><?php foreach(user_countries() as $c): ?><option value="<?=e($c)?>" <?=($c===($u['country']??'Maroc (MA)'))?'selected':''?>><?=e($c)?></option><?php endforeach; ?></select></div>
        <label>Département / Canton</label><div><select name="state"><?php foreach(user_departments() as $code=>$name): ?><option value="<?=e($code)?>" <?=($code===($u['state']??''))?'selected':''?>><?=e($code ? $code.' - '.$name : $name)?></option><?php endforeach; ?></select></div>
        <label>Tél pro.</label><div><input name="phone" value="<?=e($u['phone']??'')?>"></div>
        <label>Tél portable</label><div><input name="mobile" value="<?=e($u['mobile']??'')?>"></div>
        <label>Fax</label><div><input name="fax" value="<?=e($u['fax']??'')?>"></div>
        <label class="required">EMail</label><div><input type="email" name="email" value="<?=e($u['email']??'')?>" required></div>
        <label>Signature</label><div class="wide"><textarea name="signature" rows="3"><?=e($u['signature']??'')?></textarea></div>
        <label>Note (publique)</label><div class="wide"><textarea name="note_public" rows="3"><?=e($u['note_public']??'')?></textarea></div>
        <label>Note (privée)</label><div class="wide"><textarea name="note_private" rows="3"><?=e($u['note_private']??'')?></textarea></div>
      </div>
    </div>

    <div class="dol-section">
      <div class="form-grid two-col">
        <label>Poste/fonction</label><div><input name="job" value="<?=e($u['job']??'')?>"></div>
        <label>Salaire</label><div><input name="salary" value="<?=e($u['salary']??'')?>"></div>
        <label>Tarif horaire moyen</label><div><input name="hourly_rate" value="<?=e($u['hourly_rate']??'')?>"></div>
        <label>Tarif journalier moyen</label><div><input name="daily_rate" value="<?=e($u['daily_rate']??'')?>"></div>
        <label>Société</label><div><input name="company" value="<?=e($u['company']??'Utilisateur interne')?>"></div>
        <label>Heures de travail (par semaine)</label><div><input name="weekly_hours" value="<?=e($u['weekly_hours']??'')?>"></div>
        <label>Date d'embauche</label><div><input type="date" name="hire_date" value="<?=e($u['hire_date']??'')?>"> <small>Maintenant</small></div>
        <label>Date de naissance</label><div><input type="date" name="birth_date" value="<?=e($u['birth_date']??'')?>"></div>
        <label>Rôle</label><div><select name="role"><?php foreach(user_roles() as $r): ?><option <?=($r===($u['role']??''))?'selected':''?>><?=e($r)?></option><?php endforeach; ?></select></div>
        <label>État</label><div><select name="status"><?php foreach(user_statuses() as $s): ?><option <?=($s===($u['status']??'Actif'))?'selected':''?>><?=e($s)?></option><?php endforeach; ?></select></div>
      </div>
    </div>

    <div class="form-actions"><button class="btn-orange" type="submit"><?= $id?'ENREGISTRER':'CRÉER L\'UTILISATEUR' ?></button><a class="btn-secondary" href="index.php?page=users">ANNULER</a></div>
  </form>
</div>
<script>
function generateUserPass(){const chars='abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789@$%';let p='';for(let i=0;i<12;i++)p+=chars[Math.floor(Math.random()*chars.length)];document.querySelector('.password-field').value=p;}
</script>
<?php include __DIR__.'/../layouts/footer.php'; ?>
