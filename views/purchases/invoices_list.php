<?php
require_once __DIR__.'/_helpers.php';
$title='Factures fournisseurs';
$pdo=db(); ge_purchase_ensure_tables($pdo);
if($_SERVER['REQUEST_METHOD']==='POST'){
  require_csrf();
  $action=$_POST['action'] ?? '';
  if($action==='update_invoice'){
    $id=(int)($_POST['id'] ?? 0); $old=ge_supplier_invoice_row($pdo,$id);
    if($old){
      $amountHt=ge_decimal($_POST['amount_ht'] ?? 0); $amountTva=ge_decimal($_POST['amount_tva'] ?? 0); $amountTtc=ge_decimal($_POST['amount_ttc'] ?? 0); if($amountTtc<=0)$amountTtc=$amountHt+$amountTva;
      $paidAt=($_POST['status'] ?? '')==='Payée' ? (ge_date_or_null($_POST['paid_at'] ?? '') ?: date('Y-m-d')) : null;
      $stmt=$pdo->prepare('UPDATE ge_supplier_invoices SET supplier_name=?, invoice_date=?, due_date=?, status=?, amount_ht=?, amount_tva=?, amount_ttc=?, note=?, paid_at=? WHERE id=? AND tenant_id=?');
      $stmt->execute([trim((string)($_POST['supplier_name'] ?? '')) ?: 'Fournisseur', ge_date_or_null($_POST['invoice_date'] ?? '') ?: null, ge_date_or_null($_POST['due_date'] ?? '') ?: null, $_POST['status'] ?? 'À payer', $amountHt,$amountTva,$amountTtc,trim((string)($_POST['note'] ?? '')),$paidAt,$id,ge_current_tenant_id()]);
      if(function_exists('ge_erp_post_supplier_invoice_accounting')) ge_erp_post_supplier_invoice_accounting($pdo,$id);
      audit_log('supplier_invoice_updated','Ref: '.($old['ref'] ?? '').' id: '.$id);
    }
    redirect_to('index.php?page=supplier_invoices&ok=updated');
  }
  if($action==='delete_invoice'){
    $id=(int)($_POST['id'] ?? 0); $old=ge_supplier_invoice_row($pdo,$id);
    if($old){ $pdo->prepare('DELETE FROM ge_supplier_invoices WHERE id=? AND tenant_id=?')->execute([$id, ge_current_tenant_id()]); audit_log('supplier_invoice_deleted','Ref: '.($old['ref'] ?? '').' id: '.$id); }
    redirect_to('index.php?page=supplier_invoices&ok=deleted');
  }
}
$editInvoice=isset($_GET['edit_invoice']) ? ge_supplier_invoice_row($pdo,(int)$_GET['edit_invoice']) : null;
$per=ge_list_limit(); $page=ge_list_page(); $offset=ge_list_offset();
$total=ge_count_tenant_rows($pdo, 'ge_supplier_invoices');
$pages=max(1,(int)ceil($total/$per)); if($page>$pages){ $page=$pages; $offset=($page-1)*$per; }
$stmt=$pdo->prepare('SELECT * FROM ge_supplier_invoices WHERE tenant_id=? ORDER BY id ASC LIMIT '.$per.' OFFSET '.$offset); $stmt->execute([ge_current_tenant_id()]); $invoices=$stmt->fetchAll();
include __DIR__.'/../layouts/header.php';
?>
<div class="clean-list-page">
  <?php if(isset($_GET['ok'])): ?><div class="email-status ok">Opération enregistrée.</div><?php endif; ?>
  <div class="clean-list-head"><div class="clean-title"><i class="fa-solid fa-file-invoice-dollar"></i><span>Factures fournisseurs</span><em>(<?=e($total)?>)</em></div><div class="clean-tools"><span class="clean-page"><?=e($page)?> / <?=e($pages)?></span></div></div>
  <?php if($editInvoice): ?>
    <div class="panel" style="margin-bottom:14px"><form method="post" class="settings-form"><?=csrf_field()?><input type="hidden" name="action" value="update_invoice"><input type="hidden" name="id" value="<?=(int)$editInvoice['id']?>"><h3 style="margin-top:0">Modifier facture fournisseur <?=e($editInvoice['ref'])?></h3><div class="settings-two-cols"><div><label>Fournisseur</label><input name="supplier_name" value="<?=e($editInvoice['supplier_name'])?>" required></div><div><label>Date</label><input type="date" name="invoice_date" value="<?=e(ge_purchase_date($editInvoice['invoice_date']))?>"></div><div><label>Échéance</label><input type="date" name="due_date" value="<?=e(ge_purchase_date($editInvoice['due_date']))?>"></div><div><label>Statut</label><select name="status"><?php foreach(ge_purchase_status_options('invoice') as $s): ?><option<?=ge_purchase_selected($editInvoice['status'],$s)?>><?=e($s)?></option><?php endforeach; ?></select></div><div><label>Montant HT</label><input name="amount_ht" inputmode="decimal" value="<?=e($editInvoice['amount_ht'])?>"></div><div><label>TVA</label><input name="amount_tva" inputmode="decimal" value="<?=e($editInvoice['amount_tva'] ?? 0)?>"></div><div><label>Montant TTC</label><input name="amount_ttc" inputmode="decimal" value="<?=e($editInvoice['amount_ttc'])?>"></div><div><label>Payée le</label><input type="date" name="paid_at" value="<?=e(ge_purchase_date($editInvoice['paid_at'] ?? ''))?>"></div></div><label>Note</label><textarea name="note" rows="2"><?=e($editInvoice['note'] ?? '')?></textarea><button class="btn primary" type="submit">Enregistrer</button><a class="btn" href="index.php?page=supplier_invoices">Annuler</a></form></div>
  <?php endif; ?>
  <div class="clean-table-box"><table class="clean-table ge-paginated-table"><thead><tr><th>Réf.</th><th>Fournisseur</th><th>Date</th><th>Échéance</th><th>Statut</th><th>HT</th><th>TTC</th><th>Commande</th><th>Actions</th></tr></thead><tbody>
  <?php foreach($invoices as $r): ?><tr><td><a href="index.php?page=supplier_invoice_show&id=<?=(int)$r['id']?>"><?=e($r['ref'])?></a></td><td><?=e($r['supplier_name'])?></td><td><?=e($r['invoice_date'])?></td><td><?=e($r['due_date'])?></td><td><span class="badge gray"><?=e($r['status'])?></span></td><td><?=money($r['amount_ht'])?></td><td><?=money($r['amount_ttc'])?></td><td><?php if(!empty($r['purchase_order_id'])): ?><a href="index.php?page=purchase_order_show&id=<?=(int)$r['purchase_order_id']?>"><?=e($r['purchase_order_ref'])?></a><?php else: ?><span class="muted-info">-</span><?php endif; ?></td><td style="white-space:nowrap"><a class="btn small" href="index.php?page=supplier_invoice_show&id=<?=(int)$r['id']?>">Ouvrir</a> <a class="btn small" href="index.php?page=supplier_invoices&edit_invoice=<?=(int)$r['id']?>">Modifier</a> <a class="btn small" href="<?=csrf_url('index.php?page=purchase_pdf_generate&type=invoice&id='.(int)$r['id'].'&download=1')?>"><i class="fa-solid fa-download"></i> PDF</a> <form method="post" style="display:inline" onsubmit="return confirm('Supprimer cette facture fournisseur ?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_invoice"><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button class="btn small danger" type="submit">Supprimer</button></form></td></tr><?php endforeach; if(!$invoices): ?><tr><td colspan="9" class="empty-row">Aucune facture fournisseur</td></tr><?php endif; ?>
  </tbody></table></div>
  <?=ge_list_pager($total,$page,$pages,'p',['page'=>'supplier_invoices'])?>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
