<?php
$title='Comptabilité';
function ge_csv_safe($value){
    if(is_array($value) || is_object($value)) $value=json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $value=(string)$value;
    $value=str_replace(["
","
","
"], ' ', $value);
    $value=trim($value);
    if($value !== '' && in_array(substr($value,0,1), ['=', '+', '-', '@'], true)) $value="'".$value;
    return $value;
}
ge_accounting_seed_defaults();
if(isset($_GET['export']) && $_GET['export']==='entries'){
    $rows=data_read('accounting_entries', []);
    while(ob_get_level()>0){ @ob_end_clean(); }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ecritures-comptables-'.date('Ymd').'.csv"');
    $out=fopen('php://output','w');
    fputcsv($out,['Date','Journal','Compte','Libellé','Débit','Crédit','Source type','Source ID'],';');
    foreach($rows as $e){ fputcsv($out,[ge_csv_safe(substr((string)($e['date']??''),0,10)),ge_csv_safe($e['journal']??''),ge_csv_safe($e['account']??''),ge_csv_safe($e['label']??''),ge_csv_safe($e['debit']??0),ge_csv_safe($e['credit']??0),ge_csv_safe($e['source_type']??''),ge_csv_safe($e['source_id']??'')],';'); }
    fclose($out); exit;
}
if(($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST'){
  require_csrf();
  $action=$_POST['action'] ?? '';
  if($action==='account'){
    $fields=['ref'=>['label'=>'Réf.'],'code'=>['label'=>'Code'],'label'=>['label'=>'Libellé'],'type'=>['label'=>'Type'],'status'=>['label'=>'Statut']];
    ge_simple_handle_crud('accounting_accounts',$fields,'accounting','ACC');
  } elseif($action==='journal'){
    $fields=['ref'=>['label'=>'Réf.'],'code'=>['label'=>'Code'],'label'=>['label'=>'Libellé'],'type'=>['label'=>'Type'],'status'=>['label'=>'Statut']];
    ge_simple_handle_crud('accounting_journals',$fields,'accounting','JRN');
  } elseif($action==='period'){
    ge_accounting_save_period_from_post();
    redirect_to('index.php?page=accounting&period=1');
  } elseif($action==='entry'){
    ge_accounting_add_entry($_POST['journal']??'OD', $_POST['label']??'Écriture manuelle', $_POST['debit_account']??'411000', $_POST['credit_account']??'711000', $_POST['amount']??0, 'manual', 0);
    redirect_to('index.php?page=accounting&ok=1');
  }
}
$accounts=data_read('accounting_accounts',[]); $journals=data_read('accounting_journals',[]); $entries=data_read('accounting_entries',[]); $periods=data_read('accounting_periods',[]);
$accountOptions=[]; foreach($accounts as $a){ $code=(string)($a['code']??''); if($code!=='') $accountOptions[$code]=$code.' — '.($a['label']??''); }
$journalOptions=[]; foreach($journals as $j){ $code=(string)($j['code']??''); if($code!=='') $journalOptions[$code]=$code.' — '.($j['label']??''); }
$balance=ge_accounting_balance_rows(); $tva=ge_accounting_tva_summary();
$totalDebit=0; $totalCredit=0; foreach($entries as $e){ $totalDebit+=ge_decimal($e['debit']??0); $totalCredit+=ge_decimal($e['credit']??0); }
$entriesList=array_reverse($entries);
[$entriesList,$entriesTotal,$entriesPage,$entriesPages]=ge_list_paginate_current($entriesList);
include __DIR__.'/../layouts/header.php';
?>
<div class="erp-page accounting-page">
<?php if(isset($_GET['ok'])): ?><div class="email-status ok">Écriture enregistrée.</div><?php endif; ?>
<div class="erp-head"><div><h2><i class="fa-solid fa-scale-balanced"></i> Comptabilité</h2><p>Plan comptable, journaux, écritures automatiques, balance et TVA.</p></div><a class="btn light" href="index.php?page=accounting&export=entries"><i class="fa-solid fa-file-csv"></i> Export CSV</a></div>
<div class="dashboard-kpi-strip cards-rect">
  <div class="mini-kpi-card"><span class="mini-kpi-text"><b>Total débit</b><strong><?=money($totalDebit)?></strong><small>Écritures comptables</small></span></div>
  <div class="mini-kpi-card"><span class="mini-kpi-text"><b>Total crédit</b><strong><?=money($totalCredit)?></strong><small>Équilibre: <?=money($totalDebit-$totalCredit)?></small></span></div>
  <div class="mini-kpi-card"><span class="mini-kpi-text"><b>TVA collectée</b><strong><?=money($tva['collected'])?></strong><small>Ventes</small></span></div>
  <div class="mini-kpi-card"><span class="mini-kpi-text"><b>TVA déductible</b><strong><?=money($tva['deductible'])?></strong><small>Achats</small></span></div>
  <div class="mini-kpi-card"><span class="mini-kpi-text"><b>TVA nette</b><strong><?=money($tva['net'])?></strong><small>Collectée - déductible</small></span></div>
</div>
<section class="panel erp-card"><h3><i class="fa-solid fa-lock"></i> Périodes comptables</h3><p class="muted small-note">Pour une société sérieuse: quand une période est clôturée, les nouvelles écritures comptables dans cette période sont bloquées.</p><form method="post" class="erp-form compact-grid"><?=csrf_field()?><input type="hidden" name="action" value="period"><label>Nom</label><input name="name" value="<?=e('Exercice '.date('Y'))?>"><label>Début</label><input type="date" name="start_date" value="<?=e(date('Y-01-01'))?>"><label>Fin</label><input type="date" name="end_date" value="<?=e(date('Y-12-31'))?>"><label>Statut</label><select name="status" class="smart-select"><option>Ouverte</option><option>Clôturée</option></select><label>Note</label><input name="note"><button class="btn primary">Enregistrer période</button></form><div class="table-wrap"><table class="clean-table erp-table strong-lines"><thead><tr><th>Réf.</th><th>Nom</th><th>Début</th><th>Fin</th><th>Statut</th><th>Clôturée le</th></tr></thead><tbody><?php foreach(array_reverse($periods) as $p): ?><tr><td><?=e($p['ref']??'')?></td><td><?=e($p['name']??'')?></td><td><?=e(substr((string)($p['start_date']??''),0,10))?></td><td><?=e(substr((string)($p['end_date']??''),0,10))?></td><td><span class="<?=in_array(($p['status']??''),['Clôturée','Verrouillée','Fermée'],true)?'badge-red':'badge-green'?>"><?=e($p['status']??'Ouverte')?></span></td><td><?=e($p['closed_at']??'')?></td></tr><?php endforeach; if(!$periods): ?><tr><td colspan="6">Aucune période définie.</td></tr><?php endif; ?></tbody></table></div></section>
<div class="erp-two">
<section class="panel erp-card"><h3>Créer un compte</h3><form method="post" class="erp-form small compact-grid"><?=csrf_field()?><input type="hidden" name="action" value="account"><label>Code</label><input name="code" placeholder="411000"><label>Libellé</label><input name="label" placeholder="Clients"><label>Type</label><select name="type" class="smart-select"><option>Actif</option><option>Passif</option><option>Charge</option><option>Produit</option></select><label>Statut</label><select name="status" class="smart-select"><option>Actif</option><option>Inactif</option></select><button class="btn primary">Ajouter</button></form></section>
<section class="panel erp-card"><h3>Écriture manuelle</h3><form method="post" class="erp-form small compact-grid"><?=csrf_field()?><input type="hidden" name="action" value="entry"><label>Journal</label><select name="journal" class="smart-select"><?php foreach($journalOptions as $v=>$l): ?><option value="<?=e($v)?>"><?=e($l)?></option><?php endforeach; ?></select><label>Libellé</label><input name="label"><label>Compte débit</label><select name="debit_account" class="smart-select"><?php foreach($accountOptions as $v=>$l): ?><option value="<?=e($v)?>"><?=e($l)?></option><?php endforeach; ?></select><label>Compte crédit</label><select name="credit_account" class="smart-select"><?php foreach($accountOptions as $v=>$l): ?><option value="<?=e($v)?>"><?=e($l)?></option><?php endforeach; ?></select><label>Montant</label><input type="number" step="0.001" name="amount"><button class="btn primary">Ajouter écriture</button></form></section>
</div>
<section class="panel erp-card"><h3>Balance comptable</h3><div class="table-wrap"><table class="clean-table erp-table strong-lines"><thead><tr><th>Compte</th><th>Libellé</th><th class="num">Débit</th><th class="num">Crédit</th><th class="num">Solde</th></tr></thead><tbody><?php foreach($balance as $b): ?><tr><td><?=e($b['account'])?></td><td><?=e($b['label'])?></td><td class="num"><?=money($b['debit'])?></td><td class="num"><?=money($b['credit'])?></td><td class="num"><b><?=money($b['balance'])?></b></td></tr><?php endforeach; if(!$balance): ?><tr><td colspan="5">Aucune balance pour le moment.</td></tr><?php endif; ?></tbody></table></div></section>
<section class="panel erp-card"><h3>Grand livre / Écritures</h3><div class="table-wrap"><table class="clean-table erp-table strong-lines"><thead><tr><th>Date</th><th>Journal</th><th>Compte</th><th>Libellé</th><th class="num">Débit</th><th class="num">Crédit</th><th>Source</th></tr></thead><tbody><?php foreach($entriesList as $e): ?><tr><td><?=e(substr((string)($e['date']??''),0,10))?></td><td><?=e($e['journal']??'')?></td><td><?=e($e['account']??'')?></td><td><?=e($e['label']??'')?></td><td class="num"><?=money($e['debit']??0)?></td><td class="num"><?=money($e['credit']??0)?></td><td><?=e(($e['source_type']??'').' #'.($e['source_id']??''))?></td></tr><?php endforeach; if(!$entriesTotal): ?><tr><td colspan="7">Aucune écriture.</td></tr><?php endif; ?></tbody></table></div><?=ge_list_pager($entriesTotal,$entriesPage,$entriesPages,'p',['page'=>'accounting'])?><p class="muted small-note">Les écritures liées aux factures, avoirs et paiements sont remplacées proprement par source pour éviter les doublons.</p></section>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
