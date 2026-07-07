<?php
require_once __DIR__.'/_helpers.php';
$title='Liste des factures';
include __DIR__.'/../layouts/header.php';
$invoices=data_read('invoices',[]);
$status=$_GET['status']??'';
if($status) $invoices=array_values(array_filter($invoices,fn($i)=>($i['status']??'')===$status));
[$invoices,$invoiceTotal,$invoicePage,$invoicePages]=ge_list_slice($invoices);
$count=$invoiceTotal;
?>
<div class="invoice-list-page quote-list-page">
<form method="post" action="index.php?page=invoice_bulk_action" id="invoiceBulkForm"><?=csrf_field()?>
    <div class="quote-topline invoice-topline">
        <div class="quote-count"><i class="fa-solid fa-file-invoice-dollar text-green"></i><span>(<?=$count?>)</span></div>
        <div class="quote-bulkbar" id="invoiceBulkBar" aria-hidden="true">
            <select name="bulk_action" class="dol-select action-select" required>
                <option value="">-- Sélectionner l'action --</option>
                <option value="mark_unpaid">Classer impayée</option>
                <option value="mark_paid">Classer payée</option>
                <option value="abandon">Abandonner</option>
                <option value="send_email">Envoyer email</option>
                <option value="generate_pdf">Générer le PDF</option>
                <option value="delete">Supprimer</option>
            </select>
            <button type="submit" class="bulk-confirm">CONFIRMER</button>
        </div>
        <div class="quote-pager">
            <select><option>20</option><option>50</option><option>100</option></select>
            <span><?=e($invoicePage)?></span><span>/</span><span><?=e($invoicePages)?></span>
            <span class="page-arrow disabled"><i class="fa-solid fa-chevron-right"></i></span>
            <a class="round" href="index.php?page=invoice_new"><i class="fa-solid fa-plus"></i></a>
        </div>
    </div>

    <div class="quote-filters-panel">
        <div class="q-filter"><i class="fa-solid fa-user"></i><select><option>Tiers ayant pour commercial...</option></select></div>
        <div class="q-filter"><i class="fa-solid fa-user"></i><select><option>Liés à un contact utilisateur part...</option></select></div>
    </div>
    <div class="quote-table-wrap">
        <table class="quote-dol-list invoice-dol-list">
            <thead>
                <tr class="quote-filters-row invoice-filters-row">
                    <th><input></th>
                    <th><div class="date-pair"><input placeholder="Du"><input placeholder="au"></div></th>
                    <th><div class="date-pair"><input placeholder="Avant"><label><input type="checkbox" style="width:auto;height:auto"> Alerte</label></div></th>
                    <th><input></th>
                    <th><input></th>
                    <th><input></th>
                    <th><input></th>
                    <th><input></th>
                    <th><select><option></option><option>Brouillon</option><option>Impayée</option><option>Payée</option><option>Abandonnée</option></select></th>
                    <th class="search-cell"><i class="fa-solid fa-magnifying-glass"></i><i class="fa-solid fa-xmark"></i></th>
                </tr>
                <tr>
                    <th>Réf.</th><th><i class="fa-solid fa-sort-up"></i> Date facturation</th><th>Date échéance</th><th>Tiers</th><th>Nom alternatif</th><th>Code postal</th><th class="num">Montant HT</th><th class="num">Montant TTC</th><th>Créé par / État</th><th><i class="fa-solid fa-list"></i> <input type="checkbox" id="invoiceCheckAll"></th>
                </tr>
            </thead>
            <tbody>
            <?php if(!$invoices): ?><tr><td colspan="10" class="empty-row">Aucune facture pour le moment</td></tr><?php endif; ?>
            <?php foreach($invoices as $inv): [$ht,$tva,$ttc]=invoice_totals($inv); $status=$inv['status']??'Brouillon'; ?>
                <tr>
                    <td class="quote-ref-cell"><i class="fa-solid fa-file-invoice-dollar text-green"></i> <a class="ref" href="index.php?page=invoice_show&id=<?=(int)$inv['id']?>"><?=e($inv['ref'])?></a> <a class="download-pdf-icon" title="Télécharger PDF" href="<?=csrf_url('index.php?page=invoice_pdf_generate&id='.(int)$inv['id'].'&download=1')?>"><i class="fa-solid fa-download text-gray"></i></a><?php if(!empty($inv['order_id'])): ?><br><small><i class="fa-solid fa-link"></i> Cmd: <a class="ref" href="index.php?page=order_show&id=<?=(int)$inv['order_id']?>"><?=e($inv['order_ref'] ?? ('#'.$inv['order_id']))?></a></small><?php endif; ?><?php if(!empty($inv['expedition_id'])): ?><br><small><i class="fa-solid fa-link"></i> Exp: <a class="ref" href="index.php?page=expedition_show&id=<?=(int)$inv['expedition_id']?>"><?=e($inv['expedition_ref'] ?? ('#'.$inv['expedition_id']))?></a></small><?php endif; ?></td>
                    <td><?=e($inv['invoice_date']??'')?></td>
                    <td><?=e($inv['due_date']??($inv['invoice_date']??''))?></td>
                    <td><i class="fa-solid fa-building text-purple"></i> <?php if(!empty($inv['client_id'])): ?><a class="ref" href="index.php?page=tiers_show&id=<?=(int)$inv['client_id']?>"><?=e($inv['client']?:'—')?></a><?php else: ?><span class="ref"><?=e($inv['client']?:'—')?></span><?php endif; ?></td>
                    <td></td><td></td>
                    <td class="num price"><?=money($ht)?></td>
                    <td class="num price"><?=money($ttc)?></td>
                    <td><i class="fa-solid fa-user-tie author-icon"></i> <span class="ref"><?=e(ge_record_author($inv,'created_by'))?></span> <span class="quote-status <?=invoice_badge_class($status)?>"><?=e(invoice_status_label($status))?></span></td>
                    <td class="check-cell"><input type="checkbox" class="invoice-row-check" name="invoice_ids[]" value="<?=(int)$inv['id']?>"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?=ge_list_pager($invoiceTotal,$invoicePage,$invoicePages,'p',['page'=>'invoices'] + ($status ? ['status'=>$status] : []))?>
</form>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
