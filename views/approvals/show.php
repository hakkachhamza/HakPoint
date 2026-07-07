<?php
require_once __DIR__.'/_helpers.php';
$title='Validation';
$pdo=db(); ge_approval_ensure_tables($pdo);
$id=(int)($_GET['id'] ?? 0);
$row=ge_approval_row($pdo,$id);
if(!$row){ include __DIR__.'/../layouts/header.php'; echo '<div class="panel">Validation introuvable.</div>'; include __DIR__.'/../layouts/footer.php'; return; }
$docs=ge_approval_docs($id);
include __DIR__.'/../layouts/header.php';
?>
<div class="clean-list-page">
  <?php if(isset($_GET['ok'])): ?><div class="email-status ok">Validation mise à jour.</div><?php endif; ?>
  <?php if(isset($_GET['pdf_generated'])): ?><div class="email-status ok">PDF généré.</div><?php endif; ?>
  <div class="clean-list-head">
    <div class="clean-title"><i class="fa-solid fa-clipboard-check"></i><span>Validation <?=e($row['object_ref'] ?: '#'.$id)?></span></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn" href="index.php?page=approvals">Retour</a>
      <a class="btn" href="index.php?page=approvals&edit=<?=(int)$id?>">Modifier</a>
      <form method="post" action="index.php?page=approval_pdf_generate"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$id?>"><button class="btn primary" type="submit"><i class="fa-solid fa-file-pdf"></i> Générer PDF</button></form>
    </div>
  </div>

  <div class="panel" style="margin-bottom:14px">
    <div class="settings-two-cols">
      <div><b>Titre</b><br><?=e($row['title'] ?: '-')?></div>
      <div><b>Objet</b><br><?=e($row['object_type'])?> #<?=e($row['object_id'])?></div>
      <div><b>Référence</b><br><?=e($row['object_ref'] ?: '-')?></div>
      <div><b>Montant</b><br><?=money($row['amount'])?></div>
      <div><b>Priorité</b><br><?=e($row['priority'] ?: 'Normale')?></div>
      <div><b>Statut</b><br><span class="badge gray"><?=e($row['status'])?></span></div>
      <div><b>Demandé par</b><br><?=e(ge_approval_user_label((int)($row['requested_by'] ?? 0)))?></div>
      <div><b>Valideur</b><br><?=e(ge_approval_user_label((int)($row['approver_id'] ?? 0)))?></div>
      <div><b>Décidé par</b><br><?=e(ge_approval_user_label((int)($row['decided_by'] ?? 0)))?></div>
      <div><b>Décidé le</b><br><?=e($row['decided_at'] ?: '-')?></div>
      <div><b>Appliqué le</b><br><?=e($row['applied_at'] ?: '-')?></div>
      <div><b>Créé le</b><br><?=e($row['created_at'] ?: '-')?></div>
    </div>
    <?php if(!empty($row['reason'])): ?><hr><b>Raison</b><p><?=nl2br(e($row['reason']))?></p><?php endif; ?>
    <?php if(!empty($row['decision_reason'])): ?><b>Commentaire décision</b><p><?=nl2br(e($row['decision_reason']))?></p><?php endif; ?>
  </div>

  <div class="panel" style="margin-bottom:14px">
    <h3 style="margin-top:0">Décision</h3>
    <form method="post" action="index.php?page=approval_status" class="settings-form">
      <?=csrf_field()?>
      <input type="hidden" name="id" value="<?=(int)$id?>">
      <div class="settings-two-cols">
        <div><label>Statut</label><select name="status"><?php foreach(ge_approval_status_options() as $s): ?><option<?=ge_approval_selected($row['status'],$s)?>><?=e($s)?></option><?php endforeach; ?></select></div>
        <div><label>Appliquer au document lié</label><select name="apply_target"><option value="0">Non</option><option value="1">Oui si approuvé</option></select></div>
      </div>
      <label>Commentaire</label><textarea name="decision_reason" rows="2"><?=e($row['decision_reason'] ?? '')?></textarea>
      <button class="btn primary" type="submit">Enregistrer décision</button>
    </form>
  </div>

  <div class="panel" style="margin-bottom:14px">
    <h3 style="margin-top:0">Documents</h3>
    <div class="clean-table-box"><table class="clean-table"><thead><tr><th>Fichier</th><th>Date</th><th>Taille</th><th>Actions</th></tr></thead><tbody>
      <?php foreach($docs as $d): ?>
        <tr><td><?=e($d['filename'] ?? '')?></td><td><?=e($d['created_at'] ?? '')?></td><td><?=isset($d['size_bytes']) ? number_format((float)$d['size_bytes']/1024,1,',',' ') : ''?> KB</td><td style="white-space:nowrap"><?php if(!empty($d['url'])): ?><a class="btn small" target="_blank" href="<?=e($d['url'])?>">Voir PDF</a><?php endif; ?><form method="post" action="index.php?page=approval_document_delete" style="display:inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$id?>"><input type="hidden" name="doc_id" value="<?=(int)($d['id'] ?? 0)?>"><button class="btn small danger" type="submit">Supprimer</button></form></td></tr>
      <?php endforeach; if(!$docs): ?><tr><td colspan="4" class="empty-row">Aucun document. Cliquez sur Générer PDF.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>

  <div class="panel">
    <h3 style="margin-top:0">Zone danger</h3>
    <form method="post" action="index.php?page=approval_delete" onsubmit="return confirm('Supprimer cette validation ?')">
      <?=csrf_field()?>
      <input type="hidden" name="id" value="<?=(int)$id?>">
      <button class="btn danger" type="submit">Supprimer validation</button>
    </form>
  </div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
