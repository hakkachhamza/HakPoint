<?php
$title='Comptes bancaires';
ge_erp_update_bank_balances();
$fields=[
 'ref'=>['label'=>'Réf.'], 'name'=>['label'=>'Nom du compte'], 'bank_name'=>['label'=>'Banque'], 'rib'=>['label'=>'RIB / IBAN'], 'currency'=>['label'=>'Devise','default'=>'MAD'],
 'opening_balance'=>['label'=>'Solde initial','type'=>'money'], 'current_balance'=>['label'=>'Solde actuel','type'=>'money'], 'status'=>['label'=>'Statut','type'=>'select','options'=>['Actif','Inactif']], 'note'=>['label'=>'Note','type'=>'textarea']
];
if(($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST'){
    require_csrf();
    if(($_POST['action'] ?? '')==='reconcile'){
        ge_finance_reconcile_movement((int)($_POST['movement_id'] ?? 0), !empty($_POST['reconciled']));
        redirect_to('index.php?page=bank_accounts&reconciled=1');
    }
    ge_simple_handle_crud('bank_accounts',$fields,'bank_accounts','BA');
}
ge_erp_update_bank_balances();
$accounts=data_read('bank_accounts',[]);
$accountsList=$accounts;
[$accountsList,$accountsTotal,$accountsPage,$accountsPages]=ge_list_paginate_current($accountsList);
$editId=(int)($_GET['edit'] ?? 0); $edit=$editId?find_row_by_id($accounts,$editId):[];
$selectedAccount=(int)($_GET['account_id'] ?? 0);
$movementsAll=ge_finance_bank_movements($selectedAccount, 200);
$totalDebit=0; $totalCredit=0; foreach($movementsAll as $m){ $totalDebit+=ge_decimal($m['debit']??0); $totalCredit+=ge_decimal($m['credit']??0); }
[$movements,$movementsTotal,$movementsPage,$movementsPages]=ge_list_paginate_current($movementsAll);
include __DIR__.'/../layouts/header.php';
?>
<div class="erp-page finance-page">
<?php if(isset($_GET['ok'])): ?><div class="email-status ok">Compte bancaire enregistré.</div><?php endif; ?><?php if(isset($_GET['reconciled'])): ?><div class="email-status ok">Mouvement bancaire mis à jour.</div><?php endif; ?>
<div class="erp-head"><div><h2><i class="fa-solid fa-building-columns"></i> Finance — Comptes bancaires</h2><p>Les paiements clients/fournisseurs créent automatiquement les mouvements et recalculent les soldes.</p></div></div>
<div class="finance-summary cards-rect">
<?php foreach($accounts as $a): ?><a class="mini-kpi-card" href="index.php?page=bank_accounts&account_id=<?=(int)($a['id']??0)?>"><span class="mini-kpi-text"><b><?=e($a['name']??$a['ref']??'Compte')?></b><strong><?=money($a['current_balance']??0)?></strong><small><?=e(($a['bank_name']??'').' '.($a['currency']??'MAD'))?></small></span></a><?php endforeach; if(!$accounts): ?><div class="mini-kpi-card"><span class="mini-kpi-text"><b>Aucun compte</b><strong>0,000</strong><small>Créez votre premier compte bancaire.</small></span></div><?php endif; ?>
</div>
<section class="panel erp-card"><h3><?=$edit?'Modifier compte':'Nouveau compte bancaire'?></h3><form method="post" class="erp-form compact-grid"><?=csrf_field()?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?=e($edit['id']??0)?>"><?php foreach($fields as $n=>$m) ge_field_input($n,$m,$edit[$n]??($m['default']??'')); ?><div class="erp-actions"><button class="btn primary">Enregistrer</button><?php if($edit): ?><a class="btn light" href="index.php?page=bank_accounts">Annuler</a><?php endif; ?></div></form></section>
<section class="panel erp-card"><h3>Liste des comptes</h3><div class="table-wrap"><table class="clean-table erp-table strong-lines"><thead><tr><th>Réf.</th><th>Compte</th><th>Banque</th><th>Devise</th><th class="num">Solde initial</th><th class="num">Solde actuel</th><th>Statut</th><th>Actions</th></tr></thead><tbody><?php foreach($accountsList as $r): ?><tr><td><?=e($r['ref']??'')?></td><td><?=e($r['name']??'')?></td><td><?=e($r['bank_name']??'')?></td><td><?=e($r['currency']??'MAD')?></td><td class="num"><?=money($r['opening_balance']??0)?></td><td class="num"><b><?=money($r['current_balance']??0)?></b></td><td><span class="<?=($r['status']??'')==='Actif'?'badge-green':'badge-gray'?>"><?=e($r['status']??'')?></span></td><td class="actions-cell"><a class="mini-action" href="index.php?page=bank_account_show&id=<?=(int)($r['id']??0)?>">Ouvrir</a> <a class="mini-action" href="index.php?page=bank_accounts&edit=<?=(int)($r['id']??0)?>">Modifier</a> <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)($r['id']??0)?>"><button class="mini-action danger">Supprimer</button></form></td></tr><?php endforeach; if(!$accountsTotal): ?><tr><td colspan="8">Aucun compte.</td></tr><?php endif; ?></tbody></table></div><?=ge_list_pager($accountsTotal,$accountsPage,$accountsPages,'p',['page'=>'bank_accounts'])?></section>
<section class="panel erp-card"><div class="excel-panel-head"><div><b>Mouvements bancaires</b><span>Débit <?=money($totalDebit)?> / Crédit <?=money($totalCredit)?> / Net <?=money($totalDebit-$totalCredit)?></span></div><form method="get" class="inline-filter"><input type="hidden" name="page" value="bank_accounts"><select name="account_id" onchange="this.form.submit()"><option value="0">Tous les comptes</option><?php foreach($accounts as $a): ?><option value="<?=(int)($a['id']??0)?>" <?=$selectedAccount===(int)($a['id']??0)?'selected':''?>><?=e($a['name']??$a['ref']??'')?></option><?php endforeach; ?></select></form></div><div class="table-wrap"><table class="clean-table erp-table strong-lines"><thead><tr><th>Date</th><th>Compte</th><th>Libellé</th><th>Source</th><th class="num">Débit</th><th class="num">Crédit</th><th>Rapproché</th></tr></thead><tbody><?php foreach($movements as $m): $bank=ge_erp_find_collection_row('bank_accounts',(int)($m['bank_account_id']??0)); ?><tr><td><?=e(substr((string)($m['movement_date']??''),0,10))?></td><td><?=e($bank['name']??'')?></td><td><?=e($m['label']??'')?></td><td><?=e(($m['source_type']??'').' #'.($m['source_id']??''))?></td><td class="num"><?=money($m['debit']??0)?></td><td class="num"><?=money($m['credit']??0)?></td><td><form method="post" class="inline-form"><?=csrf_field()?><input type="hidden" name="action" value="reconcile"><input type="hidden" name="movement_id" value="<?=(int)($m['id']??0)?>"><input type="hidden" name="reconciled" value="<?=empty($m['reconciled'])?'1':'0'?>"><button class="mini-action <?=empty($m['reconciled'])?'':'success'?>" type="submit"><?=empty($m['reconciled'])?'Non':'Oui'?></button></form></td></tr><?php endforeach; if(!$movementsTotal): ?><tr><td colspan="7">Aucun mouvement bancaire.</td></tr><?php endif; ?></tbody></table></div><?=ge_list_pager($movementsTotal,$movementsPage,$movementsPages,'p',['page'=>'bank_accounts'] + ($selectedAccount ? ['account_id'=>$selectedAccount] : []))?></section>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
