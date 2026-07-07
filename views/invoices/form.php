<?php
require_once __DIR__.'/_helpers.php';
$isEdit = (($_GET['page']??'') === 'invoice_edit');
$invoices=data_read('invoices',[]);
$invoice=null; $id=0; $sourceOrder=null; $sourceOrderId=0; $sourceExpedition=null; $sourceExpeditionId=0;
if($isEdit){
    $id=(int)($_GET['id']??0);
    $invoice=find_row_by_id($invoices,$id);
    if(!$invoice) redirect_to('index.php?page=invoices');
    $sourceOrderId=(int)($invoice['order_id'] ?? 0);
    $sourceExpeditionId=(int)($invoice['expedition_id'] ?? 0);
} else {
    $sourceOrderId=(int)($_GET['order_id'] ?? 0);
    $sourceExpeditionId=(int)($_GET['from_expedition'] ?? $_GET['expedition_id'] ?? 0);

    if($sourceExpeditionId>0){
        $sourceExpedition=find_row_by_id(data_read('expeditions',[]), $sourceExpeditionId);
        if($sourceExpedition){
            $sourceOrderId=(int)($sourceExpedition['order_id'] ?? 0);
            $sourceOrder=$sourceOrderId>0 ? find_row_by_id(data_read('orders',[]), $sourceOrderId) : null;
            $invoice=[
                'client_id'=>(int)($sourceOrder['client_id'] ?? $sourceExpedition['tier_id'] ?? 0),
                'client'=>(string)($sourceOrder['client'] ?? $sourceExpedition['tier_name'] ?? ''),
                'client_ref'=>(string)($sourceOrder['client_ref'] ?? $sourceExpedition['tier_ref'] ?? ''),
                'type'=>'Facture standard',
                'invoice_date'=>date('d/m/Y'),
                'due_date'=>date('d/m/Y'),
                'payment_terms'=>(string)($sourceOrder['payment_terms'] ?? ''),
                'payment_mode'=>(string)($sourceOrder['payment_mode'] ?? ''),
                'bank_account'=>'001',
                'template'=>(string)($sourceOrder['template'] ?? 'crabe'),
                'public_note'=>trim((string)($sourceOrder['public_note'] ?? $sourceExpedition['note_public'] ?? '')),
                'private_note'=>'Créée depuis l’expédition '.($sourceExpedition['ref'] ?? ''),
                'lines'=>$sourceOrder ? invoice_lines_from_order($sourceOrder) : invoice_lines_from_expedition($sourceExpedition),
                'expedition_id'=>$sourceExpeditionId,
                'expedition_ref'=>(string)($sourceExpedition['ref'] ?? ''),
                'order_id'=>$sourceOrderId,
                'order_ref'=>(string)($sourceOrder['ref'] ?? ($sourceExpedition['order_ref'] ?? ''))
            ];
        }
    }

    if(!$invoice && $sourceOrderId>0){
        $sourceOrder=find_row_by_id(data_read('orders',[]), $sourceOrderId);
        if($sourceOrder){
            $invoice=[
                'client_id'=>(int)($sourceOrder['client_id'] ?? 0),
                'client'=>(string)($sourceOrder['client'] ?? ''),
                'client_ref'=>(string)($sourceOrder['client_ref'] ?? ''),
                'type'=>'Facture standard',
                'invoice_date'=>date('d/m/Y'),
                'due_date'=>date('d/m/Y'),
                'payment_terms'=>(string)($sourceOrder['payment_terms'] ?? ''),
                'payment_mode'=>(string)($sourceOrder['payment_mode'] ?? ''),
                'bank_account'=>'001',
                'template'=>(string)($sourceOrder['template'] ?? 'crabe'),
                'public_note'=>trim((string)($sourceOrder['public_note'] ?? '')),
                'private_note'=>'Créée depuis la commande '.($sourceOrder['ref'] ?? ''),
                'lines'=>invoice_lines_from_order($sourceOrder),
                'order_id'=>$sourceOrderId,
                'order_ref'=>(string)($sourceOrder['ref'] ?? '')
            ];
        }
    }
}
$title=$isEdit ? 'Modifier facture '.$invoice['ref'] : ($sourceExpedition ? 'Nouvelle facture depuis '.$sourceExpedition['ref'] : ($sourceOrder ? 'Nouvelle facture depuis '.$sourceOrder['ref'] : 'Nouvelle facture'));
include __DIR__.'/../layouts/header.php';
$today = date('d/m/Y');
$terms=['50/50 Fin T','à la commande','À réception','30 jours','30 jours fin de mois','60 jours','60 jours fin de mois','10 jours','10 jours fin de mois'];
$modes=['Carte bancaire','Chèque','Espèce','Ordre de prélèvement','Virement bancaire'];
$banks=['001','Compte bancaire principal','ATTIJARIWAFABANK','BMCE','Banque Populaire'];
$clients=ge_available_clients();
$products=invoice_products_options();
$lines=$isEdit ? (($invoice['lines']??[]) ?: invoice_default_lines()) : (($invoice['lines']??[]) ?: [['description'=>'','tva'=>20,'pu_ht'=>0,'qty'=>1,'unit'=>'u.','cost_price'=>0]]);
$action=$isEdit ? 'index.php?page=invoice_update' : 'index.php?page=invoice_store';
function selected_opt($a,$b){ return (string)$a===(string)$b?'selected':''; }
function checked_opt($a,$b){ return (string)$a===(string)$b?'checked':''; }
?>
<div class="invoice-form-page dol-page">
    <div class="dol-page-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
    <form method="post" action="<?=$action?>" class="devis-form invoice-form">
        <?=csrf_field()?>
        <?php if($isEdit): ?><input type="hidden" name="id" value="<?=$id?>"><?php endif; ?>
        <?php if(!$isEdit && $sourceOrder): ?>
            <input type="hidden" name="source_order_id" value="<?=(int)$sourceOrderId?>">
            <input type="hidden" name="source_order_ref" value="<?=e($sourceOrder['ref'] ?? '')?>">
        <?php endif; ?>
        <?php if(!$isEdit && $sourceExpedition): ?>
            <input type="hidden" name="source_expedition_id" value="<?=(int)$sourceExpeditionId?>">
            <input type="hidden" name="source_expedition_ref" value="<?=e($sourceExpedition['ref'] ?? '')?>">
        <?php endif; ?>
        <?php if(!$isEdit && ($sourceOrder || $sourceExpedition)): ?>
            <div class="invoice-source-order-alert"><i class="fa-solid fa-link"></i>
                Facture créée depuis
                <?php if($sourceExpedition): ?> l’expédition <a href="index.php?page=expedition_show&id=<?=(int)$sourceExpeditionId?>"><?=e($sourceExpedition['ref'] ?? '')?></a><?php endif; ?>
                <?php if($sourceOrder): ?> <?= $sourceExpedition ? ' / ' : '' ?> la commande <a href="index.php?page=order_show&id=<?=(int)$sourceOrderId?>"><?=e($sourceOrder['ref'] ?? '')?></a><?php endif; ?>.
                Les informations client, règlement et lignes ont été copiées automatiquement.
            </div>
        <?php endif; ?>
        <div class="dol-form-grid invoice-new-grid">
            <label>Client</label>
            <?php $selectedClientId=(int)($invoice['client_id']??0); $selectedClientName=(string)($invoice['client']??''); ?>
            <div class="field-with-icons">
                <i class="fa-solid fa-building text-purple"></i>
                <input type="hidden" name="client" id="invoiceClientName" value="<?=e($selectedClientName)?>">
                <select name="client_id" class="dol-select wide" required data-placeholder="Sélectionner ou taper un client" onchange="var o=this.options[this.selectedIndex];document.getElementById('invoiceClientName').value=o?o.getAttribute('data-name')||'':'';">
                    <option value="">Sélectionner un client</option>
                    <?php $clientOptionFound=false; foreach($clients as $c): $cid=(int)($c['id']??0); $cname=(string)($c['name']??''); $cref=(string)($c['ref']??''); $sel=($selectedClientId>0 && $cid===$selectedClientId) || ($selectedClientId<=0 && $selectedClientName!=='' && $selectedClientName===$cname); if($sel) $clientOptionFound=true; ?>
                        <option value="<?=$cid?>" data-name="<?=e($cname)?>" <?=$sel?'selected':''?>><?=e($cname.($cref?' ('.$cref.')':''))?></option>
                    <?php endforeach; ?>
                    <?php if($selectedClientName!=='' && !$clientOptionFound): ?><option value="0" data-name="<?=e($selectedClientName)?>" selected><?=e($selectedClientName)?></option><?php endif; ?>
                </select>
                <a class="tiny-plus" href="index.php?page=client_new">+</a>
            </div>

            <label>Type</label>
            <div class="radio-stack">
                <?php $type=$invoice['type']??'Facture standard'; ?>
                <label><input type="radio" name="type" value="Facture standard" <?=checked_opt($type,'Facture standard')?>> Facture standard <i class="fa-solid fa-circle-info info"></i></label>
                <label><input type="radio" name="type" value="Facture d'acompte" <?=checked_opt($type,"Facture d'acompte")?>> Facture d'acompte <i class="fa-solid fa-circle-info info"></i></label>
                <label><input type="radio" name="type" value="Facture avoir" <?=checked_opt($type,'Facture avoir')?>> Facture avoir <i class="fa-solid fa-circle-info info"></i></label>
                <label><input type="radio" name="type" value="Facture de remplacement" <?=checked_opt($type,'Facture de remplacement')?>> Facture de remplacement <i class="fa-solid fa-circle-info info"></i></label>
                <label><input type="radio" name="type" value="Facture modèle" <?=checked_opt($type,'Facture modèle')?>> Facture modèle <i class="fa-solid fa-circle-info info"></i></label>
            </div>

            <label>Date facturation</label>
            <div class="inline-help"><input class="dol-input small" name="invoice_date" value="<?=e($invoice['invoice_date']??$today)?>"><small>Maintenant</small></div>

            <label>Date limite règlement</label>
            <input class="dol-input small" name="due_date" value="<?=e($invoice['due_date']??($invoice['invoice_date']??$today))?>">

            <label>Conditions de règlement</label>
            <select name="payment_terms" class="dol-select"><?php foreach($terms as $t): ?><option <?=selected_opt($invoice['payment_terms']??'50/50 Fin T',$t)?>><?=e($t)?></option><?php endforeach; ?></select>

            <label>Mode de règlement</label>
            <select name="payment_mode" class="dol-select"><?php foreach($modes as $m): ?><option <?=selected_opt($invoice['payment_mode']??'Chèque',$m)?>><?=e($m)?></option><?php endforeach; ?></select>

            <label>Compte bancaire</label>
            <select name="bank_account" class="dol-select"><?php foreach($banks as $b): ?><option <?=selected_opt($invoice['bank_account']??'001',$b)?>><?=e($b)?></option><?php endforeach; ?></select>

            <label>Modèle de document</label>
            <select name="template" class="dol-select"><option <?=selected_opt($invoice['template']??'crabe','crabe')?>>crabe</option><option <?=selected_opt($invoice['template']??'','azur')?>>azur</option></select>

            <label>Note (publique)</label>
            <textarea name="public_note" class="dol-textarea wide-area"><?=e($invoice['public_note']??'')?></textarea>

            <label>Note (privée)</label>
            <textarea name="private_note" class="dol-textarea wide-area"><?=e($invoice['private_note']??'')?></textarea>
        </div>

        <div class="invoice-lines-editor">
            <div class="section-title"><i class="fa-solid fa-list"></i> Lignes de facture</div>
            <div class="table-responsive">
                <table class="editable-lines" id="invoiceLinesEditor">
                    <thead><tr><th>Produit</th><th>Description</th><th>TVA</th><th>P.U. HT</th><th>Qté</th><th>Unité</th><th>Prix revient</th><th></th></tr></thead>
                    <tbody>
                    <?php foreach($lines as $l): ?>
                    <tr>
                        <td><select name="line_product_id[]" class="line-product-select" data-product-line-select><option value="">Produit libre</option><?php foreach($products as $p): $pid=(int)($p['id']??0); ?><option value="<?=$pid?>" data-ref="<?=e($p['ref']??'')?>" data-label="<?=e($p['label']??'')?>" data-price="<?=e($p['sale_price']??0)?>" data-vat="<?=e($p['vat']??$p['tax_rate']??20)?>" data-cost="<?=e($p['buy_price']??$p['purchase_price']??0)?>" data-unit="<?=e($p['unit']??'u.')?>" <?=((int)($l['product_id']??0)===$pid)?'selected':''?>><?=e(trim(($p['ref']??'').' - '.($p['label']??''),' -'))?></option><?php endforeach;?></select></td>
                        <td><textarea name="line_description[]" required><?=e($l['description']??'')?></textarea></td>
                        <td><input name="line_tva[]" value="<?=e($l['tva']??20)?>"></td>
                        <td><input name="line_pu_ht[]" value="<?=e($l['pu_ht']??0)?>"></td>
                        <td><input name="line_qty[]" value="<?=e($l['qty']??1)?>"></td>
                        <td><input name="line_unit[]" value="<?=e($l['unit']??'u.')?>"></td>
                        <td><input name="line_cost_price[]" value="<?=e($l['cost_price']??0)?>"></td>
                        <td><button type="button" class="small-gray-btn" data-remove-line><i class="fa-solid fa-trash"></i></button></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="small-gray-btn" data-add-invoice-line><i class="fa-solid fa-plus"></i> Ajouter une ligne</button>
        </div>
        <div class="devis-actions"><button class="orange-btn" type="submit"><?= $isEdit?'ENREGISTRER':'CRÉER BROUILLON' ?></button><a class="orange-btn" href="<?= $isEdit?'index.php?page=invoice_show&id='.$id:'index.php?page=invoices' ?>">ANNULER</a></div>
    </form>
</div>
<template id="invoiceLineTemplate"><tr><td><select name="line_product_id[]" class="line-product-select" data-product-line-select><option value="">Produit libre</option><?php foreach($products as $p): $pid=(int)($p['id']??0); ?><option value="<?=$pid?>" data-ref="<?=e($p['ref']??'')?>" data-label="<?=e($p['label']??'')?>" data-price="<?=e($p['sale_price']??0)?>" data-vat="<?=e($p['vat']??$p['tax_rate']??20)?>" data-cost="<?=e($p['buy_price']??$p['purchase_price']??0)?>" data-unit="<?=e($p['unit']??'u.')?>"><?=e(trim(($p['ref']??'').' - '.($p['label']??''),' -'))?></option><?php endforeach;?></select></td><td><textarea name="line_description[]" required></textarea></td><td><input name="line_tva[]" value="20"></td><td><input name="line_pu_ht[]" value="0"></td><td><input name="line_qty[]" value="1"></td><td><input name="line_unit[]" value="u."></td><td><input name="line_cost_price[]" value="0"></td><td><button type="button" class="small-gray-btn" data-remove-line><i class="fa-solid fa-trash"></i></button></td></tr></template>
<?php include __DIR__.'/../layouts/footer.php'; ?>
