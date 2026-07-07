<?php
require_once __DIR__.'/_helpers.php';
$title='Liste validations';
$pdo=db(); ge_approval_ensure_tables($pdo);
$per=ge_list_limit(); $page=ge_list_page(); $offset=ge_list_offset();
$total=ge_count_tenant_rows($pdo, 'ge_approval_requests');
$pages=max(1,(int)ceil($total/$per)); if($page>$pages){ $page=$pages; $offset=($page-1)*$per; }
$stmt=$pdo->prepare('SELECT * FROM ge_approval_requests WHERE tenant_id=? ORDER BY id ASC LIMIT '.$per.' OFFSET '.$offset); $stmt->execute([ge_current_tenant_id()]); $rows=$stmt->fetchAll();
include __DIR__.'/../layouts/header.php';
?>
<div class="clean-list-page">
  <div class="clean-list-head"><div class="clean-title"><i class="fa-solid fa-clipboard-check"></i><span>Liste validations</span><em>(<?=e($total)?>)</em></div><div class="clean-tools"><span class="clean-page"><?=e($page)?> / <?=e($pages)?></span><a class="clean-add" href="index.php?page=approvals"><i class="fa-solid fa-plus"></i></a></div></div>
  <div class="clean-table-box"><table class="clean-table ge-paginated-table"><thead><tr><th>Objet</th><th>Référence</th><th>Montant</th><th>Priorité</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead><tbody>
    <?php foreach($rows as $r): ?><tr><td><?=e($r['title'] ?: ($r['object_type'].' #'.$r['object_id']))?></td><td><a href="index.php?page=approval_show&id=<?=(int)$r['id']?>"><?=e($r['object_ref'] ?: '#'.$r['id'])?></a></td><td><?=money($r['amount'])?></td><td><?=e($r['priority'] ?? 'Normale')?></td><td><span class="badge gray"><?=e($r['status'])?></span></td><td><?=e($r['created_at'])?></td><td style="white-space:nowrap"><a class="btn small" href="index.php?page=approval_show&id=<?=(int)$r['id']?>">Ouvrir</a> <a class="btn small" href="index.php?page=approvals&edit=<?=(int)$r['id']?>">Modifier</a> <form method="post" action="index.php?page=approval_pdf_generate" style="display:inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><input type="hidden" name="download" value="1"><button class="btn small" type="submit"><i class="fa-solid fa-download"></i> PDF</button></form><?php if(($r['status']??'')==='En attente'): ?> <form method="post" action="index.php?page=approval_status" style="display:inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><input type="hidden" name="status" value="Approuvé"><input type="hidden" name="apply_target" value="1"><button class="btn small" type="submit">Approuver</button></form> <form method="post" action="index.php?page=approval_status" style="display:inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><input type="hidden" name="status" value="Refusé"><button class="btn small danger" type="submit">Refuser</button></form><?php endif; ?></td></tr><?php endforeach; if(!$rows): ?><tr><td colspan="7" class="empty-row">Aucune demande de validation</td></tr><?php endif; ?>
  </tbody></table></div>
  <?=ge_list_pager($total,$page,$pages,'p',['page'=>'approvals_list'])?>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
