<?php
require_once __DIR__.'/_helpers.php';

$orders = data_read('orders', []);
$id = (int)($_GET['id'] ?? 0);
$order = find_row_by_id($orders, $id);
if (!$order) redirect_to('index.php?page=orders');

$status = $order['status'] ?? 'Brouillon';
$lines = $order['lines'] ?? [];
$totalHt = (float)($order['total_ht'] ?? 0);
$tva = (float)($order['total_tva'] ?? ($totalHt * .2));
$totalTtc = (float)($order['total_ttc'] ?? ($totalHt + $tva));

$costTotal = 0;
$marginTotal = 0;
foreach ($lines as $l) {
    $qty = (float)($l['qty'] ?? 0);
    $pu = (float)($l['pu_ht'] ?? 0);
    $cost = (float)($l['cost_price'] ?? 0);
    $costTotal += $cost * $qty;
    $marginTotal += ($pu - $cost) * $qty;
}

$statusClass = order_status_class($status);
$clientName = trim((string)($order['client'] ?? ''));
$docs = array_values(array_filter(data_read('order_documents', []), fn($d) => (int)($d['order_id'] ?? 0) === $id));
$linkedExpeditions = array_values(array_filter(data_read('expeditions', []), fn($e) => (int)($e['order_id'] ?? 0) === $id));
$orderInvoiceIds = array_map('intval', (array)($order['invoice_ids'] ?? []));
$linkedInvoices = array_values(array_filter(data_read('invoices', []), fn($inv) => (int)($inv['order_id'] ?? 0) === $id || in_array((int)($inv['id'] ?? 0), $orderInvoiceIds, true)));
$title = 'Commande '.$order['ref'];
include __DIR__.'/../layouts/header.php';
?>

<style>
.content{background:#f5f6f8!important;overflow:auto!important}.order-show-page{background:#fff!important;border:1px solid #d9dde5!important;border-radius:4px!important;padding:0 12px 20px!important;color:#05003d!important;font-size:12px!important;overflow-x:auto!important;max-width:100%!important;box-sizing:border-box!important}.order-show-page *{box-sizing:border-box!important}.order-topbar{display:flex!important;justify-content:space-between!important;align-items:flex-start!important;gap:20px!important;border-bottom:1px solid #cfd3dc!important;padding:14px 0 16px!important;min-width:980px!important}.order-identity{display:flex!important;align-items:center!important;gap:24px!important}.order-doc-icon{width:42px!important;height:42px!important;border:1px solid #e5e7eb!important;background:#fff!important;display:grid!important;place-items:center!important;color:#38BDF8!important;font-size:24px!important;box-shadow:0 2px 8px rgba(15,23,42,.12)!important}.order-ref-line{margin:0 0 4px!important;color:#344054!important;line-height:1.4!important}.order-client-link{display:block!important;color:#001a8f!important;text-decoration:none!important;font-weight:700!important;line-height:1.4!important}.order-identity small{color:#5b667a!important}.order-nav-actions{text-align:right!important;color:#00135f!important;font-weight:700!important;line-height:1.8!important;min-width:180px!important}.order-nav-actions a{color:#00135f!important;text-decoration:none!important}.order-summary-grid{display:grid!important;grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important;gap:16px!important;min-width:980px!important}.order-info-lines>div,.money-line{display:grid!important;grid-template-columns:240px 1fr!important;align-items:center!important;min-height:25px!important;border-bottom:1px solid #d9dde5!important;padding:0!important}.order-info-lines>div span,.money-line span{color:#475467!important;font-weight:500!important}.order-info-lines>div b,.money-line b{color:#111827!important;font-weight:600!important}.order-info-lines small{font-weight:500!important;color:#98a2b3!important}.money-line{grid-template-columns:1fr 160px!important}.money-line b{text-align:right!important}.margin-mini-table{width:100%!important;border-collapse:collapse!important;margin-top:6px!important;font-size:12px!important}.margin-mini-table th,.margin-mini-table td{border-bottom:1px solid #d9dde5!important;padding:7px 8px!important;text-align:right!important}.margin-mini-table th{background:#e6e8ee!important;color:#333c65!important;font-weight:600!important}.margin-mini-table th:first-child,.margin-mini-table td:first-child{text-align:left!important}.order-lines-wrap{margin-top:20px!important;min-width:980px!important;overflow-x:auto!important}.order-detail-lines{width:100%!important;border-collapse:collapse!important;font-size:12px!important;background:#fff!important}.order-detail-lines th,.order-detail-lines td{border-bottom:1px solid #d9dde5!important;padding:8px 7px!important;text-align:right!important;white-space:nowrap!important}.order-detail-lines th{background:#e6e8ee!important;color:#333c65!important;font-weight:600!important}.order-detail-lines th:first-child,.order-detail-lines td:first-child{text-align:left!important}.order-detail-lines .desc{min-width:360px!important}.empty-row{text-align:center!important;color:#667085!important;padding:36px!important}.order-detail-actions{display:flex!important;justify-content:flex-end!important;align-items:center!important;gap:6px!important;flex-wrap:wrap!important;margin:24px 0 56px!important;min-width:980px!important}.green-btn,.small-green-btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;background:#38BDF8!important;color:#fff!important;border:1px solid #0284C7!important;text-decoration:none!important;border-radius:3px!important;padding:8px 11px!important;font-weight:800!important;font-size:11px!important;text-transform:uppercase!important}.green-btn:hover,.small-green-btn:hover{background:#0284C7!important;color:#fff!important}.red-btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;background:#dc2626!important;color:#fff!important;border:1px solid #b91c1c!important;text-decoration:none!important;border-radius:3px!important;padding:8px 11px!important;font-weight:800!important;font-size:11px!important;text-transform:uppercase!important}.red-btn:hover{background:#b91c1c!important;color:#fff!important}.order-lower-grid{display:grid!important;grid-template-columns:minmax(0,1fr) minmax(0,1fr)!important;gap:18px!important;min-width:980px!important}.order-doc-panel{border-top:1px solid #d9dde5!important;background:#fff!important}.order-panel-head{background:#e6e8ee!important;padding:7px 9px!important;display:flex!important;align-items:center!important;gap:10px!important;color:#475467!important;min-height:34px!important}.order-panel-head select{height:28px!important;border:1px solid #cbd5e1!important;border-radius:3px!important;background:#fff!important}.order-doc-panel table,.order-shipment-panel table{width:100%!important;border-collapse:collapse!important;font-size:12px!important;background:#fff!important}.order-doc-panel td,.order-shipment-panel td,.order-shipment-panel th{border-bottom:1px solid #d9dde5!important;padding:7px 6px!important;text-align:left!important}.order-doc-panel a,.order-shipment-panel a{color:#001a8f!important;text-decoration:none!important}.bind-link{text-align:right!important;padding:8px!important;color:#001a8f!important;font-size:11px!important}.empty-small{margin:0!important;padding:12px 6px!important;border-bottom:1px solid #d9dde5!important;color:#98a2b3!important}.order-shipment-panel{width:50%!important;min-width:480px!important;margin-top:20px!important}.order-shipment-panel th{background:#e6e8ee!important;color:#333c65!important;font-weight:600!important}.status-dot{display:inline-block!important;width:10px!important;height:10px!important;border-radius:50%!important;background:#38BDF8!important}.text-green{color:#38BDF8!important}.text-red{color:#dc2626!important}.warn{color:#c39409!important}.muted-pencil{color:#c1c7d0!important}.doc-action-icon{font-size:13px!important;color:#dc2626!important}@media(max-width:900px){.order-show-page{padding:0 8px 14px!important}.order-topbar,.order-summary-grid,.order-lines-wrap,.order-detail-actions,.order-lower-grid{min-width:760px!important}.order-summary-grid,.order-lower-grid{grid-template-columns:1fr!important}.order-detail-actions{justify-content:flex-start!important}.order-shipment-panel{width:100%!important;min-width:760px!important}}
</style>

<div class="order-show-page">
  <?php if(!empty($_GET['need_expedition'])): ?><div class="alert" style="margin:12px 0;"><b>Stock non sorti :</b> crée une expédition puis mets-la en Expédiée/Livrée avant de classer la commande livrée.</div><?php endif; ?>
  <div class="order-topbar">
    <div class="order-identity">
      <div class="order-doc-icon"><i class="fa-solid fa-file-lines"></i></div>
      <div>
        <div class="order-ref-line">Réf. client <span class="muted-pencil"><i class="fa-solid fa-pencil"></i></span> : <?= e($order['client_ref'] ?? '') ?></div>
        <?php if(!empty($order['client_id'])): ?><a href="index.php?page=tiers_show&id=<?= (int)$order['client_id'] ?>" class="order-client-link"><i class="fa-solid fa-building"></i> <?= e($clientName ?: 'Client non sélectionné') ?></a><?php else: ?><span class="order-client-link"><i class="fa-solid fa-building"></i> <?= e($clientName ?: 'Client non sélectionné') ?></span><?php endif; ?>
        <small><?= e($clientName ? 'Commandes liées au client' : '') ?></small>
      </div>
    </div>
    <div class="order-nav-actions"><a href="index.php?page=orders">Retour liste</a> <i class="fa-solid fa-chevron-left"></i> <i class="fa-solid fa-chevron-right"></i><br><span class="quote-status <?= $statusClass ?>"><?= e($status) ?></span></div>
  </div>

  <div class="order-summary-grid">
    <div class="order-info-lines">
      <div><span>Remises</span><b>Ce client n'a pas de remise relative par défaut.<br><small>Ce client n'a pas ou plus de crédit disponible.</small></b></div>
      <div><span>Date</span><b><?= e($order['order_date'] ?? '') ?> <i class="fa-solid fa-triangle-exclamation warn"></i></b></div>
      <div><span>Date prévue de livraison</span><b><?= e($order['delivery_date'] ?? '') ?> <i class="fa-solid fa-pencil muted-pencil"></i></b></div>
      <div><span>Délai de livraison</span><b><?= e($order['delivery_delay'] ?? '') ?> <i class="fa-solid fa-pencil muted-pencil"></i></b></div>
      <div><span>Méthode d'expédition</span><b><?= e($order['shipping_method'] ?? '') ?> <i class="fa-solid fa-pencil muted-pencil"></i></b></div>
      <div><span>Origine</span><b><?= e($order['channel'] ?? '') ?> <i class="fa-solid fa-pencil muted-pencil"></i></b></div>
      <div><span>Conditions de règlement</span><b><?= e($order['payment_terms'] ?? '') ?> <i class="fa-solid fa-pencil muted-pencil"></i></b></div>
      <div><span>Mode de règlement</span><b><?= e($order['payment_mode'] ?? '') ?> <i class="fa-solid fa-pencil muted-pencil"></i></b></div>
    </div>

    <div class="order-money-box">
      <div class="money-line"><span>Montant HT</span><b><?= money($totalHt) ?> MAD</b></div>
      <div class="money-line"><span>Montant TVA</span><b><?= money($tva) ?> MAD</b></div>
      <div class="money-line strong"><span>Montant TTC</span><b><?= money($totalTtc) ?> MAD</b></div>
      <table class="margin-mini-table"><thead><tr><th>Marges</th><th>Prix de vente</th><th>Prix de revient</th><th>Marge</th></tr></thead><tbody><tr><td>Marge / Produits</td><td><?= money($totalHt) ?></td><td><?= money($costTotal) ?></td><td><?= money($marginTotal) ?></td></tr><tr><td>Marge / Services</td><td><?= money(0) ?></td><td><?= money(0) ?></td><td><?= money(0) ?></td></tr><tr><td>Marge totale</td><td><?= money($totalHt) ?></td><td><?= money($costTotal) ?></td><td><?= money($marginTotal) ?></td></tr></tbody></table>
    </div>
  </div>

  <div class="order-lines-wrap">
    <table class="order-detail-lines"><thead><tr><th>Description</th><th>TVA</th><th>P.U. HT</th><th>P.U TTC</th><th>Qté</th><th>Unité</th><th>Réduc.</th><th>Prix de revient</th><th>Total HT</th></tr></thead><tbody>
      <?php if (!$lines): ?><tr><td colspan="9" class="empty-row">Aucune ligne</td></tr><?php endif; ?>
      <?php foreach ($lines as $l): $qty=(float)($l['qty']??0); $pu=(float)($l['pu_ht']??0); $tax=(float)($l['tva']??20); ?>
      <tr><td class="desc"><i class="fa-solid fa-cube text-green"></i> <?= e($l['description'] ?? '') ?></td><td><?= e($tax) ?>%</td><td><?= money($pu) ?></td><td><?= money($l['pu_ttc'] ?? ($pu * (1 + $tax / 100))) ?></td><td><?= e($qty) ?></td><td><?= e($l['unit'] ?? 'u.') ?></td><td><?= e($l['reduction'] ?? '') ?></td><td><?= money($l['cost_price'] ?? 0) ?></td><td><?= money($l['total_ht'] ?? ($pu * $qty)) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table>
  </div>

  <div class="order-detail-actions">
    <a class="green-btn" href="index.php?page=order_edit&id=<?= (int)$order['id'] ?>">MODIFIER</a>
    <a class="green-btn" href="index.php?page=order_email&id=<?= (int)$order['id'] ?>">ENVOYER EMAIL</a>
    <a class="green-btn" href="index.php?page=expedition_new&order_id=<?= (int)$order['id'] ?>">CRÉER EXPÉDITION</a>
    <a class="green-btn" href="<?=csrf_url('index.php?page=order_status&id='.(int)$order['id'].'&status=Livrée')?>">CLASSER LIVRÉE</a>
    <a class="green-btn" href="index.php?page=invoice_new&order_id=<?= (int)$order['id'] ?>">CRÉER FACTURE</a>
    <a class="green-btn" href="<?=csrf_url('index.php?page=order_clone&id='.(int)$order['id'])?>" onclick="return confirm('Cloner cette commande ?')">CLONER</a>
    <a class="red-btn" href="<?=csrf_url('index.php?page=order_status&id='.(int)$order['id'].'&status=Annulée')?>">ANNULER</a>
    <a class="red-btn" href="<?=csrf_url('index.php?page=order_delete&id='.(int)$order['id'])?>" onclick="return confirm('Supprimer cette commande ?')">SUPPRIMER</a>
  </div>

  <div class="order-lower-grid">
    <div class="order-doc-panel">
      <div class="order-panel-head"><span>Modèle de document</span><select id="orderDocModel"><option value="einstein">einstein</option><option value="azur">azur</option></select><a class="small-green-btn" href="<?=csrf_url('index.php?page=order_pdf_generate&id='.(int)$order['id'])?>" onclick="this.href+='&model='+encodeURIComponent(document.getElementById('orderDocModel').value)">GÉNÉRER</a></div>
      <table><tbody>
        <?php if (!$docs): ?><tr><td colspan="4" class="empty-small">Aucun PDF généré.</td></tr><?php endif; ?>
        <?php foreach ($docs as $d): ?><tr><td><a href="index.php?page=pdf_view&file=<?= urlencode($d['url'] ?? '') ?>"><i class="fa-solid fa-file-pdf text-green"></i> <?= e($d['filename'] ?? 'document.pdf') ?></a> <a class="download-pdf-icon" title="Télécharger" href="index.php?page=pdf_download&file=<?=urlencode($d['url'] ?? '')?>"><i class="fa-solid fa-download text-gray"></i></a></td><td><?= e(number_format(((int)($d['size'] ?? 0))/1024, 0, ',', ' ')) ?> Ko</td><td><?= e($d['created_at'] ?? '') ?></td><td><a href="<?=csrf_url('index.php?page=order_document_delete&id='.(int)$order['id'].'&doc_id='.(int)($d['id'] ?? 0))?>" onclick="return confirm('Supprimer ce PDF ?')"><i class="fa-solid fa-trash doc-action-icon"></i></a></td></tr><?php endforeach; ?>
      </tbody></table>
      <div class="bind-link"><i class="fa-solid fa-link"></i> Lier à...</div>
    </div>
    <div class="order-doc-panel"><div class="order-panel-head"><span>Réf.</span><span>Par</span><span>Type</span><span>Titre</span><span>Date</span></div><p class="empty-small">Aucun</p></div>
  </div>

  <div class="order-shipment-panel"><table><thead><tr><th>Type</th><th>Réf.</th><th>Date</th><th>Montant HT</th><th>État</th></tr></thead><tbody>
    <?php if (!$linkedInvoices): ?>
      <tr><td colspan="5" class="empty-small"><i class="fa-solid fa-file-invoice-dollar text-red"></i> Aucune facture créée pour cette commande.</td></tr>
    <?php else: foreach ($linkedInvoices as $inv): ?>
      <tr><td>Facture</td><td><a href="index.php?page=invoice_show&id=<?= (int)($inv['id'] ?? 0) ?>"><i class="fa-solid fa-file-invoice-dollar text-green"></i> <?= e($inv['ref'] ?? '') ?></a></td><td><?= e($inv['invoice_date'] ?? '') ?></td><td><?= money($inv['total_ht'] ?? 0) ?></td><td><span class="status-dot"></span> <?= e($inv['status'] ?? 'Brouillon') ?></td></tr>
    <?php endforeach; endif; ?>
  </tbody></table></div>

  <div class="order-shipment-panel"><table><thead><tr><th>Type</th><th>Réf.</th><th>Date</th><th>Montant HT</th><th>État</th></tr></thead><tbody>
    <?php if (!$linkedExpeditions): ?>
      <tr><td colspan="5" class="empty-small"><i class="fa-solid fa-dolly text-red"></i> Aucune expédition créée pour cette commande.</td></tr>
    <?php else: foreach ($linkedExpeditions as $ship): ?>
      <tr><td>Expédition</td><td><a href="index.php?page=expedition_show&id=<?= (int)($ship['id'] ?? 0) ?>"><i class="fa-solid fa-dolly text-green"></i> <?= e($ship['ref'] ?? '') ?></a></td><td><?= e($ship['delivery_date'] ?? $ship['date'] ?? '') ?></td><td><?= money($totalHt) ?></td><td><span class="status-dot"></span> <?= e($ship['status'] ?? 'Brouillon') ?></td></tr>
    <?php endforeach; endif; ?>
  </tbody></table></div>
</div>

<?php include __DIR__.'/../layouts/footer.php'; ?>
