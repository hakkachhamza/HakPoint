<?php
$title='Liste des devis';
include __DIR__.'/../layouts/header.php';
$quotes=data_read('quotes',[]);
$status=$_GET['status']??'';
if($status) $quotes=array_values(array_filter($quotes,fn($q)=>($q['status']??'')===$status));
[$quotes,$quoteTotal,$quotePage,$quotePages]=ge_list_slice($quotes);
$count=$quoteTotal;
function quote_badge_class($status){
    return match($status){
        'Brouillon'=>'gray','Ouvert'=>'gold','Signée (à facturer)'=>'green','Signée'=>'green','Non signée (fermée)'=>'red','Facturée'=>'muted',default=>'gold'
    };
}
?>
<div class="quote-list-page">
<form method="post" action="index.php?page=quote_bulk_action" id="quoteBulkForm"><?=csrf_field()?>
    <div class="quote-topline">
        <div class="quote-count"><i class="fa-solid fa-file-pen text-green"></i><span>(<?=$count?>)</span></div>
        <div class="quote-bulkbar" id="quoteBulkBar" aria-hidden="true">
            <select name="bulk_action" class="dol-select action-select" required>
                <option value="">-- Sélectionner l'action --</option>
                <option value="regenerate_pdf">Régénérer le PDF</option>
                <option value="send_email">Envoyer email</option>
                <option value="validate">Valider</option>
                <option value="sign">Signer</option>
                <option value="refuse">Refuser</option>
                <option value="invoice">Classer facturé</option>
                <option value="delete">Supprimer</option>
            </select>
            <button type="submit" class="bulk-confirm">CONFIRMER</button>
        </div>
        <div class="quote-pager">
            <select><option>20</option><option>50</option><option>100</option></select>
            <span><?=e($quotePage)?></span><span>/</span><span><?=e($quotePages)?></span>
            <span class="page-arrow disabled"><i class="fa-solid fa-chevron-right"></i></span>
            <a class="round" href="index.php?page=quote_new"><i class="fa-solid fa-plus"></i></a>
        </div>
    </div>

    <div class="quote-filters-panel">
        <div class="q-filter"><i class="fa-solid fa-user"></i><select><option>Tiers ayant pour com...</option></select></div>
        <div class="q-filter"><i class="fa-solid fa-user"></i><select><option>Liés à un contact utilisateu...</option></select></div>
    </div>

    <div class="quote-table-wrap">
        <table class="quote-dol-list">
            <thead>
                <tr class="quote-filters-row">
                    <th><input></th>
                    <th><input></th>
                    <th><div class="date-pair"><input placeholder="Du"><input placeholder="au"></div></th>
                    <th><div class="date-pair"><input placeholder="Du"><input placeholder="au"></div></th>
                    <th><input></th>
                    <th><input></th>
                    <th><select><option></option><option>Brouillon</option><option>Ouvert</option><option>Signée</option><option>Facturée</option></select></th>
                    <th class="search-cell"><i class="fa-solid fa-magnifying-glass"></i><i class="fa-solid fa-xmark"></i></th>
                </tr>
                <tr>
                    <th><i class="fa-solid fa-sort-up"></i> Réf.</th>
                    <th>Tiers</th>
                    <th>Date de proposition</th>
                    <th>Date fin</th>
                    <th class="num">Montant HT</th>
                    <th>Auteur</th>
                    <th>État</th>
                    <th><i class="fa-solid fa-list"></i> <input type="checkbox" id="quoteCheckAll"></th>
                </tr>
            </thead>
            <tbody>
            <?php if(!$quotes): ?>
                <tr><td colspan="8" class="empty-row">Aucun devis pour le moment</td></tr>
            <?php endif; ?>
            <?php foreach($quotes as $q):
                $status = $q['status'] ?? 'Brouillon';
                $date = $q['proposal_date'] ?? ($q['created_at'] ?? '');
                $dateEnd = $q['end_date'] ?? ($q['validity_end'] ?? '');
                $client = $q['client'] ?: '—';
                $total = $q['total_ht'] ?? 0;
                $author = ge_record_author($q,'author');
            ?>
                <tr>
                    <td class="quote-ref-cell"><i class="fa-solid fa-file-pen text-green"></i> <a class="ref" href="index.php?page=quote_show&id=<?=(int)$q['id']?>">Devis <?=e($q['ref'])?></a> <a class="download-pdf-icon" title="Télécharger PDF" href="<?=csrf_url('index.php?page=quote_pdf_generate&id='.(int)$q['id'].'&download=1')?>"><i class="fa-solid fa-download text-gray"></i></a></td>
                    <td><i class="fa-solid fa-building text-purple"></i> <?php if(!empty($q['client_id'])): ?><a class="ref" href="index.php?page=tiers_show&id=<?=(int)$q['client_id']?>"><?=e($client)?></a><?php else: ?><span class="ref"><?=e($client)?></span><?php endif; ?></td>
                    <td><?=e($date)?></td>
                    <td><?=e($dateEnd ?: '—')?></td>
                    <td class="num price"><?=money($total)?></td>
                    <td><i class="fa-solid fa-user-tie author-icon"></i> <span class="ref"><?=e($author)?></span></td>
                    <td><span class="quote-status <?=quote_badge_class($status)?>"><?=e($status==='Signée (à facturer)'?'Signée':$status)?></span></td>
                    <td class="check-cell"><input type="checkbox" class="quote-row-check" name="quote_ids[]" value="<?=(int)$q['id']?>"></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?=ge_list_pager($quoteTotal,$quotePage,$quotePages,'p',['page'=>'quotes'] + ($status ? ['status'=>$status] : []))?>
</form>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
