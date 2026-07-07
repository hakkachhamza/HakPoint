<?php
$title='Permissions utilisateur';
include __DIR__.'/../layouts/header.php';
require_once __DIR__.'/_helpers.php';
$users=data_read('users',[]);
$id=(int)($_GET['id']??0);
$u=find_row_by_id($users,$id);
if(!$u){ echo '<div class="panel">Utilisateur introuvable</div>'; include __DIR__.'/../layouts/footer.php'; return; }
$perms=$u['permissions'] ?? [];
$groups=user_permission_groups();
$icons=function_exists('user_permission_group_icons') ? user_permission_group_icons() : [];
$total=0; foreach($groups as $items){ $total += count($items); }
$active=count(array_intersect(user_all_permission_keys(), $perms));
$isAdmin=(($u['role']??'')==='Administrateur' || ($u['username']??'')==='admin');
?>
<div class="user-show-page permissions-page permissions-modern-page">
  <div class="user-tabs">
    <a href="index.php?page=user_show&id=<?=e($u['id'])?>"><i class="fa-solid fa-user"></i> Utilisateur</a>
    <a class="active" href="index.php?page=user_permissions&id=<?=e($u['id'])?>"><i class="fa-solid fa-shield-halved"></i> Permissions</a>
  </div>

  <div class="perm-hero">
    <div class="perm-user-card">
      <div class="perm-avatar"><i class="fa-solid fa-user-shield"></i></div>
      <div>
        <div class="perm-eyebrow">Gestion des accès</div>
        <h2><?=e(user_display_name($u))?></h2>
        <p><?=e($u['username']??'')?> · <?=e($u['role']??'Utilisateur')?> · <span class="<?=user_status_badge($u['status']??'Actif')?>"><?=e($u['status']??'Actif')?></span></p>
      </div>
    </div>
    <div class="perm-summary">
      <div><b id="permActiveCount"><?=e($active)?></b><span>Actives</span></div>
      <div><b><?=e($total)?></b><span>Total</span></div>
      <div><b><?=e(count($groups))?></b><span>Sections</span></div>
    </div>
  </div>

  <?php if($isAdmin): ?>
    <div class="perm-note"><i class="fa-solid fa-circle-info"></i> Cet utilisateur est administrateur. Il garde un accès complet même si certaines cases sont décochées.</div>
  <?php endif; ?>

  <form method="post" action="index.php?page=user_permissions_save&id=<?=e($u['id'])?>" class="perm-form">
    <?=csrf_field()?>
    <div class="perm-toolbar">
      <div class="perm-search-wrap"><i class="fa-solid fa-magnifying-glass"></i><input type="search" id="permSearch" placeholder="Rechercher une section ou une permission..."></div>
      <div class="perm-toolbar-actions">
        <button type="button" class="btn-secondary" onclick="setAllPerms(true)"><i class="fa-solid fa-check-double"></i> Tout activer</button>
        <button type="button" class="btn-secondary" onclick="setAllPerms(false)"><i class="fa-solid fa-ban"></i> Tout désactiver</button>
        <button class="btn-orange" type="submit"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
      </div>
    </div>

    <div class="perm-grid" id="permGrid">
      <?php foreach($groups as $group=>$items):
        $groupKeys=array_keys($items);
        $checked=count(array_intersect($groupKeys,$perms));
        $icon=$icons[$group] ?? 'fa-folder';
      ?>
      <section class="perm-card" data-perm-card data-search="<?=e(strtolower($group.' '.implode(' ', $items)))?>">
        <div class="perm-card-head">
          <div class="perm-card-title"><span><i class="fa-solid <?=e($icon)?>"></i></span><div><h3><?=e($group)?></h3><small><b data-group-count><?=e($checked)?></b> / <?=e(count($items))?> permissions</small></div></div>
          <div class="perm-card-actions"><button type="button" onclick="toggleCard(this,true)">Tout</button><button type="button" onclick="toggleCard(this,false)">Aucun</button></div>
        </div>
        <div class="perm-items">
          <?php foreach($items as $key=>$label): ?>
          <label class="perm-item">
            <input class="perm-check" type="checkbox" name="permissions[]" value="<?=e($key)?>" <?=in_array($key,$perms,true)?'checked':''?>>
            <span class="modern-switch"></span>
            <span class="perm-copy"><b><?=e($key)?></b><small><?=e($label)?></small></span>
          </label>
          <?php endforeach; ?>
        </div>
      </section>
      <?php endforeach; ?>
    </div>

    <div class="perm-bottom-actions">
      <a class="btn-secondary" href="index.php?page=user_show&id=<?=e($u['id'])?>"><i class="fa-solid fa-arrow-left"></i> Annuler</a>
      <button class="btn-orange" type="submit"><i class="fa-solid fa-floppy-disk"></i> Enregistrer les permissions</button>
    </div>
  </form>
</div>
<script>
function refreshPermCounts(){
  const all=[...document.querySelectorAll('.perm-check')];
  const active=all.filter(x=>x.checked).length;
  const activeBox=document.getElementById('permActiveCount');
  if(activeBox) activeBox.textContent=active;
  document.querySelectorAll('[data-perm-card]').forEach(card=>{
    const count=card.querySelectorAll('.perm-check:checked').length;
    const target=card.querySelector('[data-group-count]');
    if(target) target.textContent=count;
  });
}
function setAllPerms(v){document.querySelectorAll('.perm-check').forEach(x=>x.checked=v); refreshPermCounts();}
function toggleCard(btn,v){const card=btn.closest('[data-perm-card]'); if(!card) return; card.querySelectorAll('.perm-check').forEach(x=>x.checked=v); refreshPermCounts();}
document.querySelectorAll('.perm-check').forEach(x=>x.addEventListener('change', refreshPermCounts));
const permSearch=document.getElementById('permSearch');
if(permSearch){ permSearch.addEventListener('input', function(){ const q=this.value.trim().toLowerCase(); document.querySelectorAll('[data-perm-card]').forEach(card=>{ card.style.display = (!q || (card.dataset.search||'').includes(q)) ? '' : 'none'; }); }); }
</script>
<?php include __DIR__.'/../layouts/footer.php'; ?>
