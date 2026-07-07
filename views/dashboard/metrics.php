<div class="dashboard-kpi-strip">
  <?php
  $tiersTotal = count($tiers ?? []);
  $contactsTotal = 0;
  foreach(($tiers ?? []) as $t){
      if(trim((string)($t['email'] ?? '')) !== '' || trim((string)($t['phone'] ?? '')) !== '' || trim((string)($t['mobile'] ?? '')) !== '') $contactsTotal++;
  }
  if(!function_exists('hp_dashboard_kpi_value')){
      /**
       * Real dashboard mode: never inject demo/fallback numbers.
       * A new/empty database must display 0 until real records are created.
       */
      function hp_dashboard_kpi_value($value, $fallback = 0){
          return max(0, (int)($value ?? 0));
      }
  }
  $kpis = [
    ['label'=>'Utilisateurs','value'=>hp_dashboard_kpi_value($dashboard['Utilisateurs'] ?? 0),'icon'=>'fa-user-tie','accent'=>'brown','url'=>'index.php?page=users'],
    ['label'=>'Clients','value'=>hp_dashboard_kpi_value($dashboard['Clients'] ?? 0),'icon'=>'fa-building','accent'=>'purple','url'=>'index.php?page=clients'],
    ['label'=>'Prospects','value'=>hp_dashboard_kpi_value($dashboard['Prospects'] ?? 0),'icon'=>'fa-building-user','accent'=>'purple','url'=>'index.php?page=prospects'],
    ['label'=>'Fournisseurs','value'=>hp_dashboard_kpi_value($dashboard['Fournisseurs'] ?? 0),'icon'=>'fa-truck-field','accent'=>'purple','url'=>'index.php?page=suppliers'],
    ['label'=>'Contacts','value'=>hp_dashboard_kpi_value($contactsTotal),'icon'=>'fa-address-book','accent'=>'blue','url'=>'index.php?page=tiers'],
    ['label'=>'Produits','value'=>hp_dashboard_kpi_value($productGoods ?? 0),'icon'=>'fa-cube','accent'=>'gold','url'=>'index.php?page=products'],
    ['label'=>'Services','value'=>hp_dashboard_kpi_value($productServices ?? 0),'icon'=>'fa-briefcase','accent'=>'gold','url'=>'index.php?page=products'],
    ['label'=>'Propositions/Devis','value'=>hp_dashboard_kpi_value($dashboard['Devis'] ?? 0),'icon'=>'fa-file-signature','accent'=>'green','url'=>'index.php?page=quotes'],
    ['label'=>'Commandes','value'=>hp_dashboard_kpi_value($dashboard['Commandes'] ?? 0),'icon'=>'fa-file-lines','accent'=>'teal','url'=>'index.php?page=orders'],
    ['label'=>'Factures clients','value'=>hp_dashboard_kpi_value($dashboard['Factures'] ?? 0),'icon'=>'fa-file-invoice-dollar','accent'=>'green','url'=>'index.php?page=invoices'],
    ['label'=>'Expéditions','value'=>hp_dashboard_kpi_value($dashboard['Expéditions'] ?? 0),'icon'=>'fa-dolly','accent'=>'blue','url'=>'index.php?page=expeditions'],
    ['label'=>'Réceptions','value'=>hp_dashboard_kpi_value($dashboard['Réceptions'] ?? 0),'icon'=>'fa-cart-flatbed','accent'=>'blue','url'=>'index.php?page=receptions'],
    ['label'=>'Achats','value'=>hp_dashboard_kpi_value($dashboard['Achats'] ?? 0),'icon'=>'fa-cart-shopping','accent'=>'green','url'=>'index.php?page=purchases'],
    ['label'=>'Avoirs','value'=>hp_dashboard_kpi_value($dashboard['Avoirs'] ?? 0),'icon'=>'fa-file-circle-minus','accent'=>'orange','url'=>'index.php?page=credit_notes'],
    ['label'=>'Validations','value'=>hp_dashboard_kpi_value($dashboard['Validations'] ?? 0),'icon'=>'fa-clipboard-check','accent'=>'purple','url'=>'index.php?page=approvals'],
  ];
  foreach($kpis as $k): ?>
    <a class="mini-kpi-card" href="<?=e($k['url'])?>">
      <span class="mini-kpi-icon <?=e($k['accent'])?>"><i class="fa-solid <?=e($k['icon'])?>"></i></span>
      <span class="mini-kpi-text"><b><?=e($k['label'])?></b><strong><?= (int)$k['value'] ?></strong></span>
    </a>
  <?php endforeach; ?>
</div>
