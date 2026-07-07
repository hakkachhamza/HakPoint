<?php
$id=(int)($_GET['id'] ?? 0);
$row=ge_finance_payment_by_id('payments',$id);
if(!$row){ include __DIR__.'/../layouts/header.php'; echo '<div class="panel erp-card"><h2>Paiement introuvable</h2><a class="btn light" href="index.php?page=payments">Retour</a></div>'; include __DIR__.'/../layouts/footer.php'; return; }
$title='Paiement client '.$row['ref'];
$invoice=!empty($row['invoice_id']) ? ge_erp_find_collection_row('invoices',(int)$row['invoice_id']) : [];
$bank=!empty($row['bank_account_id']) ? ge_finance_bank_account_detail((int)$row['bank_account_id']) : [];
$movement=ge_finance_bank_movement_for('payment',$id);
$entries=ge_finance_accounting_entries_for('payment',$id);
$status=(string)($row['status'] ?? 'Brouillon');
include __DIR__.'/../layouts/header.php';
?>
<?php if(isset($_GET['locked'])): ?><div class="email-status err">Paiement confirmé/rapproché verrouillé: annulez-le puis recréez un paiement corrigé.</div><?php endif; ?>
<div class="erp-page finance-page finance-detail-page">
 <?php if(isset($_GET['status_ok'])): ?><div class="email-status ok">Statut mis à jour et logique finance/comptabilité recalculée.</div><?php endif; ?>
 <div class="erp-head object-head finance-object-head">
  <div><h2><i class="fa-solid fa-money-bill-transfer"></i> Paiement client <?=e($row['ref'] ?? '')?></h2><p><?=e($row['client_name'] ?? '')?> · <?=money($row['amount'] ?? 0)?> · <?=e(substr((string)($row['date'] ?? ''),0,10))?></p></div>
  <div class="object-actions"><span class="<?=e(ge_finance_status_class($status))?>"><?=e($status)?></span><a class="btn light" href="index.php?page=payments">Liste</a><a class="btn light" href="index.php?page=payments&edit=<?=$id?>">Modifier</a></div>
 </div>
 <section class="panel erp-card finance-status-panel"><h3>Changer le statut</h3><div class="status-action-row"><?php foreach(ge_status_options('payment') as $st): ?><form method="post" action="index.php?page=finance_payment_status" class="inline-form"><?=csrf_field()?><input type="hidden" name="type" value="customer"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="status" value="<?=e($st)?>"><button class="btn <?=($status===$st?'primary':'light')?>" type="submit"><?=e($st)?></button></form><?php endforeach; ?></div><p class="muted small-note">Confirmé/Rapproché crée banque + comptabilité. Brouillon/Annulé enlève les mouvements liés. Rapproché marque aussi le mouvement bancaire comme rapproché.</p></section>
 <div class="detail-grid two-cols">
  <section class="panel erp-card"><h3>Détails paiement</h3><table class="detail-table"><tr><th>Référence</th><td><?=e($row['ref']??'')?></td></tr><tr><th>Client</th><td><?=e($row['client_name']??'')?></td></tr><tr><th>Facture</th><td><?php if($invoice): ?><a href="index.php?page=invoice_show&id=<?=(int)($invoice['id']??0)?>"><?=e($row['invoice_ref']??$invoice['ref']??'')?></a><?php else: ?><?=e($row['invoice_ref']??'')?><?php endif; ?></td></tr><tr><th>Montant</th><td><b><?=money($row['amount']??0)?></b></td></tr><tr><th>Mode</th><td><?=e($row['mode']??'')?></td></tr><tr><th>Réf. bancaire</th><td><?=e($row['reference']??'')?></td></tr><tr><th>Note</th><td><?=nl2br(e($row['note']??''))?></td></tr></table></section>
  <section class="panel erp-card"><h3>Connexion bancaire</h3><table class="detail-table"><tr><th>Compte</th><td><?php if($bank): ?><a href="index.php?page=bank_account_show&id=<?=(int)($bank['id']??0)?>"><?=e($bank['name']??$bank['ref']??'')?></a><?php else: ?>Non défini<?php endif; ?></td></tr><tr><th>Mouvement</th><td><?= $movement ? 'Créé' : 'Non créé' ?></td></tr><tr><th>Rapproché</th><td><?= !empty($movement['reconciled']) ? 'Oui' : 'Non' ?></td></tr><tr><th>Débit</th><td><?=money($movement['debit']??0)?></td></tr><tr><th>Crédit</th><td><?=money($movement['credit']??0)?></td></tr></table></section>
 </div>
 <section class="panel erp-card"><h3>Écritures comptables liées</h3><div class="table-wrap"><table class="clean-table erp-table strong-lines"><thead><tr><th>Date</th><th>Journal</th><th>Compte</th><th>Libellé</th><th class="num">Débit</th><th class="num">Crédit</th></tr></thead><tbody><?php foreach($entries as $e): ?><tr><td><?=e($e['date']??'')?></td><td><?=e($e['journal']??'')?></td><td><?=e($e['account']??'')?></td><td><?=e($e['label']??'')?></td><td class="num"><?=money($e['debit']??0)?></td><td class="num"><?=money($e['credit']??0)?></td></tr><?php endforeach; if(!$entries): ?><tr><td colspan="6">Aucune écriture pour ce statut.</td></tr><?php endif; ?></tbody></table></div></section>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
