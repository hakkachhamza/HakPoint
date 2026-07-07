<?php
require_once __DIR__.'/_helpers.php';
$invoices=data_read('invoices',[]);
$id=(int)($_GET['id']??0);
$invoice=find_row_by_id($invoices,$id);
if(!$invoice) redirect_to('index.php?page=invoices');
$title='Facture '.$invoice['ref'];
include __DIR__.'/../layouts/header.php';
[$totalHt,$tva,$totalTtc]=invoice_totals($invoice);
$client=$invoice['client']?:'Client non sélectionné';
$status=$invoice['status']??'Brouillon';
$lines=$invoice['lines']??[];
$costTotal=0;
foreach($lines as $l){ $costTotal += (float)($l['cost_price']??0) * (float)($l['qty']??0); }
$marginTotal=$totalHt-$costTotal;
$payments=$invoice['payments']??[];
$paid=0; foreach($payments as $p){ $paid+=(float)($p['amount']??0); }
$rest=max(0,$totalTtc-$paid);
$docs=data_read('invoice_documents',[]); $docs=array_values(array_filter($docs,fn($d)=>(int)($d['invoice_id']??0)===$id));
?>
<?php if(isset($_GET['pdf_generated'])): ?><div class="flash-success">PDF facture généré avec succès.</div><?php endif; ?>
<?php if(isset($_GET['updated'])): ?><div class="invoice-flash">Facture enregistrée avec succès.</div><?php endif; ?>
<?php if(($_GET['created_from_order'] ?? '')==='1'): ?><div class="invoice-flash">Facture créée depuis la commande avec toutes les lignes.</div><?php endif; ?>
<?php if(($_GET['created_from_expedition'] ?? '')==='1'): ?><div class="invoice-flash">Facture créée depuis l’expédition avec toutes les lignes.</div><?php endif; ?>
<?php if(isset($_GET['payment_added'])): ?><div class="invoice-flash">Règlement ajouté avec succès.</div><?php endif; ?>
<?php if(isset($_GET['email_saved'])): ?><div class="invoice-flash">Email enregistré dans l'historique local.</div><?php endif; ?>
<div class="quote-detail-page invoice-detail-page">
    <div class="quote-detail-head">
        <div class="quote-detail-identity">
            <div class="quote-big-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
            <div class="quote-title-block">
                <div class="quote-title-line">Réf. client <i class="fa-solid fa-pencil muted-pencil"></i> :</div>
                <?php if(!empty($invoice['client_id'])): ?><a href="index.php?page=tiers_show&id=<?=(int)$invoice['client_id']?>" class="quote-client"><i class="fa-solid fa-building"></i> <?=e($client)?></a><?php else: ?><span class="quote-client"><i class="fa-solid fa-building"></i> <?=e($client)?></span><?php endif; ?>
            </div>
        </div>
        <div class="quote-detail-nav"><a href="index.php?page=invoices">Retour liste</a><i class="fa-solid fa-chevron-left"></i><i class="fa-solid fa-chevron-right disabled"></i><div><span class="quote-status <?=invoice_badge_class($status)?>"><?=e($status)?></span></div></div>
    </div>

    <div class="quote-detail-main">
        <div class="quote-left-lines">
            <div class="quote-line"><span>Type</span><b><span class="mini-pill"><?=e(str_replace('Facture ','',$invoice['type']??'Standard'))?></span></b></div>
            <div class="quote-line"><span>Réductions ou crédits disponibles</span><b class="muted-info">Ce client n'a pas de remise relative par défaut.<br>Ce client n'a pas ou plus de crédit disponible.</b></div>
            <?php if(!empty($invoice['order_id'])): ?><div class="quote-line"><span>Commande source</span><b><a class="ref" href="index.php?page=order_show&id=<?=(int)$invoice['order_id']?>"><i class="fa-solid fa-link"></i> <?=e($invoice['order_ref'] ?? ('Commande #'.$invoice['order_id']))?></a></b></div><?php endif; ?>
            <?php if(!empty($invoice['expedition_id'])): ?><div class="quote-line"><span>Expédition source</span><b><a class="ref" href="index.php?page=expedition_show&id=<?=(int)$invoice['expedition_id']?>"><i class="fa-solid fa-link"></i> <?=e($invoice['expedition_ref'] ?? ('Expédition #'.$invoice['expedition_id']))?></a></b></div><?php endif; ?>
            <div class="quote-line"><span>Date facturation</span><b><?=e($invoice['invoice_date']??'')?></b></div>
            <div class="quote-line"><span>Conditions de règlement</span><b><i class="fa-solid fa-pencil muted-pencil"></i> <?=e($invoice['payment_terms']??'')?></b></div>
            <div class="quote-line"><span>Date limite règlement</span><b><i class="fa-solid fa-pencil muted-pencil"></i> <?=e($invoice['due_date']??($invoice['invoice_date']??''))?></b></div>
            <div class="quote-line"><span>Mode de règlement</span><b><i class="fa-solid fa-pencil muted-pencil"></i> <?=e($invoice['payment_mode']??'')?></b></div>
            <div class="quote-line"><span>Compte bancaire</span><b><i class="fa-solid fa-pencil muted-pencil"></i> <?=e($invoice['bank_account']??'')?></b></div>
        </div>
        <div class="quote-right-summary">
            <div class="quote-money-row"><span>Montant HT</span><b><?=money($totalHt)?> MAD</b></div>
            <div class="quote-money-row"><span>Montant TVA</span><b><?=money($tva)?> MAD</b></div>
            <div class="quote-money-row"><span>Montant TTC</span><b><?=money($totalTtc)?> MAD</b></div>
            <table class="quote-margin-table invoice-payment-table">
                <thead><tr><th>Règlements</th><th>Date</th><th>Type</th><th>Compte bancaire</th><th>Montant</th></tr></thead>
                <tbody>
                    <?php if(!$payments): ?><tr><td colspan="5" class="muted-info">Aucun règlement pour le moment</td></tr><?php endif; ?>
                    <?php foreach($payments as $p): ?><tr><td><i class="fa-solid fa-money-bill text-green"></i> <?=e($p['ref']??'PAY')?></td><td><?=e($p['date']??'')?></td><td><?=e($p['type']??'')?></td><td><?=e($p['bank']??'')?></td><td><?=money($p['amount']??0)?></td></tr><?php endforeach; ?>
                    <tr><td colspan="4" class="muted-info">Déjà réglé (hors avoirs et acomptes)</td><td><?=money($paid)?></td></tr>
                    <tr><td colspan="4" class="muted-info">Facturé</td><td><?=money($totalTtc)?></td></tr>
                    <tr><td colspan="4" class="muted-info">Reste à payer</td><td class="rest-pay"><?=money($rest)?></td></tr>
                </tbody>
            </table>
            <table class="quote-margin-table"><thead><tr><th>Marges</th><th>Prix de vente</th><th>Prix de revient</th><th>Marge</th></tr></thead><tbody><tr><td><b>Marge / Produits</b></td><td><?=money($totalHt)?></td><td><?=money($costTotal)?></td><td><?=money($marginTotal)?></td></tr><tr><td>Marge / Services</td><td><?=money(0)?></td><td><?=money(0)?></td><td><?=money(0)?></td></tr><tr><td class="muted-info">Marge totale</td><td><?=money($totalHt)?></td><td><?=money($costTotal)?></td><td><?=money($marginTotal)?></td></tr></tbody></table>
            <form class="invoice-mini-form" method="post" action="index.php?page=invoice_payment_add"><?=csrf_field()?>
                <input type="hidden" name="id" value="<?=$id?>">
                <div class="mini-grid">
                    <div><label>Date</label><input name="date" value="<?=date('d/m/Y H:i')?>"></div>
                    <div><label>Type</label><select name="type"><option>Chèque</option><option>Virement bancaire</option><option>Carte bancaire</option><option>Espèce</option></select></div>
                    <div><label>Compte</label><input name="bank" value="<?=e($invoice['bank_account']??'001')?>"></div>
                    <div><label>Montant</label><input name="amount" value="<?=money($rest)?>"></div>
                    <button class="orange-btn" type="submit">AJOUTER RÈGLEMENT</button>
                </div>
            </form>
        </div>
    </div>

    <div class="quote-lines-wrap"><table class="quote-lines-table"><thead><tr><th>Description</th><th>TVA</th><th>P.U. HT</th><th>P.U TTC</th><th>Qté</th><th>Unité</th><th>Réduc.</th><th>Prix de revient</th><th>Total HT</th></tr></thead><tbody>
        <?php if(!$lines): ?><tr><td colspan="9" class="muted-info">Aucune ligne ajoutée</td></tr><?php endif; ?>
        <?php foreach($lines as $l): ?><tr><td class="quote-desc"><i class="fa-solid fa-cube text-olive"></i> <?=nl2br(e($l['description']??''))?></td><td><?=e($l['tva']??20)?>%</td><td><?=money($l['pu_ht']??0)?></td><td><?=money($l['pu_ttc']??(((float)($l['pu_ht']??0))*1.2))?></td><td><?=e($l['qty']??0)?></td><td><?=e($l['unit']??'')?></td><td><?=e($l['reduction']??'')?></td><td><?=money($l['cost_price']??0)?></td><td><?=money($l['total_ht']??0)?></td></tr><?php endforeach; ?>
    </tbody></table></div>

    <div class="quote-detail-actions invoice-detail-actions"><a class="orange-btn" href="index.php?page=invoice_edit&id=<?=$id?>">MODIFIER</a><a class="orange-btn" href="<?=csrf_url('index.php?page=invoice_status&id='.$id.'&status=Impayée')?>">RÉOUVRIR</a><a class="green-btn" href="<?=csrf_url('index.php?page=invoice_status&id='.$id.'&status=Payée')?>">CLASSER PAYÉE</a><a class="orange-btn" href="index.php?page=invoice_email&id=<?=$id?>">ENVOYER EMAIL</a><a class="orange-btn" href="<?=csrf_url('index.php?page=invoice_clone&id='.$id)?>">CLONER</a><a class="gray-btn" onclick="return confirm('Supprimer cette facture ?')" href="<?=csrf_url('index.php?page=invoice_delete&id='.$id)?>">SUPPRIMER</a></div>

    <div class="quote-pdf-module invoice-pdf-module">
        <div class="pdf-panel-left">
            <div class="pdf-toolbar"><label>Modèle de document</label><select class="dol-select xs"><option><?=e($invoice['template']??'crabe')?></option></select><a class="small-gray-btn" href="<?=csrf_url('index.php?page=invoice_pdf_generate&id='.$id)?>">GÉNÉRER</a></div>
            <table class="pdf-doc-table"><tbody><?php if(!$docs): ?><tr><td>Aucun PDF généré</td></tr><?php endif; ?><?php foreach($docs as $d): ?><tr><td><i class="fa-solid fa-file-pdf text-blue"></i> <a href="index.php?page=pdf_view&file=<?=urlencode($d['url'])?>"><?=e($d['filename'])?></a> <a class="download-pdf-icon" title="Télécharger" href="index.php?page=pdf_download&file=<?=urlencode($d['url'])?>"><i class="fa-solid fa-download text-gray"></i></a></td><td><i class="fa-solid fa-magnifying-glass"></i></td><td><?=round(($d['size']??0)/1024)?> Ko</td><td><?=e($d['created_at']??'')?></td><td><a onclick="return confirm('Supprimer ce PDF ?')" href="<?=csrf_url('index.php?page=invoice_document_delete&id='.$id.'&doc_id='.(int)$d['id'])?>"><i class="fa-solid fa-trash"></i></a></td></tr><?php endforeach; ?></tbody></table>
        </div>
        <div class="pdf-panel-right"><table class="pdf-doc-table"><thead><tr><th>Réf.</th><th>Par</th><th>Type</th><th>Titre</th><th>Date</th></tr></thead><tbody><tr><td colspan="5" class="muted-info">Aucun</td></tr></tbody></table></div>
    </div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
