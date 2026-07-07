<?php
$products = data_read('products', []);
$tiers = data_read('tiers', []);
$clients = data_read('clients', []);
$suppliers = data_read('suppliers', []);
if($tiers){
    $clients = array_values(array_filter($tiers, fn($t)=>($t['type']??'')==='client'));
    $suppliers = array_values(array_filter($tiers, fn($t)=>($t['type']??'')==='supplier'));
}
$prospects = $tiers ? array_values(array_filter($tiers, fn($t)=>($t['type']??'')==='prospect')) : [];
$quotes = data_read('quotes', []);
$orders = data_read('orders', []);
$invoices = data_read('invoices', []);
$warehouses = data_read('warehouses', []);
$expeditions = data_read('expeditions', []);
$receptions = data_read('receptions', []);
$users = data_read('users', []);
// Dashboard KPIs exclude the built-in first admin account so a clean install can show 0 real business users.
$dashboardUsers = array_values(array_filter($users, function($u){
    $username = strtolower(trim((string)($u['username'] ?? '')));
    $id = (int)($u['id'] ?? $u['record_id'] ?? 0);
    return !($username === 'admin' && $id === 1);
}));

$totalStock = 0; $inStock=0; $lowStock=0; $outStock=0; $stockValue=0;
foreach($products as $p){
    $qty=(float)($p['physical_stock'] ?? $p['stock'] ?? 0);
    $alert=(float)($p['alert_stock'] ?? $p['stock_alert'] ?? 0);
    $price=(float)($p['price'] ?? $p['sell_price'] ?? $p['sale_price'] ?? $p['price_ht'] ?? 0);
    $totalStock += $qty;
    $stockValue += $qty * $price;
    if($qty <= 0) $outStock++;
    elseif($alert > 0 && $qty <= $alert) $lowStock++;
    else $inStock++;
}
$productServices=count(array_filter($products,fn($p)=>strtolower($p['type']??'')==='service'));
$productGoods=max(0,count($products)-$productServices);


// Enterprise modules data
$purchases = [];
$supplierInvoices = [];
$creditNotes = [];
$approvals = [];
$payments = data_read('payments', []);
$supplierPayments = data_read('supplier_payments', []);
$projectsDash = data_read('projects', []);
$agendaDash = data_read('agenda_events', []);
$posSalesDash = data_read('pos_sales', []);
$manufacturingDash = data_read('manufacturing_orders', []);
try{
    $pdo = db();
    if(function_exists('ge_erp_ensure_tables')) ge_erp_ensure_tables($pdo);
    $purchases = ge_fetch_tenant_rows($pdo, 'ge_purchase_orders', 'id DESC', 5000);
    $supplierInvoices = ge_fetch_tenant_rows($pdo, 'ge_supplier_invoices', 'id DESC', 5000);
    $creditNotes = ge_fetch_tenant_rows($pdo, 'ge_credit_notes', 'id DESC', 5000);
    $approvals = ge_fetch_tenant_rows($pdo, 'ge_approval_requests', 'id DESC', 5000);
}catch(Throwable $e){ }
$purchaseTotalDash = array_sum(array_map('amount_from_row', $purchases));
$supplierInvoiceTotalDash = array_sum(array_map('amount_from_row', $supplierInvoices));
$creditTotalDash = array_sum(array_map('amount_from_row', $creditNotes));
$paymentsTotalDash = array_sum(array_map('amount_from_row', $payments));
$supplierPaymentsTotalDash = array_sum(array_map('amount_from_row', $supplierPayments));
$modulesOverview = [
  'Achats'=>count($purchases),
  'Fact. fourn.'=>count($supplierInvoices),
  'Avoirs'=>count($creditNotes),
  'Validations'=>count($approvals),
  'Paiements'=>count($payments),
  'Projets'=>count($projectsDash),
  'Agenda'=>count($agendaDash),
  'POS'=>count($posSalesDash),
  'Fabrication'=>count($manufacturingDash),
];
$enterpriseAmounts = [
  'Factures clients'=>array_sum(array_map('amount_from_row',$invoices)),
  'Achats fournisseurs'=>$purchaseTotalDash,
  'Fact. fournisseurs'=>$supplierInvoiceTotalDash,
  'Avoirs clients'=>$creditTotalDash,
  'Paiements clients'=>$paymentsTotalDash,
  'Paiements fourn.'=>$supplierPaymentsTotalDash,
];

$dashboard = [
    'Produits' => count($products),
    'Stock' => $totalStock,
    'Clients' => count($clients),
    'Prospects' => count($prospects),
    'Fournisseurs' => count($suppliers),
    'Devis' => count($quotes),
    'Commandes' => count($orders),
    'Factures' => count($invoices),
    'Entrepôts' => count($warehouses),
    'Expéditions' => count($expeditions),
    'Réceptions' => count($receptions),
    'Utilisateurs' => count($dashboardUsers),
    'Achats' => count($purchases),
    'Avoirs' => count($creditNotes),
    'Validations' => count($approvals),
];
$stockStatus = ['En stock'=>$inStock,'Stock faible'=>$lowStock,'Rupture'=>$outStock];
$businessStatus = ['Clients'=>count($clients),'Prospects'=>count($prospects),'Fournisseurs'=>count($suppliers),'Produits'=>count($products),'Entrepôts'=>count($warehouses),'Utilisateurs'=>count($dashboardUsers)];
$documentsStatus = ['Devis'=>count($quotes),'Commandes'=>count($orders),'Factures'=>count($invoices),'Expéditions'=>count($expeditions),'Réceptions'=>count($receptions)];
$financialStatus = ['Total devis'=>array_sum(array_map('amount_from_row',$quotes)),'Total commandes'=>array_sum(array_map('amount_from_row',$orders)),'Total factures'=>array_sum(array_map('amount_from_row',$invoices)),'Valeur stock'=>$stockValue,'Achats'=>$purchaseTotalDash,'Avoirs'=>$creditTotalDash,'Paiements'=>$paymentsTotalDash];
$recentProducts = array_slice(array_reverse($products),0,5);
$lowStockProducts = array_values(array_filter($products, fn($p)=>(float)($p['physical_stock']??$p['stock']??0) <= (float)($p['alert_stock']??$p['stock_alert']??0)));
$lowStockProducts = array_slice($lowStockProducts,0,5);
