<?php
$title='Paiements fournisseurs';
ge_erp_update_bank_balances();
$bankOptions=ge_erp_bank_account_options();
$invoiceOptions=ge_erp_supplier_invoice_options();
$modeOptions=[]; foreach(data_read('payment_modes', []) as $m){ $label=trim((string)($m['label'] ?? $m['name'] ?? $m['ref'] ?? '')); if($label!=='') $modeOptions[$label]=$label; }
if(!$modeOptions) $modeOptions=['Espèces'=>'Espèces','Chèque'=>'Chèque','Virement'=>'Virement','Carte'=>'Carte','Online'=>'Online','Autre'=>'Autre'];
$fields=[
 'ref'=>['label'=>'Réf.'],
 'supplier_invoice_id'=>['label'=>'Facture fournisseur','type'=>'select','options'=>$invoiceOptions,'placeholder'=>'Choisir une facture fournisseur'],
 'supplier_invoice_ref'=>['label'=>'Réf. facture fournisseur'],
 'supplier_name'=>['label'=>'Fournisseur'],
 'bank_account_id'=>['label'=>'Compte bancaire','type'=>'select','options'=>$bankOptions,'placeholder'=>'Choisir un compte'],
 'date'=>['label'=>'Date','type'=>'date','default'=>date('Y-m-d')],
 'amount'=>['label'=>'Montant','type'=>'money'],
 'mode'=>['label'=>'Mode','type'=>'select','options'=>$modeOptions],
 'reference'=>['label'=>'Référence paiement'],
 'status'=>['label'=>'Statut','type'=>'select','options'=>ge_status_options('payment')],
 'note'=>['label'=>'Note','type'=>'textarea']
];
if(($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST'){
    if(($_POST['action'] ?? '')==='save'){
        $invId=(int)($_POST['supplier_invoice_id'] ?? 0);
        if($invId>0){
            try{ $pdo=db(); ge_erp_ensure_tables($pdo); $stmt=$pdo->prepare('SELECT * FROM ge_supplier_invoices WHERE id=? AND tenant_id=?'); $stmt->execute([$invId, ge_current_tenant_id()]); $inv=$stmt->fetch(); }
            catch(Throwable $e){ $inv=null; }
            if($inv){
                $_POST['supplier_invoice_ref']=$inv['ref'] ?? '';
                $_POST['supplier_id']=$inv['supplier_id'] ?? null;
                $_POST['supplier_name']=$inv['supplier_name'] ?? '';
                if(empty($_POST['amount'])) $_POST['amount']=ge_decimal($inv['remaining_amount'] ?? $inv['amount_ttc'] ?? 0);
            }
        }
        if(empty($_POST['bank_account_id']) && $bankOptions) $_POST['bank_account_id']=array_key_first($bankOptions);
    }
    ge_simple_handle_crud('supplier_payments',$fields,'supplier_payments','SPAY');
}
$rows=data_read('supplier_payments',[]);
$editId=(int)($_GET['edit'] ?? 0); $edit=$editId?find_row_by_id($rows,$editId):[];
$total=0; $confirmed=0; $draft=0; foreach($rows as $r){ $amount=ge_decimal($r['amount']??0); if(ge_finance_payment_is_active((string)($r['status']??''))) $confirmed+=$amount; else $draft+=$amount; $total+=$amount; }
$paymentRows=array_reverse($rows);
[$paymentRows,$paymentTotal,$paymentPage,$paymentPages]=ge_list_paginate_current($paymentRows);
include __DIR__.'/../layouts/header.php';
?>
<div class="erp-page finance-page">
 <?php if(isset($_GET['ok'])): ?><div class="email-status ok">Paiement fournisseur enregistré et relié à la banque/comptabilité.</div><?php endif; ?>
 <?php if(isset($_GET['cancelled'])): ?><div class="email-status ok">Paiement actif annulé proprement au lieu d’être supprimé.</div><?php endif; ?>
 <?php if(isset($_GET['deleted'])): ?><div class="email-status ok">Paiement fournisseur supprimé et liens recalculés.</div><?php endif; ?>
 <div class="erp-head"><div><h2><i class="fa-solid fa-hand-holding-dollar"></i> Paiements fournisseurs</h2><p>Sorties bancaires, facture fournisseur et écritures comptables connectées.</p></div></div>
 <div class="finance-summary cards-rect"><div class="mini-kpi-card"><span class="mini-kpi-text"><b>Total sorties</b><strong><?=money($total)?></strong><small><?=count($rows)?> lignes</small></span></div><div class="mini-kpi-card"><span class="mini-kpi-text"><b>Confirmés/Rapprochés</b><strong><?=money($confirmed)?></strong><small>impact banque + compta</small></span></div><div class="mini-kpi-card"><span class="mini-kpi-text"><b>Brouillon/Annulé</b><strong><?=money($draft)?></strong><small>sans impact</small></span></div></div>
 <section class="panel erp-card"><h3><?=$edit?'Modifier paiement fournisseur':'Nouveau paiement fournisseur'?></h3><form method="post" class="erp-form compact-grid"><?=csrf_field()?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=e($edit['id']??0)?>"><?php foreach($fields as $n=>$m) ge_field_input($n,$m,$edit[$n]??($m['default']??'')); ?><div class="erp-actions"><button class="btn primary">Enregistrer</button><?php if($edit): ?><a class="btn light" href="index.php?page=supplier_payments">Annuler</a><?php endif; ?></div></form></section>
 <section class="panel erp-card"><div class="excel-panel-head"><div><b>Liste des paiements fournisseurs</b><span>20 lignes par page. Export Excel disponible.</span></div></div><div class="table-wrap"><table class="clean-table erp-table strong-lines"><thead><tr><th>Réf.</th><th>Facture</th><th>Fournisseur</th><th>Date</th><th class="num">Montant</th><th>Mode</th><th>Compte</th><th>Statut</th><th>Actions</th></tr></thead><tbody><?php foreach($paymentRows as $r): $bank=ge_erp_find_collection_row('bank_accounts',(int)($r['bank_account_id']??0)); $st=(string)($r['status']??'Brouillon'); ?><tr><td><a href="index.php?page=supplier_payment_show&id=<?=(int)($r['id']??0)?>"><b><?=e($r['ref']??'')?></b></a></td><td><?php if(!empty($r['supplier_invoice_id'])): ?><a href="index.php?page=supplier_invoice_show&id=<?=(int)$r['supplier_invoice_id']?>"><?=e($r['supplier_invoice_ref']??$r['supplier_invoice_id'])?></a><?php else: ?><?=e($r['supplier_invoice_ref']??'')?><?php endif; ?></td><td><?=e($r['supplier_name']??'')?></td><td><?=e(substr((string)($r['date']??''),0,10))?></td><td class="num"><b><?=money($r['amount']??0)?></b></td><td><?=e($r['mode']??'')?></td><td><?php if($bank): ?><a href="index.php?page=bank_account_show&id=<?=(int)($bank['id']??0)?>"><?=e($bank['name']??$bank['ref']??'')?></a><?php endif; ?></td><td><span class="<?=e(ge_finance_status_class($st))?>"><?=e($st)?></span></td><td class="actions-cell"><a class="mini-action" href="index.php?page=supplier_payment_show&id=<?=(int)($r['id']??0)?>">Ouvrir</a><?php if(!ge_finance_payment_is_active($st)): ?><a class="mini-action" href="index.php?page=supplier_payments&edit=<?=(int)($r['id']??0)?>">Modifier</a><?php endif; ?><?php if($st!=='Confirmé'): ?><form method="post" action="index.php?page=finance_payment_status" class="inline-form"><?=csrf_field()?><input type="hidden" name="type" value="supplier"><input type="hidden" name="id" value="<?=(int)($r['id']??0)?>"><input type="hidden" name="status" value="Confirmé"><button class="mini-action success">Confirmer</button></form><?php endif; ?><?php if($st!=='Rapproché'): ?><form method="post" action="index.php?page=finance_payment_status" class="inline-form"><?=csrf_field()?><input type="hidden" name="type" value="supplier"><input type="hidden" name="id" value="<?=(int)($r['id']??0)?>"><input type="hidden" name="status" value="Rapproché"><button class="mini-action">Rapprocher</button></form><?php endif; ?><form method="post" style="display:inline" onsubmit="return confirm('Action sur ce paiement ? Un paiement confirmé sera annulé proprement.')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)($r['id']??0)?>"><button class="mini-action danger"><?=ge_finance_payment_is_active($st)?'Annuler':'Supprimer'?></button></form></td></tr><?php endforeach; if(!$paymentTotal): ?><tr><td colspan="9">Aucun paiement fournisseur.</td></tr><?php endif; ?></tbody></table></div><?=ge_list_pager($paymentTotal,$paymentPage,$paymentPages,'p',['page'=>'supplier_payments'])?></section>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
