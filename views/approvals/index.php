<?php
require_once __DIR__.'/_helpers.php';
$title='Validations';
$pdo=db(); ge_approval_ensure_tables($pdo);
$editId=(int)($_GET['edit'] ?? 0);
$edit=$editId>0 ? ge_approval_row($pdo,$editId) : null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    $action=$_POST['action'] ?? 'store';
    if($action==='store'){
        $data=ge_approval_normalize_post();
        $id=ge_approval_insert($pdo,$data);
        audit_log('approval_requested','Ref: '.$data['object_ref']);
        redirect_to('index.php?page=approval_show&id='.$id.'&ok=1');
    }
    if($action==='update'){
        $id=(int)($_POST['id'] ?? 0);
        if($id>0){
            $data=ge_approval_normalize_post();
            ge_approval_update($pdo,$id,$data);
            audit_log('approval_updated','ID: '.$id);
            redirect_to('index.php?page=approval_show&id='.$id.'&ok=1');
        }
    }
    if($action==='decide'){
        $id=(int)($_POST['id'] ?? 0);
        ge_approval_decide($pdo,$id,(string)($_POST['status'] ?? 'En attente'),trim((string)($_POST['decision_reason'] ?? '')), !empty($_POST['apply_target']));
        audit_log('approval_decision','Approval id: '.$id.' status: '.($_POST['status'] ?? ''));
        redirect_to('index.php?page=approval_show&id='.$id.'&ok=1');
    }
}
$rows=ge_fetch_tenant_rows($pdo, 'ge_approval_requests', 'id ASC', 20);
$users=data_read('users', []);
include __DIR__.'/../layouts/header.php';
?>
<div class="clean-list-page">
  <?php if(isset($_GET['ok'])): ?><div class="email-status ok">Validation enregistrée.</div><?php endif; ?>
  <div class="clean-list-head"><div class="clean-title"><i class="fa-solid fa-clipboard-check"></i><span>Validations</span></div></div>

  <div class="panel" style="margin-bottom:14px"><form method="post" class="settings-form">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="<?=$edit ? 'update' : 'store'?>">
    <?php if($edit): ?><input type="hidden" name="id" value="<?=(int)$edit['id']?>"><?php endif; ?>
    <div class="settings-two-cols">
      <div><label>Titre</label><input name="title" value="<?=e($edit['title'] ?? '')?>" placeholder="Validation facture / stock / achat..."></div>
      <div><label>Type document</label><select name="object_type"><?php foreach(ge_approval_type_options() as $k=>$label): ?><option value="<?=e($k)?>"<?=ge_approval_selected($edit['object_type'] ?? 'devis',$k)?>><?=e($label)?></option><?php endforeach; ?></select></div>
      <div><label>ID document</label><input name="object_id" inputmode="numeric" value="<?=e($edit['object_id'] ?? '')?>"></div>
      <div><label>Référence</label><input name="object_ref" value="<?=e($edit['object_ref'] ?? '')?>"></div>
      <div><label>Montant</label><input name="amount" value="<?=e($edit['amount'] ?? '0')?>"></div>
      <div><label>Priorité</label><select name="priority"><option<?=ge_approval_selected($edit['priority'] ?? 'Normale','Basse')?>>Basse</option><option<?=ge_approval_selected($edit['priority'] ?? 'Normale','Normale')?>>Normale</option><option<?=ge_approval_selected($edit['priority'] ?? 'Normale','Haute')?>>Haute</option><option<?=ge_approval_selected($edit['priority'] ?? 'Normale','Urgente')?>>Urgente</option></select></div>
      <div><label>Valideur</label><select name="approver_id"><option value="">--</option><?php foreach($users as $u): ?><option value="<?=(int)($u['id']??0)?>"<?=ge_approval_selected($edit['approver_id'] ?? '', $u['id'] ?? '')?>><?=e(ge_user_full_name($u))?></option><?php endforeach; ?></select></div>
      <div><label>Statut</label><select name="status"><?php foreach(ge_approval_status_options() as $s): ?><option<?=ge_approval_selected($edit['status'] ?? 'En attente',$s)?>><?=e($s)?></option><?php endforeach; ?></select></div>
      <div><label>Modèle</label><select name="template"><option value="simple">Simple</option><option value="detail"<?=ge_approval_selected($edit['template'] ?? '', 'detail')?>>Détaillé</option></select></div>
    </div>
    <label>Raison / demande</label><textarea name="reason" rows="2"><?=e($edit['reason'] ?? '')?></textarea>
    <label>Commentaire décision</label><textarea name="decision_reason" rows="2"><?=e($edit['decision_reason'] ?? '')?></textarea>
    <button class="btn primary" type="submit"><i class="fa-solid fa-save"></i> <?=$edit ? 'Enregistrer modification' : 'Demander validation'?></button>
    <?php if($edit): ?><a class="btn" href="index.php?page=approvals">Annuler</a><?php endif; ?>
  </form></div>

  <div class="clean-table-box"><table class="clean-table"><caption style="caption-side:top;text-align:left;padding:8px 0;font-weight:700">Dernières 20 validations <a class="btn small" style="float:right" href="index.php?page=approvals_list">Voir liste complète</a></caption>
    <thead><tr><th>Objet</th><th>Référence</th><th>Montant</th><th>Priorité</th><th>Statut</th><th>Date</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><?=e($r['title'] ?: ($r['object_type'].' #'.$r['object_id']))?></td>
        <td><a href="index.php?page=approval_show&id=<?=(int)$r['id']?>"><?=e($r['object_ref'] ?: '#'.$r['id'])?></a></td>
        <td><?=money($r['amount'])?></td>
        <td><?=e($r['priority'] ?? 'Normale')?></td>
        <td><span class="badge gray"><?=e($r['status'])?></span></td>
        <td><?=e($r['created_at'])?></td>
        <td style="white-space:nowrap">
          <a class="btn small" href="index.php?page=approval_show&id=<?=(int)$r['id']?>">Ouvrir</a>
          <a class="btn small" href="index.php?page=approvals&edit=<?=(int)$r['id']?>">Modifier</a>
          <form method="post" action="index.php?page=approval_pdf_generate" style="display:inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button class="btn small" type="submit">PDF</button></form>
          <?php if(($r['status']??'')==='En attente'): ?>
            <form method="post" action="index.php?page=approval_status" style="display:inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><input type="hidden" name="status" value="Approuvé"><input type="hidden" name="apply_target" value="1"><button class="btn small" type="submit">Approuver</button></form>
            <form method="post" action="index.php?page=approval_status" style="display:inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><input type="hidden" name="status" value="Refusé"><button class="btn small danger" type="submit">Refuser</button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; if(!$rows): ?><tr><td colspan="7" class="empty-row">Aucune demande de validation</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
