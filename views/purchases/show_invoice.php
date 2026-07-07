<?php
require_once __DIR__.'/_helpers.php';
$pdo = db();
ge_purchase_ensure_tables($pdo);
$id = (int)($_GET['id'] ?? 0);
$invoice = ge_supplier_invoice_row($pdo, $id);
if (!$invoice) redirect_to('index.php?page=purchases');
$title = 'Facture fournisseur '.$invoice['ref'];
$docs = ge_purchase_docs('invoice', $id);
$lines = ge_purchase_lines($pdo, 'invoice', $id);
$ht = (float)($invoice['amount_ht'] ?? 0);
$tva = (float)($invoice['amount_tva'] ?? 0);
$ttc = (float)($invoice['amount_ttc'] ?? 0); if ($ttc <= 0) $ttc = $ht + $tva;
include __DIR__.'/../layouts/header.php';
?>
<?php if(isset($_GET['pdf_generated'])): ?><div class="flash-success">PDF facture fournisseur généré avec succès.</div><?php endif; ?>
<div class="quote-detail-page invoice-detail-page">
  <div class="quote-detail-head">
    <div class="quote-detail-identity">
      <div class="quote-big-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
      <div class="quote-title-block">
        <div class="quote-title-line">Facture fournisseur :</div>
        <span class="quote-client"><i class="fa-solid fa-building"></i> <?=e($invoice['supplier_name'])?></span>
      </div>
    </div>
    <div class="quote-detail-nav"><a href="index.php?page=purchases">Retour liste</a><div><span class="quote-status gray"><?=e($invoice['status'])?></span></div></div>
  </div>

  <div class="quote-detail-main">
    <div class="quote-left-lines">
      <div class="quote-line"><span>Référence</span><b><?=e($invoice['ref'])?></b></div>
      <div class="quote-line"><span>Fournisseur</span><b><?=e($invoice['supplier_name'])?></b></div>
      <div class="quote-line"><span>Date facture</span><b><?=e($invoice['invoice_date'])?></b></div>
      <div class="quote-line"><span>Échéance</span><b><?=e($invoice['due_date'])?></b></div>
      <div class="quote-line"><span>Statut</span><b><?=e($invoice['status'])?></b></div>
      <div class="quote-line"><span>Payée le</span><b><?=e($invoice['paid_at'] ?? '')?></b></div>
      <?php if(!empty($invoice['purchase_order_id'])): ?><div class="quote-line"><span>Bon de commande source</span><b><a class="ref" href="index.php?page=purchase_order_show&id=<?=(int)$invoice['purchase_order_id']?>"><?=e($invoice['purchase_order_ref'])?></a></b></div><?php endif; ?>
      <div class="quote-line"><span>Note</span><b class="muted-info"><?=nl2br(e($invoice['note'] ?? ''))?></b></div>
    </div>
    <div class="quote-right-summary">
      <div class="quote-money-row"><span>Montant HT</span><b><?=money($ht)?> MAD</b></div>
      <div class="quote-money-row"><span>Montant TVA</span><b><?=money($tva)?> MAD</b></div>
      <div class="quote-total-box"><span>Total TTC</span><b><?=money($ttc)?> MAD</b></div>
    </div>
  </div>

  <div class="clean-table-box" style="margin:14px 0">
    <table class="clean-table">
      <thead><tr><th>Produit</th><th>Qté</th><th>PU HT</th><th>TVA</th><th>Total HT</th><th>Total TTC</th></tr></thead>
      <tbody>
      <?php foreach($lines as $l): ?><tr>
        <td><?=e(($l['product_ref'] ? $l['product_ref'].' - ' : '').$l['product_label'])?></td>
        <td><?=money($l['qty'])?></td>
        <td><?=money($l['pu_ht'])?></td>
        <td><?=money($l['tva_rate'])?>%</td>
        <td><?=money($l['total_ht'])?></td>
        <td><?=money($l['total_ttc'])?></td>
      </tr><?php endforeach; if(!$lines): ?><tr><td colspan="6" class="empty-row">Aucune ligne détaillée.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="quote-detail-actions invoice-detail-actions">
    <a class="orange-btn" href="index.php?page=purchases&edit_invoice=<?=$id?>">MODIFIER</a>
    <a class="orange-btn" href="<?=csrf_url('index.php?page=purchase_status&type=invoice&id='.$id.'&status=À payer')?>">À PAYER</a>
    <a class="green-btn" href="<?=csrf_url('index.php?page=purchase_status&type=invoice&id='.$id.'&status=Payée')?>">CLASSER PAYÉE</a>
    <a class="gray-btn" onclick="return confirm('Annuler cette facture fournisseur ?')" href="<?=csrf_url('index.php?page=purchase_status&type=invoice&id='.$id.'&status=Annulée')?>">ANNULER</a>
  </div>

  <div class="quote-pdf-module invoice-pdf-module">
    <div class="pdf-panel-left">
      <div class="pdf-toolbar"><label>Document PDF</label><select class="dol-select xs"><option><?=e($invoice['template'] ?? 'standard')?></option></select><a class="small-gray-btn" href="<?=csrf_url('index.php?page=purchase_pdf_generate&type=invoice&id='.$id)?>">GÉNÉRER</a></div>
      <table class="pdf-doc-table"><tbody><?php if(!$docs): ?><tr><td>Aucun PDF généré</td></tr><?php endif; ?><?php foreach($docs as $d): ?><tr><td><i class="fa-solid fa-file-pdf text-blue"></i> <a href="index.php?page=pdf_view&file=<?=urlencode($d['url'])?>"><?=e($d['filename'])?></a> <a class="download-pdf-icon" title="Télécharger" href="index.php?page=pdf_download&file=<?=urlencode($d['url'])?>"><i class="fa-solid fa-download text-gray"></i></a></td><td><?=round((($d['size'] ?? $d['size_bytes'] ?? 0)/1024))?> Ko</td><td><?=e($d['created_at'] ?? '')?></td><td><a onclick="return confirm('Supprimer ce PDF ?')" href="<?=csrf_url('index.php?page=purchase_document_delete&type=invoice&id='.$id.'&doc_id='.(int)$d['id'])?>"><i class="fa-solid fa-trash"></i></a></td></tr><?php endforeach; ?></tbody></table>
    </div>
  </div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
