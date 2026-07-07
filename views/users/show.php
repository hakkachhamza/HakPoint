<?php
$title='Utilisateur';
include __DIR__.'/../layouts/header.php';
require_once __DIR__.'/_helpers.php';
$users=data_read('users',[]);
$u=find_row_by_id($users,(int)($_GET['id']??0));
if(!$u){ echo '<div class="panel">Utilisateur introuvable</div>'; include __DIR__.'/../layouts/footer.php'; return; }
$manager=user_find_user($users,$u['manager_id']??'');
$validator=user_find_user($users,$u['expense_validator']??'');
?>
<div class="user-show-page">
  <div class="user-tabs"><a class="active" href="index.php?page=user_show&id=<?=e($u['id'])?>"><i class="fa-solid fa-user"></i> Utilisateur</a><a href="index.php?page=user_permissions&id=<?=e($u['id'])?>">Permissions</a></div>
  <div class="user-head">
    <div class="avatar-card"><i class="fa-solid fa-user-tie"></i></div>
    <div class="user-head-info"><h2><?=e($u['username']??user_display_name($u))?> <span class="gender-symbol"><?=($u['gender']??'')==='Femme'?'♀':'♂'?></span></h2><p><?=e(user_display_name($u))?></p></div>
    <div class="user-nav"><a href="index.php?page=users">Retour liste</a><span>‹</span><span>›</span><br><span class="<?=user_status_badge($u['status']??'Actif')?>"><?=e($u['status']??'Actif')?></span></div>
  </div>
  <div class="user-info-grid">
    <div class="user-lines">
      <div><span>Identifiant</span><b><?=e($u['username']??'')?></b></div>
      <div><span>Type <i class="info-dot">i</i></span><b><span class="gray-pill"><?=e($u['external_user']??'Interne')?></span></b></div>
      <div><span>Salarié</span><b><?=!empty($u['employee'])?'☑':''?></b></div>
      <div><span>Responsable hiérarchique</span><b><?php if($manager): ?><i class="fa-solid fa-user"></i> <a class="ref" href="index.php?page=user_show&id=<?=e($manager['id'])?>"><?=e(user_display_name($manager))?></a><?php endif; ?></b></div>
      <div><span>Forcer le valideur des notes de frais <i class="info-dot">i</i></span><b><?php if($validator): ?><i class="fa-solid fa-user"></i> <a class="ref" href="index.php?page=user_show&id=<?=e($validator['id'])?>"><?=e(user_display_name($validator))?></a><?php endif; ?></b></div>
      <div><span>Poste/fonction</span><b><?=e($u['job']??'')?></b></div>
      <div><span>Heures de travail (par semaine)</span><b><?=e($u['weekly_hours']??'')?></b></div>
      <div><span>Salaire</span><b><?=e($u['salary']??'')?></b></div>
      <div><span>Tarif horaire moyen <i class="info-dot">i</i></span><b><?=e($u['hourly_rate']??'')?></b></div>
      <div><span>Tarif journalier moyen <i class="info-dot">i</i></span><b><?=e($u['daily_rate']??'')?></b></div>
      <div><span>Date d'embauche</span><b><?=e($u['hire_date']??'')?></b></div>
      <div><span>Date de naissance</span><b><?=e($u['birth_date']??'')?></b></div>
    </div>
    <div class="user-lines">
      <div><span>Lien tiers / contact</span><b class="muted">Cet utilisateur n'est ni un prospect, ni un client, ni un fournisseur</b></div>
      <div><span>Signature</span><b><?=e($u['signature']??'')?></b></div>
      <h4><i class="fa-solid fa-key"></i> Identifiants</h4>
      <div><span>Période de validité de l'identifiant</span><b><?=e(trim(($u['valid_from']??'').' - '.($u['valid_to']??''),' -'))?></b></div>
      <div><span>Dernière connexion</span><b><?=e($u['last_login']??'')?> <span class="muted"><?=!empty($u['previous_login'])?'(Précédent), '.e($u['previous_login']).' (Actuellement)':''?></span></b></div>
    </div>
  </div>
  <div class="user-actions"><a class="btn-light" href="index.php?page=user_email&id=<?=e($u['id'])?>">ENVOYER EMAIL</a><a class="btn-orange" href="index.php?page=user_edit&id=<?=e($u['id'])?>">MODIFIER</a><a class="btn-light" href="index.php?page=user_permissions&id=<?=e($u['id'])?>">PERMISSIONS</a><a class="btn-light" href="<?=csrf_url('index.php?page=user_delete&id='.(int)$u['id'])?>" onclick="return confirm('Désactiver / supprimer cet utilisateur ?')"><?=($u['status']??'Actif')==='Actif'?'DÉSACTIVER':'SUPPRIMER'?></a></div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
