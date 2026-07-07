<?php
require_once __DIR__.'/_helpers.php';
$quotes = data_read('quotes', []);
$id = (int)($_GET['id'] ?? 0);
$quote = find_row_by_id($quotes, $id);
if (!$quote) redirect_to('index.php?page=quotes');

$title = 'Devis '.$quote['ref'];
include __DIR__.'/../layouts/header.php';

$status = $quote['status'] ?? 'Ouvert';
$client = $quote['client'] ?: 'Client non sélectionné';
$proposalDate = $quote['proposal_date'] ?? date('d/m/Y');
$endDate = $quote['end_date'] ?? ($quote['validity_end'] ?? '');
$totalHt = (float)($quote['total_ht'] ?? 0);
$tva = (float)($quote['total_tva'] ?? ($totalHt * 0.20));
$totalTtc = (float)($quote['total_ttc'] ?? ($totalHt + $tva));

$lines = $quote['lines'] ?? [];
$costTotal = 0;
foreach ($lines as $l) {
    $costTotal += (float)($l['cost_price'] ?? 0) * (float)($l['qty'] ?? 0);
}
$marginTotal = $totalHt - $costTotal;
function quote_show_badge($status) {
    return match($status) {
        'Brouillon' => 'Brouillon',
        'Signée', 'Signée (à facturer)' => 'Validée (proposition ouverte)',
        'Facturée' => 'Facturée',
        'Non signée (fermée)', 'Refusée' => 'Refusée',
        default => $status ?: 'Ouvert'
    };
}
?>
<?php if(isset($_GET['email_saved'])): ?><div class="flash-success">Email préparé/enregistré avec succès.</div><?php endif; ?>
<div class="quote-detail-page">
    <div class="quote-detail-head">
        <div class="quote-detail-identity">
            <div class="quote-big-icon"><i class="fa-solid fa-file-signature"></i></div>
            <div class="quote-title-block">
                <div class="quote-title-line">Réf. client <i class="fa-solid fa-pencil muted-pencil"></i> :</div>
                <?php if(!empty($quote['client_id'])): ?><a href="index.php?page=tiers_show&id=<?=(int)$quote['client_id']?>" class="quote-client"><i class="fa-solid fa-building"></i> <?=e($client)?></a><?php else: ?><span class="quote-client"><i class="fa-solid fa-building"></i> <?=e($client)?></span><?php endif; ?>
            </div>
        </div>
        <div class="quote-detail-nav">
            <a href="index.php?page=quotes">Retour liste</a>
            <i class="fa-solid fa-chevron-left disabled"></i>
            <i class="fa-solid fa-chevron-right"></i>
            <div><span class="quote-state-badge"><?=e(quote_show_badge($status))?></span></div>
        </div>
    </div>

    <div class="quote-detail-main">
        <div class="quote-left-lines">
            <div class="quote-line"><span>Remises</span><b class="muted-info">Ce client n'a pas de remise relative par défaut.<br>Ce client n'a pas ou plus de crédit disponible.</b></div>
            <div class="quote-line"><span>Date de proposition</span><b><?=e($proposalDate)?></b></div>
            <div class="quote-line"><span>Date de fin de validité</span><b><i class="fa-solid fa-pencil muted-pencil"></i> <?=e($endDate ?: '—')?> <i class="fa-solid fa-triangle-exclamation warn"></i></b></div>
            <div class="quote-line"><span>Conditions de règlement</span><b><i class="fa-solid fa-pencil muted-pencil"></i> <?=e($quote['payment_terms'] ?? '')?></b></div>
            <div class="quote-line"><span>Mode de règlement</span><b><i class="fa-solid fa-pencil muted-pencil"></i> <?=e($quote['payment_mode'] ?? '')?></b></div>
            <div class="quote-line"><span>Date de livraison</span><b><i class="fa-solid fa-pencil muted-pencil"></i> <?=e($quote['delivery_date'] ?? '')?></b></div>
            <div class="quote-line"><span>Délai livraison <i class="fa-solid fa-circle-info info"></i></span><b><i class="fa-solid fa-pencil muted-pencil"></i> <?=e($quote['delivery_delay'] ?? '')?></b></div>
            <div class="quote-line"><span>Méthode d'expédition</span><b><i class="fa-solid fa-pencil muted-pencil"></i> <?=e($quote['shipping_method'] ?? '')?></b></div>
            <div class="quote-line"><span>Origine</span><b><i class="fa-solid fa-pencil muted-pencil"></i> <?=e($quote['origin'] ?? '')?></b></div>
        </div>

        <div class="quote-right-summary">
            <div class="quote-money-row"><span>Montant HT</span><b><?=money($totalHt)?> MAD</b></div>
            <div class="quote-money-row"><span>Montant TVA</span><b><?=money($tva)?> MAD</b></div>
            <div class="quote-money-row"><span>Montant TTC</span><b><?=money($totalTtc)?> MAD</b></div>
            <table class="quote-margin-table">
                <thead><tr><th>Marges</th><th>Prix de vente</th><th>Prix de revient</th><th>Marge</th></tr></thead>
                <tbody>
                    <tr><td><b>Marge / Produits</b></td><td><?=money($totalHt)?></td><td><?=money($costTotal)?></td><td><?=money($marginTotal)?></td></tr>
                    <tr><td>Marge / Services</td><td><?=money(0)?></td><td><?=money(0)?></td><td><?=money(0)?></td></tr>
                    <tr><td class="muted-info">Marge totale</td><td><?=money($totalHt)?></td><td><?=money($costTotal)?></td><td><?=money($marginTotal)?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="quote-lines-wrap">
        <table class="quote-lines-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>TVA</th>
                    <th>P.U. HT</th>
                    <th>P.U TTC</th>
                    <th>Qté</th>
                    <th>Unité</th>
                    <th>Réduc.</th>
                    <th>Prix de<br>revient</th>
                    <th>Total HT</th>
                </tr>
            </thead>
            <tbody>
            <?php if(!$lines): ?><tr><td colspan="9" class="muted-info">Aucune ligne ajoutée</td></tr><?php endif; ?>
            <?php foreach($lines as $line): ?>
                <tr>
                    <td class="quote-desc"><i class="fa-solid fa-cube text-olive"></i> <?=e($line['description'] ?? '')?></td>
                    <td><?=e($line['tva'] ?? 20)?>%</td>
                    <td><?=money($line['pu_ht'] ?? 0)?></td>
                    <td><?=money($line['pu_ttc'] ?? 0)?></td>
                    <td><?=e($line['qty'] ?? 0)?></td>
                    <td><?=e($line['unit'] ?? '')?></td>
                    <td><?=e($line['reduction'] ?? '')?></td>
                    <td><?=money($line['cost_price'] ?? 0)?></td>
                    <td><?=money($line['total_ht'] ?? 0)?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="quote-detail-actions">
        <a class="orange-btn" href="index.php?page=quote_edit&id=<?=(int)$quote['id']?>">MODIFIER</a>
        <a class="orange-btn" href="index.php?page=quote_email&id=<?=(int)$quote['id']?>">ENVOYER EMAIL</a>
        <a class="orange-btn" href="<?=csrf_url('index.php?page=quote_status&id='.(int)$quote['id'].'&status=Ouvert')?>">VALIDER</a><a class="orange-btn" href="<?=csrf_url('index.php?page=quote_status&id='.(int)$quote['id'].'&status=Signée (à facturer)')?>">SIGNER</a><a class="orange-btn" href="<?=csrf_url('index.php?page=quote_status&id='.(int)$quote['id'].'&status=Non signée (fermée)')?>">REFUSER</a><a class="orange-btn" href="<?=csrf_url('index.php?page=quote_status&id='.(int)$quote['id'].'&status=Facturée')?>">CLASSER FACTURÉ</a>
        <a class="orange-btn" href="<?=csrf_url('index.php?page=quote_clone&id='.(int)$quote['id'])?>" onclick="return confirm('Cloner ce devis ?')">CLONER</a>
        <a class="gray-btn" href="<?=csrf_url('index.php?page=quote_delete&id='.(int)$quote['id'])?>" onclick="return confirm('Supprimer ce devis ?')">SUPPRIMER</a>
    </div>
</div>

    <?php
    $docs = array_values(array_filter(data_read('quote_documents', []), fn($d)=> (int)($d['quote_id']??0)===(int)$quote['id']));
    ?>
    <div class="quote-pdf-module">
        <div class="pdf-panel-left">
            <div class="pdf-toolbar">
                <label>Modèle de document</label>
                <select class="dol-select xs"><option>azur</option></select>
                <a class="small-gray-btn" href="<?=csrf_url('index.php?page=quote_pdf_generate&id='.(int)$quote['id'])?>">GÉNÉRER</a>
            </div>
            <table class="pdf-doc-table">
                <tbody>
                <?php if(!$docs): ?>
                    <tr><td class="muted-info">Aucun document généré</td><td></td><td></td><td></td><td></td></tr>
                <?php endif; ?>
                <?php foreach(array_reverse($docs) as $doc): ?>
                    <tr>
                        <td><i class="fa-regular fa-file-pdf text-blue"></i> <a href="index.php?page=pdf_view&file=<?=urlencode($doc['url'])?>"><?=e($doc['filename'])?></a> <a class="download-pdf-icon" title="Télécharger" href="index.php?page=pdf_download&file=<?=urlencode($doc['url'])?>"><i class="fa-solid fa-download text-gray"></i></a></td>
                        <td><i class="fa-solid fa-magnifying-glass muted-info"></i></td>
                        <td><?=number_format(((int)($doc['size']??0))/1024,0,',',' ')?> Ko</td>
                        <td><?=e($doc['created_at']??'')?></td>
                        <td><a href="index.php?page=pdf_view&file=<?=urlencode($doc['url'])?>"><i class="fa-solid fa-arrow-up-right-from-square"></i> <?=e($doc['url'])?></a> <a onclick="return confirm('Supprimer ce document ?')" href="<?=csrf_url('index.php?page=quote_document_delete&id='.(int)$quote['id'].'&doc_id='.(int)$doc['id'])?>"><i class="fa-solid fa-trash"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="pdf-panel-right">
            <table class="pdf-doc-table">
                <thead><tr><th>Réf.</th><th>Par</th><th>Type</th><th>Titre</th><th>▲ Date</th></tr></thead>
                <tbody><tr><td class="muted-info">Aucun</td><td></td><td></td><td></td><td></td></tr></tbody>
            </table>
        </div>
    </div>

<?php include __DIR__.'/../layouts/footer.php'; ?>
