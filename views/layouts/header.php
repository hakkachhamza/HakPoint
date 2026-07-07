<?php ge_begin_translate(); ?>
<?php
$title = $title ?? 'Global Energie';
$cu = current_user();
$displayName = ge_user_full_name($cu);
$roleName = $cu['role'] ?? 'Utilisateur';
$userEmail = trim((string)($cu['email'] ?? ''));
$userLogin = trim((string)($cu['username'] ?? ''));
$userStatus = trim((string)($cu['status'] ?? 'Actif'));
$userTenant = trim((string)($cu['tenant_slug'] ?? (function_exists('ge_current_tenant_slug') ? ge_current_tenant_slug() : '')));
$stockData = function_exists('ge_header_stock_notifications') ? ge_header_stock_notifications(6) : ['count'=>0,'items'=>[]];
$stockNotifications = $stockData['items'] ?? [];
$stockNotificationCount = (int)($stockData['count'] ?? count($stockNotifications));
$stockNotificationPreview = $stockNotifications;
$stockAlertSignature = implode('|', array_map(function($n){ return (int)($n['id'] ?? 0).':'.(string)($n['qty'] ?? ''); }, $stockNotificationPreview));
?>
<!doctype html><html lang="<?=e(app_setting('language','fr'))?>" dir="<?=app_setting('language','fr')==='ar'?'rtl':'ltr'?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?></title><link rel="icon" href="assets/images/global-energie-icon.png"><link rel="stylesheet" href="assets/css/style.css?v=20260627-settings-dropdown-v3"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></head><body>
<div class="mobile-overlay" data-sidebar-close></div>
<div class="app">
<aside class="sidebar">
 <div class="brand brand-logo-only"><img class="brand-logo-img brand-logo-full" src="assets/images/global-energie-logo.png" alt="Global Energie"><img class="brand-logo-mini" src="assets/images/global-energie-icon.png" alt="Global Energie"></div>
 <nav>
  <a class="<?=active('dashboard')?>" href="index.php?page=dashboard"><i class="fa-solid fa-chart-line"></i> <?=ge_t('Tableau de bord')?></a>
<?php if(ge_module_enabled('tiers')): ?>
  <div class="menu-group <?=in_array(current_page(),['tiers','tiers_new','tiers_show','tiers_edit','tiers_merge','prospects','prospect_new','clients','client_new','suppliers','supplier_new'])?'open':''?> tiers-menu">
    <a class="<?=in_array(current_page(),['tiers','tiers_new','tiers_show','tiers_edit','tiers_merge','prospects','prospect_new','clients','client_new','suppliers','supplier_new'])?'active':''?>" href="index.php?page=tiers" data-menu-toggle><i class="fa-solid fa-city"></i> <?=ge_t('Tiers')?> <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu tiers-submenu">
      <a href="index.php?page=tiers_new">Nouveau tiers</a>
      <a href="index.php?page=tiers">Liste</a>
      <div class="submenu-heading">Prospects</div>
      <a href="index.php?page=prospects">Liste prospects</a>
      <a href="index.php?page=prospect_new">Nouveau prospect</a>
      <div class="submenu-heading">Clients</div>
      <a href="index.php?page=clients">Liste clients</a>
      <a href="index.php?page=client_new">Nouveau client</a>
      <div class="submenu-heading">Fournisseurs</div>
      <a href="index.php?page=suppliers">Liste fournisseurs</a>
      <a href="index.php?page=supplier_new">Nouveau fournisseur</a>
    </div>
  </div>
<?php endif; ?>
<?php if(ge_module_enabled('products')): ?>
  <div class="menu-group <?=in_array(current_page(),['products','product_new','product_show','product_stock'])?'open':''?>">
    <a class="<?=in_array(current_page(),['products','product_new','product_show','product_stock'])?'active':''?>" href="index.php?page=products" data-menu-toggle><i class="fa-solid fa-boxes-stacked"></i> <?=ge_t('Produits')?> <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu"><a href="index.php?page=product_new">Nouveau produit</a><a href="index.php?page=products">Liste</a><a href="index.php?page=product_stock">Stocks</a></div>
  </div>
<?php endif; ?>
<?php if(ge_module_enabled('sales')): ?>
  <div class="menu-group <?=in_array(current_page(),['quotes','quote_new','quote_show','quote_stats'])?'open':''?>">
    <a class="<?=in_array(current_page(),['quotes','quote_new','quote_show','quote_stats'])?'active':''?>" href="index.php?page=quotes" data-menu-toggle><i class="fa-solid fa-file-pen"></i> <?=ge_t('Devis')?> <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu devis-submenu">
      <a href="index.php?page=quote_new">Nouveau Devis</a>
      <a href="index.php?page=quotes">Liste</a>
      <a href="index.php?page=quotes&status=Brouillon">Brouillons</a>
      <a href="index.php?page=quotes&status=Ouvert">Ouvert</a>
      <a href="index.php?page=quotes&status=Signée (à facturer)">Signée (à facturer)</a>
      <a href="index.php?page=quotes&status=Non signée (fermée)">Non signée (fermée)</a>
      <a href="index.php?page=quotes&status=Facturée">Facturée</a>
      <a href="index.php?page=quote_stats">Statistiques</a>
    </div>
  </div>
<?php endif; ?>
<?php if(ge_module_enabled('sales')): ?>
  <div class="menu-group <?=in_array(current_page(),['orders','order_new','order_show','order_edit','order_email','order_stats'])?'open':''?> commandes-menu">
    <a class="<?=in_array(current_page(),['orders','order_new','order_show','order_edit','order_email','order_stats'])?'active':''?>" href="index.php?page=orders" data-menu-toggle><i class="fa-solid fa-file-invoice"></i> <?=ge_t('Commandes')?> <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu commandes-submenu">
      <a href="index.php?page=order_new">Nouvelle commande</a>
      <a href="index.php?page=orders">Liste</a>
      <a href="index.php?page=orders&status=Brouillon">Brouillon</a>
      <a href="index.php?page=orders&status=Validée">Validée</a>
      <a href="index.php?page=orders&status=En cours">En cours</a>
      <a href="index.php?page=orders&status=Livrée">Livrée</a>
      <a href="index.php?page=orders&status=Annulée">Annulée</a>
      <a href="index.php?page=order_stats">Statistiques</a>
    </div>
  </div>
<?php endif; ?>
<?php if(ge_module_enabled('sales')): ?>
  <div class="menu-group <?=in_array(current_page(),['invoices','invoice_new','invoice_show','invoice_stats'])?'open':''?>">
    <a class="<?=in_array(current_page(),['invoices','invoice_new','invoice_show','invoice_stats'])?'active':''?>" href="index.php?page=invoices" data-menu-toggle><i class="fa-solid fa-file-invoice-dollar"></i> <?=ge_t('Factures clients')?> <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu facture-submenu">
      <a href="index.php?page=invoice_new">Nouvelle facture</a>
      <a href="index.php?page=invoices">Liste</a>
      <a href="index.php?page=invoices&status=Brouillon">Brouillon</a>
      <a href="index.php?page=invoices&status=Impayée">Impayée</a>
      <a href="index.php?page=invoices&status=Payée">Payée</a>
      <a href="index.php?page=invoices&status=Abandonnée">Abandonnée</a>
      <a href="index.php?page=invoice_stats">Statistiques</a>
    </div>
  </div>
<?php endif; ?>
  <?php if(ge_module_enabled('purchases') || ge_module_enabled('sales') || ge_module_enabled('settings')): ?>
  <?php $avPages=['purchases','purchase_orders','supplier_invoices','purchase_order_show','supplier_invoice_show','credit_notes','credit_notes_list','credit_note_show','approvals','approvals_list','approval_show']; ?>
  <div class="menu-group <?=in_array(current_page(),$avPages,true)?'open':''?>">
    <a class="<?=in_array(current_page(),$avPages,true)?'active':''?>" href="<?=ge_module_enabled('purchases')?'index.php?page=purchases':'index.php?page=approvals'?>" data-menu-toggle><i class="fa-solid fa-cart-shopping"></i> Achats & Validation <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu">
      <?php if(ge_module_enabled('purchases')): ?>
        <a href="index.php?page=purchases">Achats fournisseurs</a>
        <a href="index.php?page=purchase_orders">Bons de commande fournisseur</a>
        <a href="index.php?page=supplier_invoices">Factures fournisseurs</a>
      <?php endif; ?>
      <?php if(ge_module_enabled('sales')): ?>
        <a href="index.php?page=credit_notes">Avoirs clients</a>
        <a href="index.php?page=credit_notes_list">Liste avoirs clients</a>
      <?php endif; ?>
      <?php if(ge_module_enabled('settings')): ?>
        <a href="index.php?page=approvals">Validations</a>
        <a href="index.php?page=approvals_list">Liste validations</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
<?php if(ge_module_enabled('stock')): ?>
  <div class="menu-group <?=in_array(current_page(),['warehouses','warehouse_new','warehouse_show','warehouse_edit','warehouse_movements','warehouse_transfer','warehouse_stock_at_date'])?'open':''?>">
    <a class="<?=in_array(current_page(),['warehouses','warehouse_new','warehouse_show','warehouse_edit','warehouse_movements','warehouse_transfer','warehouse_stock_at_date'])?'active':''?>" href="index.php?page=warehouses" data-menu-toggle><i class="fa-solid fa-box-open"></i> <?=ge_t('Entrepôts')?> <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu warehouse-submenu">
      <a href="index.php?page=warehouse_new">Nouvel entrepôt</a>
      <a href="index.php?page=warehouses">Liste</a>
      <a href="index.php?page=warehouse_movements">Mouvements</a>
      <a href="index.php?page=warehouse_transfer">Changement de stock en</a>
      <a href="index.php?page=warehouse_stock_at_date">Stock à date</a>
    </div>
  </div>
<?php endif; ?>
<?php if(ge_module_enabled('sales')): ?>
  <div class="menu-group <?=in_array(current_page(),['expeditions','expedition_new','expedition_show','expedition_stats'])?'open':''?>">
    <a class="<?=in_array(current_page(),['expeditions','expedition_new','expedition_show','expedition_stats'])?'active':''?>" href="index.php?page=expeditions" data-menu-toggle><i class="fa-solid fa-dolly"></i> <?=ge_t('Expéditions')?> <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu expedition-submenu">
      <a href="index.php?page=expedition_new">Nouvelle expédition</a>
      <a href="index.php?page=expeditions">Liste</a>
      <a href="index.php?page=expedition_stats">Statistiques</a>
    </div>
  </div>
<?php endif; ?>
<?php if(ge_module_enabled('purchases')): ?>
  <div class="menu-group <?=in_array(current_page(),['receptions','reception_new','reception_show','reception_stats'])?'open':''?>">
    <a class="<?=in_array(current_page(),['receptions','reception_new','reception_show','reception_stats'])?'active':''?>" href="index.php?page=receptions" data-menu-toggle><i class="fa-solid fa-cart-flatbed"></i> <?=ge_t('Réceptions')?> <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu reception-submenu">
      <a href="index.php?page=reception_new">Nouvelle réception</a>
      <a href="index.php?page=receptions">Liste</a>
      <a href="index.php?page=reception_stats">Statistiques</a>
    </div>
  </div>
<?php endif; ?>
<?php if(ge_module_enabled('settings')): ?>
  <div class="menu-group <?=in_array(current_page(),['users','user_new','user_show','user_edit','user_permissions','user_hierarchy','user_email'])?'open':''?>">
    <a class="<?=in_array(current_page(),['users','user_new','user_show','user_edit','user_permissions','user_hierarchy','user_email'])?'active':''?>" href="index.php?page=users" data-menu-toggle><i class="fa-solid fa-users-gear"></i> <?=ge_t('Utilisateurs')?> <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu users-submenu">
      <a href="index.php?page=user_new">Nouvel utilisateur</a>
      <a href="index.php?page=users">Liste des utilisateurs</a>
      <a href="index.php?page=user_hierarchy">Vue hiérarchique</a>
    </div>
  </div>
<?php endif; ?>
<?php if(ge_module_enabled('api')): ?>
  <div class="menu-group <?=in_array(current_page(),['api','api_save'])?'open':''?>">
    <a class="<?=in_array(current_page(),['api','api_save'])?'active':''?>" href="index.php?page=api" data-menu-toggle><i class="fa-solid fa-code"></i> API <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu api-submenu">
      <a href="index.php?page=api"><i class="fa-solid fa-circle-info"></i> Aperçu API</a>
      <a href="index.php?page=api#api-status">Statut connexion</a>
      <a href="index.php?page=api#api-docs">Documentation</a>
      <a href="index.php?page=api#api-embed">Code à mettre sur site</a>
    </div>
  </div><?php endif; ?>


  <?php if(ge_module_enabled('finance')): ?>
  <div class="menu-group <?=in_array(current_page(),['bank_accounts','payment_modes','payments','supplier_payments','currencies'])?'open':''?>">
    <a class="<?=in_array(current_page(),['bank_accounts','payment_modes','payments','supplier_payments','currencies'])?'active':''?>" href="index.php?page=bank_accounts" data-menu-toggle><i class="fa-solid fa-building-columns"></i> Finance <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu">
      <a href="index.php?page=bank_accounts">Comptes bancaires</a>
      <a href="index.php?page=payment_modes">Modes de paiement</a>
      <a href="index.php?page=payments">Paiements clients</a>
      <a href="index.php?page=supplier_payments">Paiements fournisseurs</a>
      <a href="index.php?page=currencies">Devises</a>
    </div>
  </div>
  <?php endif; ?>
  <?php if(ge_module_enabled('accounting')): ?>
  <div class="menu-group <?=in_array(current_page(),['accounting'])?'open':''?>">
    <a class="<?=active('accounting')?>" href="index.php?page=accounting"><i class="fa-solid fa-scale-balanced"></i> Comptabilité</a>
  </div>
  <?php endif; ?>
    <?php if(ge_module_enabled('documents')): ?>
  <div class="menu-group <?=in_array(current_page(),['documents'])?'open':''?>">
    <a class="<?=active('documents')?>" href="index.php?page=documents"><i class="fa-solid fa-folder-open"></i> Documents</a>
  </div>
  <?php endif; ?>
  <?php if(ge_module_enabled('signatures')): ?>
  <div class="menu-group <?=in_array(current_page(),['signatures'])?'open':''?> signatures-menu">
    <a class="<?=active('signatures')?>" href="index.php?page=signatures&tab=send" data-menu-toggle><i class="fa-solid fa-signature"></i> Signatures <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu signatures-submenu">
      <a href="index.php?page=signatures&tab=send"><i class="fa-solid fa-paper-plane"></i> Envoyer PDF</a>
      <a href="index.php?page=signatures&tab=signed"><i class="fa-solid fa-circle-check"></i> Liste signés</a>
      <a href="index.php?page=signatures&tab=pending"><i class="fa-regular fa-clock"></i> Liste non signés</a>
    </div>
  </div>
  <?php endif; ?>
  <?php if(ge_module_enabled('projects') || ge_module_enabled('agenda')): ?>
  <div class="menu-group <?=in_array(current_page(),['projects','agenda'])?'open':''?>">
    <a class="<?=in_array(current_page(),['projects','agenda'])?'active':''?>" href="index.php?page=projects" data-menu-toggle><i class="fa-solid fa-calendar-check"></i> Projets & Agenda <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu">
      <?php if(ge_module_enabled('projects')): ?><a href="index.php?page=projects">Projets</a><?php endif; ?>
      <?php if(ge_module_enabled('agenda')): ?><a href="index.php?page=agenda">Agenda / relances</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php if(ge_module_enabled('reports') || ge_module_enabled('analytics')): ?>
  <div class="menu-group <?=in_array(current_page(),['reports','analytics'])?'open':''?>">
    <a class="<?=in_array(current_page(),['reports','analytics'])?'active':''?>" href="index.php?page=reports" data-menu-toggle><i class="fa-solid fa-chart-simple"></i> <?=ge_t('Rapports')?> <i class="fa-solid fa-chevron-down menu-arrow"></i></a>
    <div class="submenu">
      <?php if(ge_module_enabled('reports')): ?><a href="index.php?page=reports">Rapports tableau</a><?php endif; ?>
      <?php if(ge_module_enabled('analytics')): ?><a href="index.php?page=analytics">Analyse / BI</a><?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
 </nav>
</aside>
<main class="main">
<header class="topbar">
  <button class="mobile-menu-btn" type="button" data-sidebar-toggle aria-label="Ouvrir le menu"><i class="fa-solid fa-bars"></i></button>
  <div class="page-title"><b><?=e(ge_t($title))?></b></div>
  <div class="topbar-actions">
    <?php if(ge_module_enabled('api')): ?>
      <a class="header-quick-icon" href="index.php?page=api#api-docs" title="Aide" aria-label="Aide"><i class="fa-solid fa-question"></i></a>
    <?php endif; ?>
    <?php if(ge_module_enabled('settings')): ?>
      <div class="header-settings-menu">
        <button type="button" class="header-quick-icon" id="headerSettingsBtn" title="Paramètres" aria-label="Paramètres" aria-haspopup="true" aria-expanded="false"><i class="fa-solid fa-gear"></i></button>
        <div class="header-settings-dropdown" id="headerSettingsDropdown">
          <div class="header-dropdown-title">
            <strong><?=ge_t('Paramètres')?></strong>
            <small><?=ge_t('Profil, langue et sécurité')?></small>
          </div>
          <a class="settings-drop-item" href="index.php?page=settings#profile-section">
            <span><i class="fa-solid fa-circle-user"></i></span>
            <div><strong><?=ge_t('Profil')?></strong><small><?=ge_t('Photo, compte et préférences')?></small></div>
          </a>
          <a class="settings-drop-item" href="index.php?page=settings#language-section">
            <span><i class="fa-solid fa-language"></i></span>
            <div><strong><?=ge_t('Langue')?></strong><small><?=ge_t('Français, English ou العربية')?></small></div>
          </a>
          <a class="settings-drop-item" href="index.php?page=settings#security-section">
            <span><i class="fa-solid fa-shield-halved"></i></span>
            <div><strong><?=ge_t('Sécurité')?></strong><small><?=ge_t('Mot de passe et double authentification')?></small></div>
          </a>
        </div>
      </div>
    <?php endif; ?>
    <div class="header-notif-menu">
      <button type="button" class="header-icon-btn" id="headerNotifBtn" aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
        <i class="fa-regular fa-bell"></i>
        <?php if($stockNotificationCount > 0): ?><span class="notif-badge"><?= $stockNotificationCount > 99 ? '99+' : (int)$stockNotificationCount ?></span><?php endif; ?>
      </button>
      <div class="header-notif-dropdown" id="headerNotifDropdown">
        <div class="header-dropdown-title">
          <strong><?=ge_t('Notifications')?></strong>
          <small><?= $stockNotificationCount > 0 ? e($stockNotificationCount.' alerte(s) stock') : e('Aucune alerte stock') ?></small>
        </div>
        <?php if(empty($stockNotificationPreview)): ?>
          <div class="notif-empty"><i class="fa-regular fa-circle-check"></i> <?=ge_t('Aucune alerte stock pour le moment.')?></div>
        <?php else: ?>
          <?php foreach($stockNotificationPreview as $notif): ?>
            <a class="notif-item" href="<?=product_url($notif['id'], 'stock')?>">
              <span class="notif-item-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
              <span class="notif-item-text">
                <strong><?=e(($notif['ref'] ? $notif['ref'].' - ' : '').$notif['label'])?></strong>
                <small><?=ge_t('Stock')?>: <?=e((string)$notif['qty'])?> / <?=ge_t('Alerte')?>: <?=e((string)$notif['alert'])?></small>
              </span>
            </a>
          <?php endforeach; ?>
          <div class="header-dropdown-footer"><a href="index.php?page=product_stock"><?=ge_t('Voir tous les stocks')?></a></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="header-user-menu">
      <?php $avatar=user_avatar_src($cu); ?>
      <button type="button" class="header-user-btn header-user-icon-only" id="headerUserBtn" aria-haspopup="true" aria-expanded="false" aria-label="Profil">
        <span class="header-avatar"><?php if($avatar): ?><img src="<?=e($avatar)?>" alt="Profile"><?php else: ?><i class="fa-solid fa-circle-user"></i><?php endif; ?></span>
      </button>
      <div class="header-user-dropdown" id="headerUserDropdown">
        <div class="header-profile-summary">
          <span class="header-profile-avatar"><?php if($avatar): ?><img src="<?=e($avatar)?>" alt="Profile"><?php else: ?><i class="fa-solid fa-circle-user"></i><?php endif; ?></span>
          <div class="header-profile-info">
            <strong><?=e($displayName)?></strong>
            <small><i class="fa-solid fa-shield-halved"></i> <?=e($roleName)?></small>
            <?php if($userLogin !== ''): ?><small><i class="fa-solid fa-user"></i> <?=e($userLogin)?></small><?php endif; ?>
            <?php if($userEmail !== ''): ?><small><i class="fa-solid fa-envelope"></i> <?=e($userEmail)?></small><?php endif; ?>
            <?php if($userTenant !== ''): ?><small><i class="fa-solid fa-building"></i> <?=e($userTenant)?></small><?php endif; ?>
            <?php if($userStatus !== ''): ?><small><i class="fa-solid fa-circle-check"></i> <?=e($userStatus)?></small><?php endif; ?>
          </div>
        </div>
        <a href="index.php?page=settings"><i class="fa-solid fa-circle-user"></i> <?=ge_t('Profil')?></a>
        <a href="index.php?page=users"><i class="fa-solid fa-users-gear"></i> <?=ge_t('Utilisateurs')?></a>
        <a href="index.php?page=api"><i class="fa-solid fa-code"></i> API</a>
        <a href="index.php?page=reports"><i class="fa-solid fa-chart-simple"></i> <?=ge_t('Rapports')?></a>
        <a href="index.php?page=backups"><i class="fa-solid fa-database"></i> Sauvegardes</a>
        <a href="index.php?page=settings"><i class="fa-solid fa-gear"></i> <?=ge_t('Paramètres')?></a>
        <a href="index.php?page=logout"><i class="fa-solid fa-right-from-bracket"></i> <?=ge_t('Déconnexion')?></a>
      </div>
    </div>
  </div>
</header>

<script>
(function(){
  function setupDropdown(buttonId, dropdownId){
    const btn = document.getElementById(buttonId);
    const drop = document.getElementById(dropdownId);
    if(!btn || !drop) return null;
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      const willOpen = !drop.classList.contains('show');
      document.querySelectorAll('.header-user-dropdown.show, .header-notif-dropdown.show, .header-settings-dropdown.show').forEach(function(other){
        if(other !== drop) other.classList.remove('show');
      });
      document.querySelectorAll('#headerUserBtn, #headerNotifBtn, #headerSettingsBtn').forEach(function(otherBtn){
        if(otherBtn !== btn) otherBtn.setAttribute('aria-expanded', 'false');
      });
      drop.classList.toggle('show', willOpen);
      btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
    drop.addEventListener('click', function(e){ e.stopPropagation(); });
    return {btn, drop};
  }

  const dropdowns = [
    setupDropdown('headerUserBtn', 'headerUserDropdown'),
    setupDropdown('headerNotifBtn', 'headerNotifDropdown'),
    setupDropdown('headerSettingsBtn', 'headerSettingsDropdown')
  ].filter(Boolean);

  document.addEventListener('click', function(){
    dropdowns.forEach(function(item){
      item.drop.classList.remove('show');
      item.btn.setAttribute('aria-expanded', 'false');
    });
  });
})();
</script>
<section class="content">
