<?php
$id=(int)($_GET['id'] ?? 0);
if(($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST'){
    require_csrf();
    $status=trim((string)($_POST['status'] ?? ''));
    if(in_array($status,['Actif','Inactif'],true)){
        $rows=data_read('bank_accounts',[]);
        foreach($rows as &$r){ if((int)($r['id']??0)===$id){ $r['status']=$status; $r['updated_at']=date('Y-m-d H:i:s'); break; } }
        unset($r); data_write('bank_accounts',$rows,false); audit_log('bank_account_status','bank account #'.$id.' => '.$status); redirect_to('index.php?page=bank_account_show&id='.$id.'&status_ok=1');
    }
}
ge_erp_update_bank_balances();
$account=ge_finance_bank_account_detail($id);
if(!$account){ include __DIR__.'/../layouts/header.php'; echo '<div class="panel erp-card"><h2>Compte bancaire introuvable</h2><a class="btn light" href="index.php?page=bank_accounts">Retour</a></div>'; include __DIR__.'/../layouts/footer.php'; return; }
$movements=ge_finance_bank_movements($id,500);
$payments=array_filter(data_read('payments',[]), fn($p)=>(int)($p['bank_account_id']??0)===$id);
$supplierPayments=array_filter(data_read('supplier_payments',[]), fn($p)=>(int)($p['bank_account_id']??0)===$id);
$totalDebit=0; $totalCredit=0; foreach($movements as $m){ $totalDebit+=ge_decimal($m['debit']??0); $totalCredit+=ge_decimal($m['credit']??0); }
$title='Compte bancaire '.$account['name'];
include __DIR__.'/../layouts/header.php';
?>
<div class="erp-page finance-page finance-detail-page">
 <?php if(isset($_GET['status_ok'])): ?><div class="email-status ok">Statut du compte mis à jour.</div><?php endif; ?>
 <div class="erp-head object-head finance-object-head"><div><h2><i class="fa-solid fa-building-columns"></i> <?=e($account['name']??$account['ref']??'Compte bancaire')?></h2><p><?=e($account['bank_name']??'')?> · <?=e($account['currency']??'MAD')?> · Solde <?=money($account['current_balance']??0)?></p></div><div class="object-actions"><span class="<?=($account['status']??'')==='Actif'?'badge-green':'badge-gray'?>"><?=e($account['status']??'')?></span><a class="btn light" href="index.php?page=bank_accounts">Liste</a><a class="btn light" href="index.php?page=bank_accounts&edit=<?=$id?>">Modifier</a></div></div>
 <section class="panel erp-card finance-status-panel"><h3>Statut du compte</h3><div class="status-action-row"><?php foreach(['Actif','Inactif'] as $st): ?><form method="post" class="inline-form"><?=csrf_field()?><input type="hidden" name="status" value="<?=e($st)?>"><button class="btn <?=($account['status']??'')===$st?'primary':'light'?>"><?=e($st)?></button></form><?php endforeach; ?></div></section>
 <div class="finance-summary cards-rect"><div class="mini-kpi-card"><span class="mini-kpi-text"><b>Solde initial</b><strong><?=money($account['opening_balance']??0)?></strong><small><?=e($account['currency']??'MAD')?></small></span></div><div class="mini-kpi-card"><span class="mini-kpi-text"><b>Entrées</b><strong><?=money($totalDebit)?></strong><small>Paiements clients</small></span></div><div class="mini-kpi-card"><span class="mini-kpi-text"><b>Sorties</b><strong><?=money($totalCredit)?></strong><small>Paiements fournisseurs</small></span></div><div class="mini-kpi-card"><span class="mini-kpi-text"><b>Solde actuel</b><strong><?=money($account['current_balance']??0)?></strong><small><?=count($movements)?> mouvements</small></span></div></div>
 <section class="panel erp-card"><h3>Détails du compte</h3><table class="detail-table"><tr><th>Référence</th><td><?=e($account['ref']??'')?></td></tr><tr><th>Banque</th><td><?=e($account['bank_name']??'')?></td></tr><tr><th>RIB / IBAN</th><td><?=e($account['rib']??$account['iban']??'')?></td></tr><tr><th>Paiements clients</th><td><?=count($payments)?></td></tr><tr><th>Paiements fournisseurs</th><td><?=count($supplierPayments)?></td></tr><tr><th>Note</th><td><?=nl2br(e($account['note']??''))?></td></tr></table></section>
 <section class="panel erp-card"><h3>Mouvements bancaires</h3><div class="table-wrap"><table class="clean-table erp-table strong-lines"><thead><tr><th>Date</th><th>Libellé</th><th>Source</th><th class="num">Débit</th><th class="num">Crédit</th><th>Rapproché</th><th>Détail</th></tr></thead><tbody><?php foreach($movements as $m): $src=(string)($m['source_type']??''); $sid=(int)($m['source_id']??0); $link=$src==='payment'?'payment_show':($src==='supplier_payment'?'supplier_payment_show':''); ?><tr><td><?=e(substr((string)($m['movement_date']??''),0,10))?></td><td><?=e($m['label']??'')?></td><td><?=e($src.' #'.$sid)?></td><td class="num"><?=money($m['debit']??0)?></td><td class="num"><?=money($m['credit']??0)?></td><td><?=!empty($m['reconciled'])?'Oui':'Non'?></td><td><?php if($link): ?><a class="mini-action" href="index.php?page=<?=$link?>&id=<?=$sid?>">Ouvrir</a><?php endif; ?></td></tr><?php endforeach; if(!$movements): ?><tr><td colspan="7">Aucun mouvement bancaire.</td></tr><?php endif; ?></tbody></table></div></section>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
