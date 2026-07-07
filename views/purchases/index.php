<?php
require_once __DIR__.'/_helpers.php';
$title = 'Achats fournisseurs';
$pdo = db();
ge_purchase_ensure_tables($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $type = $_POST['type'] ?? 'order';
        $supplier = trim((string)($_POST['supplier_name'] ?? '')) ?: 'Fournisseur';
        $amountHt = ge_decimal($_POST['amount_ht'] ?? 0);
        $amountTva = ge_decimal($_POST['amount_tva'] ?? 0);
        $amountTtc = ge_decimal($_POST['amount_ttc'] ?? 0);
        if ($amountTtc <= 0) $amountTtc = $amountHt + $amountTva;

        if ($type === 'invoice') {
            $ref = trim((string)($_POST['ref'] ?? '')) ?: 'FF-'.date('ymd-His');
            $stmt = $pdo->prepare('INSERT INTO ge_supplier_invoices(tenant_id,ref,supplier_name,invoice_date,due_date,status,amount_ht,amount_tva,amount_ttc,note,template) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                ge_current_tenant_id(),
                $ref,
                $supplier,
                ge_date_or_null($_POST['date'] ?? '') ?: date('Y-m-d'),
                ge_date_or_null($_POST['due_date'] ?? '') ?: null,
                $_POST['status'] ?? 'À payer',
                $amountHt,
                $amountTva,
                $amountTtc,
                trim((string)($_POST['note'] ?? '')),
                'standard',
            ]);
            $invoiceId = (int)$pdo->lastInsertId();
            if(function_exists('ge_erp_post_supplier_invoice_accounting')) ge_erp_post_supplier_invoice_accounting($pdo, $invoiceId);
            audit_log('supplier_invoice_created','Ref: '.$ref);
        } else {
            $ref = trim((string)($_POST['ref'] ?? '')) ?: 'PO-'.date('ymd-His');
            $stmt = $pdo->prepare('INSERT INTO ge_purchase_orders(tenant_id,ref,supplier_name,order_date,due_date,status,amount_ht,amount_tva,amount_ttc,note,created_by,template) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([
                ge_current_tenant_id(),
                $ref,
                $supplier,
                ge_date_or_null($_POST['date'] ?? '') ?: date('Y-m-d'),
                ge_date_or_null($_POST['due_date'] ?? '') ?: null,
                $_POST['status'] ?? 'Brouillon',
                $amountHt,
                $amountTva,
                $amountTtc,
                trim((string)($_POST['note'] ?? '')),
                (int)(current_user()['id'] ?? 0) ?: null,
                'standard',
            ]);
            $orderId = (int)$pdo->lastInsertId();
            ge_purchase_sync_order_lines_from_post($pdo, $orderId);
            if(($_POST['status'] ?? '') === 'Reçue' && function_exists('ge_erp_apply_purchase_receipt')) {
                ge_erp_apply_purchase_receipt($pdo, $orderId);
            }
            $fresh = ge_purchase_order_row($pdo, $orderId) ?: [];
            if(function_exists('ge_erp_request_approval')) ge_erp_request_approval($pdo, 'achat', $orderId, $ref, (float)($fresh['amount_ttc'] ?? $amountTtc), 'Achat fournisseur '.$ref);
            audit_log('purchase_order_created','Ref: '.$ref);
        }
        redirect_to('index.php?page=purchases&ok=created');
    }

    if ($action === 'update_order') {
        $id = (int)($_POST['id'] ?? 0);
        $old = ge_purchase_order_row($pdo, $id);
        if ($old) {
            $receiptApplied = !empty($old['receipt_applied_at']);
            $postedStatus = $_POST['status'] ?? 'Brouillon';

            $amountHt = ge_decimal($_POST['amount_ht'] ?? 0);
            $amountTva = ge_decimal($_POST['amount_tva'] ?? 0);
            $amountTtc = ge_decimal($_POST['amount_ttc'] ?? 0);
            if ($amountTtc <= 0) $amountTtc = $amountHt + $amountTva;

            // If the receipt has already been applied to stock, do not silently change lines/amounts
            // or send the order back to an earlier status. That would make stock inconsistent.
            if ($receiptApplied) {
                $amountHt = ge_decimal($old['amount_ht'] ?? 0);
                $amountTva = ge_decimal($old['amount_tva'] ?? 0);
                $amountTtc = ge_decimal($old['amount_ttc'] ?? ($amountHt + $amountTva));
                if (in_array($postedStatus, ['Brouillon','Validée','Commandée','Annulée'], true)) {
                    $postedStatus = (string)($old['status'] ?? 'Reçue');
                }
            }

            $stmt = $pdo->prepare('UPDATE ge_purchase_orders SET supplier_name=?, order_date=?, due_date=?, status=?, amount_ht=?, amount_tva=?, amount_ttc=?, note=? WHERE id=? AND tenant_id=?');
            $stmt->execute([
                trim((string)($_POST['supplier_name'] ?? '')) ?: 'Fournisseur',
                ge_date_or_null($_POST['order_date'] ?? '') ?: null,
                ge_date_or_null($_POST['due_date'] ?? '') ?: null,
                $postedStatus,
                $amountHt,
                $amountTva,
                $amountTtc,
                trim((string)($_POST['note'] ?? '')),
                $id,
                ge_current_tenant_id(),
            ]);

            if (!$receiptApplied) {
                ge_purchase_sync_order_lines_from_post($pdo, $id);
            }

            $fresh = ge_purchase_order_row($pdo, $id) ?: [];
            if(function_exists('ge_erp_request_approval')) ge_erp_request_approval($pdo, 'achat', $id, (string)($fresh['ref'] ?? $old['ref'] ?? '#'.$id), (float)($fresh['amount_ttc'] ?? $amountTtc), 'Achat fournisseur '.($fresh['ref'] ?? $old['ref'] ?? '#'.$id));
            if(function_exists('ge_erp_apply_purchase_receipt') && $postedStatus === 'Reçue') ge_erp_apply_purchase_receipt($pdo, $id);
            audit_log('purchase_order_updated','Ref: '.($old['ref'] ?? '').' id: '.$id);
        }
        redirect_to('index.php?page=purchases&ok=updated');
    }

    if ($action === 'update_invoice') {
        $id = (int)($_POST['id'] ?? 0);
        $old = ge_supplier_invoice_row($pdo, $id);
        if ($old) {
            $amountHt = ge_decimal($_POST['amount_ht'] ?? 0);
            $amountTva = ge_decimal($_POST['amount_tva'] ?? 0);
            $amountTtc = ge_decimal($_POST['amount_ttc'] ?? 0);
            if ($amountTtc <= 0) $amountTtc = $amountHt + $amountTva;
            $paidAt = ($_POST['status'] ?? '') === 'Payée' ? (ge_date_or_null($_POST['paid_at'] ?? '') ?: date('Y-m-d')) : null;
            $stmt = $pdo->prepare('UPDATE ge_supplier_invoices SET supplier_name=?, invoice_date=?, due_date=?, status=?, amount_ht=?, amount_tva=?, amount_ttc=?, note=?, paid_at=? WHERE id=? AND tenant_id=?');
            $stmt->execute([
                trim((string)($_POST['supplier_name'] ?? '')) ?: 'Fournisseur',
                ge_date_or_null($_POST['invoice_date'] ?? '') ?: null,
                ge_date_or_null($_POST['due_date'] ?? '') ?: null,
                $_POST['status'] ?? 'À payer',
                $amountHt,
                $amountTva,
                $amountTtc,
                trim((string)($_POST['note'] ?? '')),
                $paidAt,
                $id,
                ge_current_tenant_id(),
            ]);
            if(function_exists('ge_erp_post_supplier_invoice_accounting')) ge_erp_post_supplier_invoice_accounting($pdo, $id);
            audit_log('supplier_invoice_updated','Ref: '.($old['ref'] ?? '').' id: '.$id);
        }
        redirect_to('index.php?page=supplier_invoices&ok=updated');
    }

    if ($action === 'delete_order') {
        $id = (int)($_POST['id'] ?? 0);
        $old = ge_purchase_order_row($pdo, $id);
        if ($old) {
            if (!empty($old['receipt_applied_at'])) {
                audit_log('purchase_order_delete_blocked','Stock already applied for order id: '.$id);
                redirect_to('index.php?page=purchases&error=stock_applied');
            }
            $pdo->prepare('DELETE FROM ge_purchase_order_lines WHERE purchase_order_id=? AND tenant_id=?')->execute([$id, ge_current_tenant_id()]);
            $stmt = $pdo->prepare('DELETE FROM ge_purchase_orders WHERE id=? AND tenant_id=?');
            $stmt->execute([$id, ge_current_tenant_id()]);
            audit_log('purchase_order_deleted','Ref: '.($old['ref'] ?? '').' id: '.$id);
        }
        redirect_to('index.php?page=purchases&ok=deleted');
    }

    if ($action === 'delete_invoice') {
        $id = (int)($_POST['id'] ?? 0);
        $old = ge_supplier_invoice_row($pdo, $id);
        if ($old) {
            $stmt = $pdo->prepare('DELETE FROM ge_supplier_invoices WHERE id=? AND tenant_id=?');
            $stmt->execute([$id, ge_current_tenant_id()]);
            audit_log('supplier_invoice_deleted','Ref: '.($old['ref'] ?? '').' id: '.$id);
        }
        redirect_to('index.php?page=supplier_invoices&ok=deleted');
    }
}

$editOrder = isset($_GET['edit_order']) ? ge_purchase_order_row($pdo, (int)$_GET['edit_order']) : null;
$editInvoice = isset($_GET['edit_invoice']) ? ge_supplier_invoice_row($pdo, (int)$_GET['edit_invoice']) : null;
$orders = ge_fetch_tenant_rows($pdo, 'ge_purchase_orders', 'id ASC', 20);
$invoices = []; // Factures fournisseurs now have their own section: index.php?page=supplier_invoices
$products = data_read('products', []);
$warehouses = data_read('warehouses', []);
$editOrderLines = $editOrder ? ge_purchase_lines($pdo, 'order', (int)$editOrder['id']) : [];
if(!$editOrderLines) $editOrderLines = [['product_id'=>'','product_label'=>'','warehouse_id'=>'','qty'=>1,'unit'=>'u.','pu_ht'=>0,'tva_rate'=>20]];
include __DIR__.'/../layouts/header.php';
?>
<div class="clean-list-page">
  <?php if(isset($_GET['ok'])): ?><div class="email-status ok">Opération enregistrée.</div><?php endif; ?>
  <?php if(($_GET['error'] ?? '') === 'stock_applied'): ?><div class="email-status err">Impossible de supprimer ou modifier les lignes : le stock est déjà appliqué pour ce bon.</div><?php endif; ?>
  <div class="clean-list-head"><div class="clean-title"><i class="fa-solid fa-cart-shopping"></i><span>Achats fournisseurs</span></div></div>

  <div class="panel" style="margin-bottom:14px">
    <form method="post" class="settings-form"><?=csrf_field()?>
      <input type="hidden" name="action" value="create">
      <div class="settings-two-cols">
        <div><label>Type</label><select name="type"><option value="order">Bon de commande fournisseur</option><option value="invoice">Facture fournisseur</option></select></div>
        <div><label>Référence</label><input name="ref" placeholder="Auto si vide"></div>
        <div><label>Fournisseur</label><input name="supplier_name" required placeholder="Nom fournisseur"></div>
        <div><label>Date</label><input type="date" name="date" value="<?=date('Y-m-d')?>"></div>
        <div><label>Échéance</label><input type="date" name="due_date"></div>
        <div><label>Statut</label><select name="status"><option>Brouillon</option><option>Validée</option><option>Commandée</option><option>À payer</option><option>Payée</option><option>Annulée</option></select></div>
        <div><label>Montant HT</label><input name="amount_ht" inputmode="decimal" value="0"></div>
        <div><label>TVA</label><input name="amount_tva" inputmode="decimal" value="0"></div>
        <div><label>Montant TTC</label><input name="amount_ttc" inputmode="decimal" value="0" placeholder="Auto avec lignes"></div>
      </div>
      <div class="purchase-lines-wrap" style="margin:12px 0"><table class="clean-table purchase-lines-table"><thead><tr><th>Produit</th><th>Description</th><th>Entrepôt</th><th>Qté</th><th>P.U. HT</th><th>TVA</th></tr></thead><tbody>
        <?php for($i=0;$i<3;$i++): ?><tr>
          <td><?=ge_purchase_product_select($products)?></td>
          <td><input name="line_label[]" placeholder="Article / service"></td>
          <td><?=ge_purchase_warehouse_select($warehouses)?></td>
          <td><input name="line_qty[]" value="<?= $i===0 ? '1' : '' ?>" inputmode="decimal"></td>
          <td><input name="line_pu_ht[]" value="<?= $i===0 ? '0' : '' ?>" inputmode="decimal"></td>
          <td><input name="line_tva[]" value="20" inputmode="decimal"><input type="hidden" name="line_unit[]" value="u."></td>
        </tr><?php endfor; ?>
      </tbody></table></div>
      <label>Note</label><textarea name="note" rows="2"></textarea>
      <button class="btn primary" type="submit"><i class="fa-solid fa-plus"></i> Ajouter</button>
    </form>
  </div>

  <?php if($editOrder): ?>
    <div class="panel" style="margin-bottom:14px">
      <form method="post" class="settings-form"><?=csrf_field()?>
        <input type="hidden" name="action" value="update_order"><input type="hidden" name="id" value="<?=(int)$editOrder['id']?>">
        <h3 style="margin-top:0">Modifier bon de commande <?=e($editOrder['ref'])?></h3>
        <div class="settings-two-cols">
          <div><label>Fournisseur</label><input name="supplier_name" value="<?=e($editOrder['supplier_name'])?>" required></div>
          <div><label>Date</label><input type="date" name="order_date" value="<?=e(ge_purchase_date($editOrder['order_date']))?>"></div>
          <div><label>Échéance</label><input type="date" name="due_date" value="<?=e(ge_purchase_date($editOrder['due_date'] ?? ''))?>"></div>
          <div><label>Statut</label><select name="status"><?php foreach(ge_purchase_status_options('order') as $s): ?><option<?=ge_purchase_selected($editOrder['status'],$s)?>><?=e($s)?></option><?php endforeach; ?></select></div>
          <div><label>Montant HT</label><input name="amount_ht" inputmode="decimal" value="<?=e($editOrder['amount_ht'])?>"></div>
          <div><label>TVA</label><input name="amount_tva" inputmode="decimal" value="<?=e($editOrder['amount_tva'] ?? 0)?>"></div>
          <div><label>Montant TTC</label><input name="amount_ttc" inputmode="decimal" value="<?=e($editOrder['amount_ttc'] ?? 0)?>"></div>
        </div>
        <div class="purchase-lines-wrap" style="margin:12px 0"><table class="clean-table purchase-lines-table"><thead><tr><th>Produit</th><th>Description</th><th>Entrepôt</th><th>Qté</th><th>P.U. HT</th><th>TVA</th></tr></thead><tbody>
          <?php foreach($editOrderLines as $line): ?><tr>
            <td><?=ge_purchase_product_select($products, $line['product_id'] ?? '')?></td>
            <td><input name="line_label[]" value="<?=e($line['product_label'] ?? '')?>"></td>
            <td><?=ge_purchase_warehouse_select($warehouses, $line['warehouse_id'] ?? '')?></td>
            <td><input name="line_qty[]" value="<?=e($line['qty'] ?? 1)?>" inputmode="decimal"></td>
            <td><input name="line_pu_ht[]" value="<?=e($line['pu_ht'] ?? 0)?>" inputmode="decimal"></td>
            <td><input name="line_tva[]" value="<?=e($line['tva_rate'] ?? 20)?>" inputmode="decimal"><input type="hidden" name="line_unit[]" value="<?=e($line['unit'] ?? 'u.')?>"></td>
          </tr><?php endforeach; ?>
          <tr><td><?=ge_purchase_product_select($products)?></td><td><input name="line_label[]" placeholder="Ajouter ligne"></td><td><?=ge_purchase_warehouse_select($warehouses)?></td><td><input name="line_qty[]" inputmode="decimal"></td><td><input name="line_pu_ht[]" inputmode="decimal"></td><td><input name="line_tva[]" value="20" inputmode="decimal"><input type="hidden" name="line_unit[]" value="u."></td></tr>
        </tbody></table></div>
        <label>Note</label><textarea name="note" rows="2"><?=e($editOrder['note'])?></textarea>
        <button class="btn primary" type="submit">Enregistrer</button>
        <a class="btn" href="index.php?page=purchases">Annuler</a>
      </form>
    </div>
  <?php endif; ?>

  <?php if($editInvoice): ?>
    <div class="panel" style="margin-bottom:14px">
      <form method="post" class="settings-form"><?=csrf_field()?>
        <input type="hidden" name="action" value="update_invoice"><input type="hidden" name="id" value="<?=(int)$editInvoice['id']?>">
        <h3 style="margin-top:0">Modifier facture fournisseur <?=e($editInvoice['ref'])?></h3>
        <div class="settings-two-cols">
          <div><label>Fournisseur</label><input name="supplier_name" value="<?=e($editInvoice['supplier_name'])?>" required></div>
          <div><label>Date</label><input type="date" name="invoice_date" value="<?=e(ge_purchase_date($editInvoice['invoice_date']))?>"></div>
          <div><label>Échéance</label><input type="date" name="due_date" value="<?=e(ge_purchase_date($editInvoice['due_date']))?>"></div>
          <div><label>Statut</label><select name="status"><?php foreach(ge_purchase_status_options('invoice') as $s): ?><option<?=ge_purchase_selected($editInvoice['status'],$s)?>><?=e($s)?></option><?php endforeach; ?></select></div>
          <div><label>Montant HT</label><input name="amount_ht" inputmode="decimal" value="<?=e($editInvoice['amount_ht'])?>"></div>
          <div><label>TVA</label><input name="amount_tva" inputmode="decimal" value="<?=e($editInvoice['amount_tva'] ?? 0)?>"></div>
          <div><label>Montant TTC</label><input name="amount_ttc" inputmode="decimal" value="<?=e($editInvoice['amount_ttc'])?>"></div>
          <div><label>Payée le</label><input type="date" name="paid_at" value="<?=e(ge_purchase_date($editInvoice['paid_at'] ?? ''))?>"></div>
        </div>
        <label>Note</label><textarea name="note" rows="2"><?=e($editInvoice['note'] ?? '')?></textarea>
        <button class="btn primary" type="submit">Enregistrer</button>
        <a class="btn" href="index.php?page=purchases">Annuler</a>
      </form>
    </div>
  <?php endif; ?>

  <div class="clean-table-box">
    <table class="clean-table">
      <thead><tr><th colspan="9">Bons de commande fournisseur <a class="btn small" style="float:right" href="index.php?page=purchase_orders">Voir liste complète</a></th></tr><tr><th>Réf.</th><th>Fournisseur</th><th>Date</th><th>Statut</th><th>HT</th><th>TTC</th><th>Facture</th><th>Créé</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($orders as $r): ?>
        <tr>
          <td><a href="index.php?page=purchase_order_show&id=<?=(int)$r['id']?>"><?=e($r['ref'])?></a></td><td><?=e($r['supplier_name'])?></td><td><?=e($r['order_date'])?></td><td><span class="badge gray"><?=e($r['status'])?></span></td><td><?=money($r['amount_ht'])?></td><td><?=money($r['amount_ttc'] ?? $r['amount_ht'])?></td>
          <td><?php if(!empty($r['supplier_invoice_id'])): ?><a href="index.php?page=supplier_invoice_show&id=<?=(int)$r['supplier_invoice_id']?>"><?=e($r['supplier_invoice_ref'])?></a><?php else: ?><span class="muted-info">Non</span><?php endif; ?></td><td><?=e($r['created_at'])?></td>
          <td style="white-space:nowrap">
            <a class="btn small" href="index.php?page=purchase_order_show&id=<?=(int)$r['id']?>">Ouvrir</a>
            <a class="btn small" href="index.php?page=purchases&edit_order=<?=(int)$r['id']?>">Modifier</a>
            <a class="btn small" href="<?=csrf_url('index.php?page=purchase_pdf_generate&type=order&id='.(int)$r['id'])?>">PDF</a>
            <?php if(empty($r['supplier_invoice_id'])): ?><a class="btn small" href="<?=csrf_url('index.php?page=purchase_make_invoice&id='.(int)$r['id'])?>">Créer facture</a><?php endif; ?>
            <form method="post" style="display:inline" onsubmit="return confirm('Supprimer ce bon de commande fournisseur ?')"><?=csrf_field()?><input type="hidden" name="action" value="delete_order"><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button class="btn small danger" type="submit">Supprimer</button></form>
          </td>
        </tr>
      <?php endforeach; if(!$orders): ?><tr><td colspan="9" class="empty-row">Aucun bon de commande fournisseur</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="panel" style="margin-top:14px"><a class="btn" href="index.php?page=supplier_invoices"><i class="fa-solid fa-file-invoice-dollar"></i> Ouvrir la section Factures fournisseurs</a></div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
