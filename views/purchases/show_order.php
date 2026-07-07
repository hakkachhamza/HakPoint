<?php
require_once __DIR__.'/_helpers.php';
$pdo = db();
ge_purchase_ensure_tables($pdo);
$id = (int)($_GET['id'] ?? 0);
$order = ge_purchase_order_row($pdo, $id);
if (!$order) redirect_to('index.php?page=purchases');
$title = 'Bon de commande fournisseur '.$order['ref'];
$docs = ge_purchase_docs('order', $id);
$lines = ge_purchase_lines($pdo, 'order', $id);
$ht = (float)($order['amount_ht'] ?? 0);
$tva = (float)($order['amount_tva'] ?? 0);
$ttc = (float)($order['amount_ttc'] ?? 0); if ($ttc <= 0) $ttc = $ht + $tva;
include __DIR__.'/../layouts/header.php';
?>
<?php if(isset($_GET['pdf_generated'])): ?><div class="flash-success">PDF généré avec succès.</div><?php endif; ?>
<?php if(isset($_GET['invoice_created'])): ?><div class="flash-success">Facture fournisseur créée depuis ce bon de commande.</div><?php endif; ?>
<?php if(isset($_GET['stock_applied'])): ?><div class="flash-error">Le stock est déjà appliqué pour ce bon. Impossible de revenir à un statut qui annule la réception.</div><?php endif; ?>
<div class="quote-detail-page invoice-detail-page">
  <div class="quote-detail-head">
    <div class="quote-detail-identity">
      <div class="quote-big-icon"><i class="fa-solid fa-cart-shopping"></i></div>
      <div class="quote-title-block">
        <div class="quote-title-line">Bon de commande fournisseur :</div>
        <span class="quote-client"><i class="fa-solid fa-building"></i> <?=e($order['supplier_name'])?></span>
      </div>
    </div>
    <div class="quote-detail-nav"><a href="index.php?page=purchases">Retour liste</a><div><span class="quote-status gray"><?=e($order['status'])?></span></div></div>
  </div>

  <div class="quote-detail-main">
    <div class="quote-left-lines">
      <div class="quote-line"><span>Référence</span><b><?=e($order['ref'])?></b></div>
      <div class="quote-line"><span>Fournisseur</span><b><?=e($order['supplier_name'])?></b></div>
      <div class="quote-line"><span>Date commande</span><b><?=e($order['order_date'])?></b></div>
      <div class="quote-line"><span>Échéance</span><b><?=e($order['due_date'] ?? '')?></b></div>
      <div class="quote-line"><span>Statut</span><b><?=e($order['status'])?></b></div>
      <?php if(!empty($order['supplier_invoice_id'])): ?><div class="quote-line"><span>Facture fournisseur</span><b><a class="ref" href="index.php?page=supplier_invoice_show&id=<?=(int)$order['supplier_invoice_id']?>"><?=e($order['supplier_invoice_ref'])?></a></b></div><?php endif; ?>
      <div class="quote-line"><span>Note</span><b class="muted-info"><?=nl2br(e($order['note'] ?? ''))?></b></div>
    </div>
    <div class="quote-right-summary">
      <div class="quote-money-row"><span>Montant HT</span><b><?=money($ht)?> MAD</b></div>
      <div class="quote-money-row"><span>Montant TVA</span><b><?=money($tva)?> MAD</b></div>
      <div class="quote-total-box"><span>Total TTC</span><b><?=money($ttc)?> MAD</b></div>
    </div>
  </div>

  <div class="clean-table-box" style="margin:14px 0">
    <table class="clean-table">
      <thead><tr><th>Produit</th><th>Entrepôt</th><th>Qté</th><th>Reçue</th><th>PU HT</th><th>TVA</th><th>Total HT</th></tr></thead>
      <tbody>
      <?php foreach($lines as $l): ?><tr>
        <td><?=e(($l['product_ref'] ? $l['product_ref'].' - ' : '').$l['product_label'])?></td>
        <td><?=e($l['warehouse_id'] ?? '')?></td>
        <td><?=money($l['qty'])?></td>
        <td><?=money($l['received_qty'] ?? 0)?></td>
        <td><?=money($l['pu_ht'])?></td>
        <td><?=money($l['tva_rate'])?>%</td>
        <td><?=money($l['total_ht'])?></td>
      </tr><?php endforeach; if(!$lines): ?><tr><td colspan="7" class="empty-row">Aucune ligne produit. Modifie le bon pour ajouter des lignes.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="quote-detail-actions invoice-detail-actions">
    <a class="orange-btn" href="index.php?page=purchases&edit_order=<?=$id?>">MODIFIER</a>
    <a class="orange-btn" href="<?=csrf_url('index.php?page=purchase_status&type=order&id='.$id.'&status=Validée')?>">VALIDER</a>
    <a class="green-btn" href="<?=csrf_url('index.php?page=purchase_status&type=order&id='.$id.'&status=Reçue')?>">CLASSER REÇUE</a>
    <?php if(empty($order['supplier_invoice_id'])): ?><a class="green-btn" href="<?=csrf_url('index.php?page=purchase_make_invoice&id='.$id)?>">CRÉER FACTURE</a><?php endif; ?>
    <a class="gray-btn" onclick="return confirm('Annuler ce bon de commande ?')" href="<?=csrf_url('index.php?page=purchase_status&type=order&id='.$id.'&status=Annulée')?>">ANNULER</a>
  </div>

  <div class="quote-pdf-module invoice-pdf-module">
    <div class="pdf-panel-left">
      <div class="pdf-toolbar"><label>Document PDF</label><select class="dol-select xs"><option><?=e($order['template'] ?? 'standard')?></option></select><a class="small-gray-btn" href="<?=csrf_url('index.php?page=purchase_pdf_generate&type=order&id='.$id)?>">GÉNÉRER</a></div>
      <table class="pdf-doc-table"><tbody><?php if(!$docs): ?><tr><td>Aucun PDF généré</td></tr><?php endif; ?><?php foreach($docs as $d): ?><tr><td><i class="fa-solid fa-file-pdf text-blue"></i> <a href="index.php?page=pdf_view&file=<?=urlencode($d['url'])?>"><?=e($d['filename'])?></a> <a class="download-pdf-icon" title="Télécharger" href="index.php?page=pdf_download&file=<?=urlencode($d['url'])?>"><i class="fa-solid fa-download text-gray"></i></a></td><td><?=round((($d['size'] ?? $d['size_bytes'] ?? 0)/1024))?> Ko</td><td><?=e($d['created_at'] ?? '')?></td><td><a onclick="return confirm('Supprimer ce PDF ?')" href="<?=csrf_url('index.php?page=purchase_document_delete&type=order&id='.$id.'&doc_id='.(int)$d['id'])?>"><i class="fa-solid fa-trash"></i></a></td></tr><?php endforeach; ?></tbody></table>
    </div>
  </div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
