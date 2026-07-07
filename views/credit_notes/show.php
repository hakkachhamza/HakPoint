<?php
require_once __DIR__.'/_helpers.php';
$title='Avoir client';
$pdo=db(); ge_credit_ensure_tables($pdo);
$id=(int)($_GET['id'] ?? 0);
$row=ge_credit_row($pdo,$id);
if(!$row){ include __DIR__.'/../layouts/header.php'; echo '<div class="panel">Avoir introuvable.</div>'; include __DIR__.'/../layouts/footer.php'; return; }
$docs=ge_credit_docs($id);
include __DIR__.'/../layouts/header.php';
?>
<div class="clean-list-page">
  <?php if(isset($_GET['ok'])): ?><div class="email-status ok">Avoir mis à jour.</div><?php endif; ?>
  <?php if(isset($_GET['pdf_generated'])): ?><div class="email-status ok">PDF généré.</div><?php endif; ?>
  <div class="clean-list-head">
    <div class="clean-title"><i class="fa-solid fa-file-circle-minus"></i><span>Avoir client <?=e($row['ref'])?></span></div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn" href="index.php?page=credit_notes">Retour</a>
      <a class="btn" href="index.php?page=credit_notes&edit=<?=(int)$id?>">Modifier</a>
      <form method="post" action="index.php?page=credit_note_pdf_generate"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$id?>"><button class="btn primary" type="submit"><i class="fa-solid fa-file-pdf"></i> Générer PDF</button></form>
    </div>
  </div>

  <div class="panel" style="margin-bottom:14px">
    <div class="settings-two-cols">
      <div><b>Client</b><br><?=e($row['client_name'] ?: '-')?></div>
      <div><b>Facture source</b><br><?=e($row['invoice_ref'] ?: ($row['invoice_id'] ?: '-'))?></div>
      <div><b>Date</b><br><?=e($row['credit_date'] ?: '-')?></div>
      <div><b>Statut</b><br><span class="badge gray"><?=e($row['status'])?></span></div>
      <div><b>Total HT</b><br><?=money($row['amount_ht'])?></div>
      <div><b>TVA</b><br><?=money($row['amount_tva'] ?? 0)?></div>
      <div><b>Total TTC</b><br><?=money($row['amount_ttc'])?></div>
      <div><b>Remboursé le</b><br><?=e($row['refunded_at'] ?: '-')?></div>
    </div>
    <?php if(!empty($row['reason'])): ?><hr><b>Raison</b><p><?=nl2br(e($row['reason']))?></p><?php endif; ?>
    <?php if(!empty($row['note'])): ?><b>Note interne</b><p><?=nl2br(e($row['note']))?></p><?php endif; ?>
  </div>

  <div class="panel" style="margin-bottom:14px">
    <h3 style="margin-top:0">Changer le statut</h3>
    <form method="post" action="index.php?page=credit_note_status" class="settings-form">
      <?=csrf_field()?>
      <input type="hidden" name="id" value="<?=(int)$id?>">
      <div class="settings-two-cols"><div><label>Statut</label><select name="status"><?php foreach(ge_credit_status_options() as $s): ?><option<?=ge_credit_selected($row['status'],$s)?>><?=e($s)?></option><?php endforeach; ?></select></div></div>
      <button class="btn primary" type="submit">Mettre à jour</button>
    </form>
  </div>

  <div class="panel" style="margin-bottom:14px">
    <h3 style="margin-top:0">Documents</h3>
    <div class="clean-table-box"><table class="clean-table"><thead><tr><th>Fichier</th><th>Date</th><th>Taille</th><th>Actions</th></tr></thead><tbody>
      <?php foreach($docs as $d): ?>
        <tr><td><?=e($d['filename'] ?? '')?></td><td><?=e($d['created_at'] ?? '')?></td><td><?=isset($d['size_bytes']) ? number_format((float)$d['size_bytes']/1024,1,',',' ') : ''?> KB</td><td style="white-space:nowrap"><?php if(!empty($d['url'])): ?><a class="btn small" target="_blank" href="<?=e($d['url'])?>">Voir PDF</a><?php endif; ?><form method="post" action="index.php?page=credit_note_document_delete" style="display:inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$id?>"><input type="hidden" name="doc_id" value="<?=(int)($d['id'] ?? 0)?>"><button class="btn small danger" type="submit">Supprimer</button></form></td></tr>
      <?php endforeach; if(!$docs): ?><tr><td colspan="4" class="empty-row">Aucun document. Cliquez sur Générer PDF.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>

  <div class="panel">
    <h3 style="margin-top:0">Zone danger</h3>
    <form method="post" action="index.php?page=credit_note_delete" onsubmit="return confirm('Supprimer cet avoir ?')">
      <?=csrf_field()?>
      <input type="hidden" name="id" value="<?=(int)$id?>">
      <button class="btn danger" type="submit">Supprimer avoir</button>
    </form>
  </div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
