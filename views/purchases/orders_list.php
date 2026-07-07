<?php
require_once __DIR__.'/_helpers.php';
$title='Bons de commande fournisseur';
$pdo=db(); ge_purchase_ensure_tables($pdo);
$per=ge_list_limit(); $page=ge_list_page(); $offset=ge_list_offset();
$total=ge_count_tenant_rows($pdo, 'ge_purchase_orders');
$pages=max(1,(int)ceil($total/$per)); if($page>$pages){ $page=$pages; $offset=($page-1)*$per; }
$stmt=$pdo->prepare('SELECT * FROM ge_purchase_orders WHERE tenant_id=? ORDER BY id ASC LIMIT '.$per.' OFFSET '.$offset); $stmt->execute([ge_current_tenant_id()]); $orders=$stmt->fetchAll();
include __DIR__.'/../layouts/header.php';
?>
<div class="clean-list-page">
  <div class="clean-list-head">
    <div class="clean-title"><i class="fa-solid fa-file-signature"></i><span>Bons de commande fournisseur</span><em>(<?=e($total)?>)</em></div>
    <div class="clean-tools"><span class="clean-page"><?=e($page)?> / <?=e($pages)?></span><a class="clean-add" href="index.php?page=purchases"><i class="fa-solid fa-plus"></i></a></div>
  </div>
  <div class="clean-table-box"><table class="clean-table ge-paginated-table">
    <thead><tr><th>Réf.</th><th>Fournisseur</th><th>Date</th><th>Statut</th><th>HT</th><th>TTC</th><th>Facture</th><th>Créé</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($orders as $r): ?>
      <tr>
        <td><a href="index.php?page=purchase_order_show&id=<?=(int)$r['id']?>"><?=e($r['ref'])?></a></td>
        <td><?=e($r['supplier_name'])?></td>
        <td><?=e($r['order_date'])?></td>
        <td><span class="badge gray"><?=e($r['status'])?></span></td>
        <td><?=money($r['amount_ht'])?></td>
        <td><?=money($r['amount_ttc'] ?? $r['amount_ht'])?></td>
        <td><?php if(!empty($r['supplier_invoice_id'])): ?><a href="index.php?page=supplier_invoice_show&id=<?=(int)$r['supplier_invoice_id']?>"><?=e($r['supplier_invoice_ref'])?></a><?php else: ?><span class="muted-info">Non</span><?php endif; ?></td>
        <td><?=e($r['created_at'])?></td>
        <td style="white-space:nowrap"><a class="btn small" href="index.php?page=purchase_order_show&id=<?=(int)$r['id']?>">Ouvrir</a> <a class="btn small" href="index.php?page=purchases&edit_order=<?=(int)$r['id']?>">Modifier</a> <a class="btn small" href="<?=csrf_url('index.php?page=purchase_pdf_generate&type=order&id='.(int)$r['id'].'&download=1')?>"><i class="fa-solid fa-download"></i> PDF</a></td>
      </tr>
    <?php endforeach; if(!$orders): ?><tr><td colspan="9" class="empty-row">Aucun bon de commande fournisseur</td></tr><?php endif; ?>
    </tbody>
  </table></div>
  <?=ge_list_pager($total,$page,$pages,'p',['page'=>'purchase_orders'])?>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
