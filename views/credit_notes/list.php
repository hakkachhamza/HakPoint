<?php
require_once __DIR__.'/_helpers.php';
$title='Liste avoirs clients';
$pdo=db(); ge_credit_ensure_tables($pdo);
$per=ge_list_limit(); $page=ge_list_page(); $offset=ge_list_offset();
$total=ge_count_tenant_rows($pdo, 'ge_credit_notes');
$pages=max(1,(int)ceil($total/$per)); if($page>$pages){ $page=$pages; $offset=($page-1)*$per; }
$stmt=$pdo->prepare('SELECT * FROM ge_credit_notes WHERE tenant_id=? ORDER BY id ASC LIMIT '.$per.' OFFSET '.$offset); $stmt->execute([ge_current_tenant_id()]); $rows=$stmt->fetchAll();
include __DIR__.'/../layouts/header.php';
?>
<div class="clean-list-page">
  <div class="clean-list-head"><div class="clean-title"><i class="fa-solid fa-file-circle-minus"></i><span>Liste avoirs clients</span><em>(<?=e($total)?>)</em></div><div class="clean-tools"><span class="clean-page"><?=e($page)?> / <?=e($pages)?></span><a class="clean-add" href="index.php?page=credit_notes"><i class="fa-solid fa-plus"></i></a></div></div>
  <div class="clean-table-box"><table class="clean-table ge-paginated-table"><thead><tr><th>Réf.</th><th>Facture</th><th>Client</th><th>Date</th><th>Statut</th><th>HT</th><th>TTC</th><th>Actions</th></tr></thead><tbody>
    <?php foreach($rows as $r): ?><tr><td><a href="index.php?page=credit_note_show&id=<?=(int)$r['id']?>"><?=e($r['ref'])?></a></td><td><?=e($r['invoice_ref'] ?: $r['invoice_id'])?></td><td><?=e($r['client_name'])?></td><td><?=e($r['credit_date'])?></td><td><span class="badge gray"><?=e($r['status'])?></span></td><td><?=money($r['amount_ht'])?></td><td><?=money($r['amount_ttc'])?></td><td style="white-space:nowrap"><a class="btn small" href="index.php?page=credit_note_show&id=<?=(int)$r['id']?>">Ouvrir</a> <a class="btn small" href="index.php?page=credit_notes&edit=<?=(int)$r['id']?>">Modifier</a> <form method="post" action="index.php?page=credit_note_pdf_generate" style="display:inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><input type="hidden" name="download" value="1"><button class="btn small" type="submit"><i class="fa-solid fa-download"></i> PDF</button></form></td></tr><?php endforeach; if(!$rows): ?><tr><td colspan="8" class="empty-row">Aucun avoir</td></tr><?php endif; ?>
  </tbody></table></div>
  <?=ge_list_pager($total,$page,$pages,'p',['page'=>'credit_notes_list'])?>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
