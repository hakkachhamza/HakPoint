<?php
$title='Rapports';
include __DIR__.'/../layouts/header.php';

function ge_report_sum($collection){ $sum=0; foreach(data_read($collection,[]) as $r){ $sum += amount_from_row($r); } return $sum; }
function ge_report_count_status($collection){ $out=[]; foreach(data_read($collection,[]) as $r){ $s=trim((string)($r['status'] ?? 'Sans statut')) ?: 'Sans statut'; $out[$s]=($out[$s] ?? 0)+1; } arsort($out); return $out; }
function ge_report_date_minmax($rows){ $dates=[]; foreach((array)$rows as $r){ $d=$r['date']??$r['invoice_date']??$r['order_date']??$r['created_at']??''; if($d) $dates[]=substr((string)$d,0,10); } sort($dates); return [$dates[0]??'—', end($dates)?:'—']; }
$products=data_read('products',[]); $tiers=data_read('tiers',[]); $quotes=data_read('quotes',[]); $orders=data_read('orders',[]); $invoices=data_read('invoices',[]); $warehouses=data_read('warehouses',[]); $expeditions=data_read('expeditions',[]); $receptions=data_read('receptions',[]); $payments=data_read('payments',[]); $supplierPayments=data_read('supplier_payments',[]);
$totalSales=ge_report_sum('invoices'); $totalOrders=ge_report_sum('orders'); $totalQuotes=ge_report_sum('quotes');
$pdo=db(); db_install_enterprise_tables($pdo); if(function_exists('ge_erp_ensure_tables')) ge_erp_ensure_tables($pdo);
$purchases=ge_fetch_tenant_rows($pdo, 'ge_purchase_orders', 'id DESC', 5000);
$supplierInvoices=ge_fetch_tenant_rows($pdo, 'ge_supplier_invoices', 'id DESC', 5000);
$creditNotes=ge_fetch_tenant_rows($pdo, 'ge_credit_notes', 'id DESC', 5000);
$approvals=ge_fetch_tenant_rows($pdo, 'ge_approval_requests', 'id DESC', 5000);
$purchaseTotal=array_sum(array_map('amount_from_row',$purchases)); $supplierInvoiceTotal=array_sum(array_map('amount_from_row',$supplierInvoices)); $creditTotal=array_sum(array_map('amount_from_row',$creditNotes)); $pendingApprovals=ge_count_tenant_rows($pdo, 'ge_approval_requests', "status='En attente'");
$stats=[
    ['Module'=>'Produits','Éléments'=>count($products),'Montant HT'=>'—','Statut principal'=>'Catalogue','Période'=>implode(' → ', ge_report_date_minmax($products)),'Lien'=>'index.php?page=products'],
    ['Module'=>'Tiers','Éléments'=>count($tiers),'Montant HT'=>'—','Statut principal'=>'CRM','Période'=>implode(' → ', ge_report_date_minmax($tiers)),'Lien'=>'index.php?page=tiers'],
    ['Module'=>'Devis','Éléments'=>count($quotes),'Montant HT'=>money($totalQuotes),'Statut principal'=>array_key_first(ge_report_count_status('quotes')) ?: '—','Période'=>implode(' → ', ge_report_date_minmax($quotes)),'Lien'=>'index.php?page=quotes'],
    ['Module'=>'Commandes','Éléments'=>count($orders),'Montant HT'=>money($totalOrders),'Statut principal'=>array_key_first(ge_report_count_status('orders')) ?: '—','Période'=>implode(' → ', ge_report_date_minmax($orders)),'Lien'=>'index.php?page=orders'],
    ['Module'=>'Factures clients','Éléments'=>count($invoices),'Montant HT'=>money($totalSales),'Statut principal'=>array_key_first(ge_report_count_status('invoices')) ?: '—','Période'=>implode(' → ', ge_report_date_minmax($invoices)),'Lien'=>'index.php?page=invoices'],
    ['Module'=>'Achats fournisseurs','Éléments'=>count($purchases),'Montant HT'=>money($purchaseTotal),'Statut principal'=>'Commandes fournisseur','Période'=>implode(' → ', ge_report_date_minmax($purchases)),'Lien'=>'index.php?page=purchases'],
    ['Module'=>'Factures fournisseurs','Éléments'=>count($supplierInvoices),'Montant HT'=>money($supplierInvoiceTotal),'Statut principal'=>'Fournisseur','Période'=>implode(' → ', ge_report_date_minmax($supplierInvoices)),'Lien'=>'index.php?page=purchases'],
    ['Module'=>'Avoirs clients','Éléments'=>count($creditNotes),'Montant HT'=>money($creditTotal),'Statut principal'=>'Avoir','Période'=>implode(' → ', ge_report_date_minmax($creditNotes)),'Lien'=>'index.php?page=credit_notes'],
    ['Module'=>'Validations','Éléments'=>count($approvals),'Montant HT'=>'—','Statut principal'=>$pendingApprovals.' en attente','Période'=>implode(' → ', ge_report_date_minmax($approvals)),'Lien'=>'index.php?page=approvals'],
    ['Module'=>'Entrepôts','Éléments'=>count($warehouses),'Montant HT'=>'—','Statut principal'=>'Stock','Période'=>implode(' → ', ge_report_date_minmax($warehouses)),'Lien'=>'index.php?page=warehouses'],
    ['Module'=>'Expéditions','Éléments'=>count($expeditions),'Montant HT'=>'—','Statut principal'=>array_key_first(ge_report_count_status('expeditions')) ?: '—','Période'=>implode(' → ', ge_report_date_minmax($expeditions)),'Lien'=>'index.php?page=expeditions'],
    ['Module'=>'Réceptions','Éléments'=>count($receptions),'Montant HT'=>'—','Statut principal'=>array_key_first(ge_report_count_status('receptions')) ?: '—','Période'=>implode(' → ', ge_report_date_minmax($receptions)),'Lien'=>'index.php?page=receptions'],
    ['Module'=>'Paiements clients','Éléments'=>count($payments),'Montant HT'=>money(array_sum(array_map('amount_from_row',$payments))),'Statut principal'=>'Finance','Période'=>implode(' → ', ge_report_date_minmax($payments)),'Lien'=>'index.php?page=payments'],
    ['Module'=>'Paiements fournisseurs','Éléments'=>count($supplierPayments),'Montant HT'=>money(array_sum(array_map('amount_from_row',$supplierPayments))),'Statut principal'=>'Finance','Période'=>implode(' → ', ge_report_date_minmax($supplierPayments)),'Lien'=>'index.php?page=supplier_payments'],
];
$statusGroups=['Devis'=>ge_report_count_status('quotes'),'Commandes'=>ge_report_count_status('orders'),'Factures'=>ge_report_count_status('invoices'),'Expéditions'=>ge_report_count_status('expeditions'),'Réceptions'=>ge_report_count_status('receptions')];
?>
<div class="reports-page ge-simple-section">
  <section class="ge-section-hero compact">
    <div><div class="ge-eyebrow"><i class="fa-solid fa-table"></i> Rapports style Excel</div><h1>Rapports</h1><p>Tableau simple, lisible et imprimable avec tous les modules connectés.</p></div>
    <div class="ge-hero-actions"><a class="btn primary" href="index.php?page=analytics"><i class="fa-solid fa-chart-pie"></i> Analyse / BI</a><button class="btn secondary" type="button" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimer</button></div>
  </section>

  <div class="ge-stat-grid">
    <div class="ge-stat-card"><i class="fa-solid fa-sack-dollar"></i><span>Total factures</span><strong><?=money($totalSales)?></strong></div>
    <div class="ge-stat-card"><i class="fa-solid fa-file-invoice"></i><span>Total commandes</span><strong><?=money($totalOrders)?></strong></div>
    <div class="ge-stat-card"><i class="fa-solid fa-cart-shopping"></i><span>Achats HT</span><strong><?=money($purchaseTotal)?></strong></div>
    <div class="ge-stat-card"><i class="fa-solid fa-file-circle-minus"></i><span>Avoirs HT</span><strong><?=money($creditTotal)?></strong></div>
    <div class="ge-stat-card"><i class="fa-solid fa-boxes-stacked"></i><span>Produits</span><strong><?=e(count($products))?></strong></div>
    <div class="ge-stat-card"><i class="fa-solid fa-clipboard-check"></i><span>Validations en attente</span><strong><?=e($pendingApprovals)?></strong></div>
  </div>

  <div class="panel ge-panel-clean">
    <div class="report-excel-toolbar"><div><h2><i class="fa-solid fa-table-list"></i> Résumé Excel</h2><span class="report-excel-note">Recherche rapide dans le tableau sans recharger la page.</span></div><input class="clean-input" id="reportSearch" placeholder="Filtrer le rapport..."></div>
    <div class="dol-table-wrap">
      <table class="clean-table excel-report-table" id="reportExcelTable">
        <thead><tr><th>Module</th><th>Total éléments</th><th>Montant HT</th><th>Statut principal</th><th>Période</th><th>Ouvrir</th></tr></thead>
        <tbody>
        <?php foreach($stats as $s): ?>
          <tr><td><b><?=e($s['Module'])?></b></td><td class="num"><?=e($s['Éléments'])?></td><td class="num"><?=e($s['Montant HT'])?></td><td><?=e($s['Statut principal'])?></td><td><?=e($s['Période'])?></td><td><a class="btn small" href="<?=e($s['Lien'])?>">Voir</a></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="ge-two-cols">
    <?php foreach($statusGroups as $name=>$items): ?>
      <div class="panel ge-panel-clean"><h3><i class="fa-solid fa-signal"></i> Statuts — <?=e($name)?></h3><?php if(!$items): ?><p class="muted-text">Aucune donnée.</p><?php endif; ?><table class="clean-table excel-report-table"><thead><tr><th>Statut</th><th>Total</th></tr></thead><tbody><?php foreach($items as $status=>$count): ?><tr><td><?=e($status)?></td><td class="num"><b><?=e($count)?></b></td></tr><?php endforeach; ?></tbody></table></div>
    <?php endforeach; ?>
  </div>
</div>
<script>
(function(){const input=document.getElementById('reportSearch'), table=document.getElementById('reportExcelTable'); if(!input||!table)return; input.addEventListener('input',function(){const q=this.value.toLowerCase(); table.querySelectorAll('tbody tr').forEach(tr=>{tr.style.display=tr.textContent.toLowerCase().includes(q)?'':'none';});});})();
</script>
<?php include __DIR__.'/../layouts/footer.php'; ?>
