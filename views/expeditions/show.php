<?php
require __DIR__.'/_helpers.php';
require_once __DIR__.'/../invoices/_helpers.php';
$id=(int)($_GET['id']??0);
$r=expedition_find($id);
if(!$r) redirect_to('index.php?page=expeditions');
$tiers=tiers_all(); $tier=find_row_by_id($tiers,(int)($r['tier_id']??0));
$lines=expedition_lines($id); $docs=expedition_documents($id); $expInvoiceIds=array_map('intval',(array)($r['invoice_ids'] ?? [])); $linkedInvoices=array_values(array_filter(data_read('invoices', []), fn($inv)=>(int)($inv['expedition_id'] ?? 0)===$id || in_array((int)($inv['id'] ?? 0), $expInvoiceIds, true))); 
$title=expedition_display_ref($r);
include __DIR__.'/../layouts/header.php';
$ref=expedition_display_ref($r);
$cmdRef=$r['order_ref']??('SO'.date('ym').'-'.str_pad((string)$id,6,'0',STR_PAD_LEFT));
$shipmentStatus=$r['status']??'Validée';
?>
<div class="dol-detail-page expedition-detail-page">
  <?php if(!empty($_GET['stock_error'])): ?><div class="alert" style="margin:0 0 12px;"><b>Stock insuffisant :</b> <?=e($_GET['stock_error'])?>. Corrige le stock ou crée une réception avant de clôturer l’expédition.</div><?php endif; ?>
  <div class="detail-hero clean-hero">
    <div class="detail-icon"><i class="fa-solid fa-dolly text-green"></i></div>
    <div>
      <h2>Réf. client <i class="fa-solid fa-pen muted small-icon"></i> :</h2>
      <p><i class="fa-solid fa-building text-purple"></i> <a class="ref" href="index.php?page=tiers_show&id=<?=(int)($r['tier_id']??0)?>"><?=e($r['tier_name']??'')?></a></p>
    </div>
    <div class="detail-right"><a href="index.php?page=expeditions" class="ref">Retour liste</a> <i class="fa-solid fa-chevron-left muted"></i> <i class="fa-solid fa-chevron-right"></i><br><span class="badge <?=e(expedition_status_badge($shipmentStatus))?>"><?=e($shipmentStatus)?><?=($shipmentStatus==='Validée')?' (produits à envoyer ou envoyés)':''?></span><br><span class="muted">Stock traité: <?=!empty($r['stock_done'])?'Oui':'Non'?></span></div>
  </div>

  <div class="two-cols shipment-info-grid">
    <table class="info-table compact-info">
      <tr><td>Réf. commande</td><td><i class="fa-solid fa-file-lines text-green"></i> <?php if(!empty($r['order_id'])): ?><a class="ref" href="index.php?page=order_show&id=<?=(int)$r['order_id']?>"><?=e($cmdRef)?></a><?php else: ?><span class="ref"><?=e($cmdRef)?></span><?php endif; ?></td></tr>
      <tr><td>Date création</td><td><?=e(format_date_fr($r['created_at']??$r['date']??''))?></td></tr>
      <tr><td>Date prévue de livraison</td><td><i class="fa-solid fa-pen muted"></i> <?=e(format_date_fr($r['delivery_date']??''))?></td></tr>
      <tr><td>Poids</td><td><i class="fa-solid fa-pen muted"></i> <?=e($r['weight']??'')?></td></tr>
      <tr><td>Largeur</td><td><i class="fa-solid fa-pen muted"></i> <?=e($r['width']??'')?></td></tr>
      <tr><td>Hauteur</td><td><i class="fa-solid fa-pen muted"></i> <?=e($r['height']??'')?></td></tr>
      <tr><td>Profondeur</td><td><i class="fa-solid fa-pen muted"></i> <?=e($r['depth']??'')?></td></tr>
      <tr><td>Volume</td><td><i class="fa-solid fa-pen muted"></i> <?=e($r['volume']??'')?></td></tr>
    </table>
    <table class="info-table compact-info">
      <tr><td>Méthode d'expédition</td><td><i class="fa-solid fa-pen muted"></i> <?=e($r['shipping_method']??'')?></td></tr>
      <tr><td>Numéro de suivi</td><td><i class="fa-solid fa-pen muted"></i> <?=e($r['tracking']??'')?></td></tr>
    </table>
  </div>

  <div class="clean-table-box shipment-products-box">
    <table class="clean-table shipment-products-table">
      <thead><tr><th>Produits</th><th>Qté commandée</th><th>Qté dans les autres expéditions</th><th>Qté à expédier</th><th>Entrepôt source</th><th>Poids calculé</th><th>Volume calculé</th></tr></thead>
      <tbody>
      <?php foreach($lines as $l): $qty=(float)($l['qty']??0); ?>
        <tr>
          <td><i class="fa-solid fa-box text-gold"></i> <a class="ref" href="index.php?page=product_show&id=<?=(int)($l['product_id']??0)?>"><?=e($l['product_ref']??'')?></a> - <?=e($l['product_label']??'')?></td>
          <td class="num"><?=e($qty)?> <?=e($l['unit']??'')?></td>
          <td class="num">0</td>
          <td class="num"><?=e($qty)?> <?=e($l['unit']??'')?></td>
          <td><i class="fa-solid fa-box-open text-gold"></i> <a class="ref" href="index.php?page=warehouse_show&id=<?=(int)($r['warehouse_id']??0)?>"><?=e($r['warehouse_name']??'')?></a></td>
          <td class="num">0</td>
          <td class="num">0 m³</td>
        </tr>
      <?php endforeach; if(!$lines): ?><tr><td colspan="7" class="empty-row">Aucune ligne</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="right-actions shipment-actions">
    <a class="btn orange" href="index.php?page=expedition_edit&id=<?=$id?>">MODIFIER</a>
    <a class="btn orange" href="index.php?page=expedition_email&id=<?=$id?>">ENVOYER EMAIL</a>
    <a class="btn orange" href="index.php?page=invoice_new&from_expedition=<?=$id?>">CRÉER FACTURE</a>
    <a class="btn orange" href="<?=csrf_url('index.php?page=expedition_status&id='.$id.'&status=Livrée')?>">CLÔTURER</a>
    <a class="btn danger-light" href="<?=csrf_url('index.php?page=expedition_status&id='.$id.'&status=Annulée')?>">ANNULER</a>
    <a class="btn danger-light" href="<?=csrf_url('index.php?page=expedition_delete&id='.$id)?>" onclick="return confirm('Supprimer cette expédition ?')">SUPPRIMER</a>
  </div>

  <div class="two-cols lower-panels shipment-docs-panels">
    <div class="panel">
      <div class="docbar"><span>Modèle de document</span><select><option>rouget</option><option>standard</option></select><a class="btn small" href="<?=csrf_url('index.php?page=expedition_pdf_generate&id='.$id)?>">GÉNÉRER</a></div>
      <table class="clean-table"><tbody>
      <?php foreach($docs as $d): ?><tr><td><i class="fa-regular fa-file-pdf"></i> <a href="index.php?page=pdf_view&file=<?=urlencode($d['url'])?>"><?=e($d['filename'])?></a> <a class="download-pdf-icon" title="Télécharger" href="index.php?page=pdf_download&file=<?=urlencode($d['url'])?>"><i class="fa-solid fa-download text-gray"></i></a></td><td><i class="fa-solid fa-magnifying-glass muted"></i></td><td><?=e(round(($d['size']??0)/1024))?> Ko</td><td><?=e($d['created_at']??'')?></td><td><a href="<?=csrf_url('index.php?page=expedition_document_delete&id='.(int)$d['id'].'&expedition_id='.$id)?>"><i class="fa-solid fa-trash"></i></a></td></tr><?php endforeach; if(!$docs): ?><tr><td>Aucun</td></tr><?php endif; ?>
      </tbody></table>
      <div class="link-to"><i class="fa-solid fa-link"></i> Lier à...</div>
    </div>
    <div class="panel"><div class="panel-head">Réf. <span>Par</span> <span>Type</span> <span>Titre</span> <span>▴ Date</span></div><table class="clean-table"><tr><td>Aucun</td></tr></table></div>
  </div>

  <div class="panel linked-object-panel">
    <table class="clean-table"><thead><tr><th>Type</th><th>Réf.</th><th>Date</th><th>Montant HT</th><th>État</th></tr></thead><tbody>
      <tr><td>Commande client</td><td><i class="fa-solid fa-file-lines text-green"></i> <?php if(!empty($r['order_id'])): ?><a class="ref" href="index.php?page=order_show&id=<?=(int)$r['order_id']?>"><?=e($cmdRef)?></a><?php else: ?><?=e($cmdRef)?><?php endif; ?></td><td><?=e(format_date_fr($r['date']??''))?></td><td><?=e(money($r['amount_ht']??0))?></td><td><span class="dot green-dot"></span></td></tr>
      <?php foreach($linkedInvoices as $inv): ?><tr><td>Facture</td><td><i class="fa-solid fa-file-invoice-dollar text-green"></i> <a class="ref" href="index.php?page=invoice_show&id=<?=(int)($inv['id']??0)?>"><?=e($inv['ref']??'')?></a></td><td><?=e($inv['invoice_date']??'')?></td><td><?=money($inv['total_ht']??0)?></td><td><span class="badge <?=e(invoice_badge_class($inv['status']??'Brouillon'))?>"><?=e($inv['status']??'Brouillon')?></span></td></tr><?php endforeach; ?>
    </tbody></table>
  </div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
