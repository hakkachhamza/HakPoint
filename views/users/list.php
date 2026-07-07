<?php
$title='Liste des utilisateurs';
include __DIR__.'/../layouts/header.php';
require_once __DIR__.'/_helpers.php';
$users=data_read('users',[]); [$users,$usersTotal,$usersPage,$usersPages]=ge_list_slice($users);
?>
<div class="clean-list-page users-clean-list">
  <form method="post" action="index.php?page=user_bulk_action" id="usersBulkForm"><?=csrf_field()?>
    <div class="clean-list-head">
      <div class="clean-title"><i class="fa-solid fa-user-tie"></i><span>(<?=e($usersTotal)?>)</span></div>
    </div>

    <div class="bulkbar clean-actionbar" id="usersBulkBar">
      <select name="action">
        <option value="">-- Sélectionner l'action --</option>
        <option value="activate">Définir sur le statut Actif</option>
        <option value="disable">Désactiver</option>
        <option value="delete">Supprimer</option>
      </select>
      <button class="bulk-confirm" type="submit">CONFIRMER</button>
    </div>

    <div class="clean-table-box">
      <table class="clean-table users-table" id="usersTable">
        <thead>
          <tr class="clean-filters">
            <th><input data-filter="0" placeholder="Identifiant"></th>
            <th><input data-filter="1" placeholder="Nom"></th>
            <th><input data-filter="2" placeholder="Prénom"></th>
            <th><input data-filter="3" placeholder="Responsable"></th>
            <th><input data-filter="4" placeholder="Tél pro."></th>
            <th><input data-filter="5" placeholder="Tél portable"></th>
            <th><input data-filter="6" placeholder="Email"></th>
            <th><input data-filter="7" placeholder="Société"></th>
            <th><input data-filter="8" placeholder="Connexion"></th>
            <th><select data-filter="9"><option value="">Tous</option><option>Actif</option><option>Désactivé</option></select></th>
            <th class="clean-row-tools"><button type="button" onclick="filterCleanTable('usersTable')"><i class="fa-solid fa-magnifying-glass"></i></button><button type="button" onclick="clearCleanFilters('usersTable')"><i class="fa-solid fa-xmark"></i></button></th>
          </tr>
          <tr>
            <th>Identifiant</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Responsable hiérarchique</th>
            <th>Tél pro.</th>
            <th>Tél portable</th>
            <th>EMail</th>
            <th>Société</th>
            <th>Dernière connexion</th>
            <th>État</th>
            <th><i class="fa-solid fa-list"></i> <input type="checkbox" onclick="toggleAllUsers(this)"></th>
          </tr>
        </thead>
        <tbody>
          <?php if(!$users): ?><tr><td colspan="11" class="empty-row">Aucun utilisateur</td></tr><?php endif; ?>
          <?php foreach($users as $u): $manager=user_find_user($users,$u['manager_id']??''); ?>
          <tr>
            <td><a class="ref" href="index.php?page=user_show&id=<?=e($u['id'])?>"><i class="fa-solid fa-user-tie user-mini"></i> <?=e($u['username']??'')?></a><?=($u['role']??'')==='Administrateur'?' <span class="star">★</span>':''?></td>
            <td><?=e($u['name']??'')?></td>
            <td><?=e($u['firstname']??'')?></td>
            <td><?php if($manager): ?><a class="ref" href="index.php?page=user_show&id=<?=e($manager['id'])?>"><i class="fa-solid fa-user-tie user-mini"></i> <?=e(user_display_name($manager))?></a><?=($manager['role']??'')==='Administrateur'?' <span class="star">★</span>':''?><?php endif; ?></td>
            <td><?=e($u['phone']??'')?></td>
            <td><?=e($u['mobile']??'')?></td>
            <td><?=e($u['email']??'')?></td>
            <td class="muted"><?=e($u['company']??'Utilisateur interne')?></td>
            <td><?=e($u['last_login']??'')?></td>
            <td><span class="<?=user_status_badge($u['status']??'Actif')?>"><?=e($u['status']??'Actif')?></span></td>
            <td><input class="user-check" type="checkbox" name="ids[]" value="<?=e($u['id'])?>" onchange="updateUsersBulkBar()"></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </form>
  <?=ge_list_pager($usersTotal,$usersPage,$usersPages,'p',['page'=>'users'])?>
</div>
<script>
function updateUsersBulkBar(){document.getElementById('usersBulkBar').style.display=document.querySelectorAll('.user-check:checked').length?'flex':'none'}
function toggleAllUsers(x){document.querySelectorAll('.user-check').forEach(c=>c.checked=x.checked);updateUsersBulkBar()}
</script>
<?php include __DIR__.'/../layouts/footer.php'; ?>
