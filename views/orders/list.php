<?php
$title='Liste des commandes';
include __DIR__.'/../layouts/header.php';
$orders=data_read('orders',[]);
$status=$_GET['status']??'';
if($status) $orders=array_values(array_filter($orders,fn($o)=>($o['status']??'')===$status));
[$orders,$orderTotal,$orderPage,$orderPages]=ge_list_slice($orders);
$count=$orderTotal;
$expeditions = data_read('expeditions', []);
$invoices = data_read('invoices', []);
function order_has_expedition($orderId, $expeditions){
    foreach($expeditions as $e){
        if((int)($e['order_id'] ?? 0) === (int)$orderId) return true;
    }
    return false;
}

function order_has_invoice($order, $invoices){
    $orderId=(int)($order['id'] ?? 0);
    if(!empty($order['invoice_id']) || !empty($order['invoice_ids']) || (($order['facture'] ?? '') === 'Oui')) return true;
    foreach($invoices as $inv){
        if((int)($inv['order_id'] ?? 0) === $orderId) return true;
    }
    return false;
}

function order_badge_class($status){ return match($status){ 'Brouillon'=>'gray','Validée'=>'green','En cours'=>'green','Livrée'=>'muted','Annulée'=>'red', default=>'green'}; }
?>
<div class="quote-list-page orders-list-page">
<form method="post" action="index.php?page=order_bulk_action" id="orderBulkForm"><?=csrf_field()?>
    <div class="quote-topline">
        <div class="quote-count"><i class="fa-solid fa-file-invoice text-green"></i><span>(<?=$count?>)</span></div>
        <div class="quote-bulkbar" id="quoteBulkBar" aria-hidden="true">
            <select name="bulk_action" class="dol-select action-select" required>
                <option value="">-- Sélectionner l'action --</option>
                <option value="validate">Valider</option>
                <option value="progress">En cours</option>
                <option value="delivered">Livrée</option>
                <option value="cancel">Annulée</option>
                <option value="delete">Supprimer</option>
            </select>
            <button type="submit" class="bulk-confirm">CONFIRMER</button>
        </div>
        <div class="quote-pager">
            <select><option>20</option><option>50</option><option>100</option></select>
            <span><?=e($orderPage)?></span><span>/</span><span><?=e($orderPages)?></span>
            <span class="page-arrow disabled"><i class="fa-solid fa-chevron-right"></i></span>
            <a class="round" href="index.php?page=order_new"><i class="fa-solid fa-plus"></i></a>
        </div>
    </div>

    <div class="quote-filters-panel">
        <div class="q-filter"><i class="fa-solid fa-user"></i><select><option>Tiers ayant pour com...</option></select></div>
        <div class="q-filter"><i class="fa-solid fa-user"></i><select><option>Liés à un contact utilisateu...</option></select></div>
    </div>

    <div class="quote-table-wrap">
        <table class="quote-dol-list order-dol-list">
            <thead>
                <tr class="quote-filters-row">
                    <th><input></th><th><input></th>
                    <th><div class="date-pair"><input placeholder="Du"><input placeholder="au"></div></th>
                    <th><div class="date-pair"><input placeholder="Du"><input placeholder="au"></div></th>
                    <th><input></th><th><input></th><th><select><option></option></select></th><th><select><option></option></select></th><th class="search-cell"><i class="fa-solid fa-magnifying-glass"></i><i class="fa-solid fa-xmark"></i></th>
                </tr>
                <tr>
                    <th><i class="fa-solid fa-sort-up"></i> Réf.</th><th>Tiers</th><th>Date de commande</th><th>Date prévue de liv...</th><th class="num">Montant HT</th><th>Auteur</th><th>Expédiable</th><th>Facturé</th><th>État <i class="fa-solid fa-list"></i> <input type="checkbox" id="quoteCheckAll"></th>
                </tr>
            </thead>
            <tbody>
            <?php if(!$orders): ?><tr><td colspan="9" class="empty-row">Aucune commande pour le moment</td></tr><?php endif; ?>
            <?php foreach($orders as $o): $st=$o['status']??'Brouillon'; $client=$o['client']?:'—'; $author=ge_record_author($o,'author'); $hasExpedition=order_has_expedition((int)$o['id'],$expeditions); $hasInvoice=order_has_invoice($o,$invoices); ?>
                <tr>
                    <td class="quote-ref-cell"><i class="fa-solid fa-file-invoice text-green"></i> <a class="ref" href="index.php?page=order_show&id=<?=(int)$o['id']?>"><?=e($o['ref'])?></a> <a class="download-pdf-icon" title="Télécharger PDF" href="<?=csrf_url('index.php?page=order_pdf_generate&id='.(int)$o['id'].'&download=1')?>"><i class="fa-solid fa-download text-gray"></i></a></td>
                    <td><i class="fa-solid fa-building text-purple"></i> <?php if(!empty($o['client_id'])): ?><a class="ref" href="index.php?page=tiers_show&id=<?=(int)$o['client_id']?>"><?=e($client)?></a><?php else: ?><span class="ref"><?=e($client)?></span><?php endif; ?></td>
                    <td><?=e($o['order_date']??'')?> <i class="fa-solid fa-triangle-exclamation warn"></i></td>
                    <td><?=e(($o['delivery_date']??'') ?: '—')?></td>
                    <td class="num price"><?=money($o['total_ht']??0)?></td>
                    <td><i class="fa-solid fa-user-tie author-icon"></i> <span class="ref"><?=e($author)?></span></td>
                    <td class="center"><i class="fa-solid fa-dolly <?= $hasExpedition ? 'text-green' : 'text-red' ?>" title="<?= $hasExpedition ? 'Expédition créée' : 'Aucune expédition' ?>"></i></td>
                    <td class="center"><span class="quote-status <?= $hasInvoice ? 'green' : 'red' ?>"><?= $hasInvoice ? 'Oui' : 'Non' ?></span></td>
                    <td><span class="quote-status <?=order_badge_class($st)?>"><?=e($st)?></span> <input type="checkbox" class="quote-row-check" name="order_ids[]" value="<?=(int)$o['id']?>"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?=ge_list_pager($orderTotal,$orderPage,$orderPages,'p',['page'=>'orders'] + ($status ? ['status'=>$status] : []))?>
</form>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
