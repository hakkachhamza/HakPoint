<?php
$title='Sauvegardes';
$rows=ge_list_backups(50);
include __DIR__.'/../layouts/header.php';
?>
<div class="clean-list-page backup-page">
  <?php if(isset($_GET['created'])): ?><div class="email-status ok">Sauvegarde créée avec succès.</div><?php endif; ?>
  <?php if(isset($_GET['restored'])): ?><div class="email-status ok">Backup restauré avec succès.</div><?php endif; ?>
  <?php if(isset($_GET['err'])): ?><div class="email-status err"><?=e($_GET['err'])?></div><?php endif; ?>

  <div class="clean-list-head">
    <div class="clean-title"><i class="fa-solid fa-database"></i><span>Sauvegardes (<?=count($rows)?>)</span></div>
    <form method="post" action="index.php?page=backup_create" class="clean-tools"><?=csrf_field()?>
      <input class="clean-input" name="note" placeholder="Note courte" style="max-width:220px">
      <button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Créer backup</button>
    </form>
  </div>

  <div class="panel ge-panel-clean backup-restore-panel">
    <div class="ge-panel-title">
      <div><h2><i class="fa-solid fa-rotate-left"></i> Installer / restaurer un backup</h2><p>Choisis un backup existant puis tape <b>RESTORE</b>. Le système crée automatiquement une sauvegarde avant restauration.</p></div>
    </div>
    <form method="post" action="index.php?page=backup_restore" class="backup-restore-form">
      <?=csrf_field()?>
      <select name="filename" required>
        <option value="">Choisir un backup...</option>
        <?php foreach($rows as $r): ?><option value="<?=e($r['filename'] ?? '')?>"><?=e(($r['filename'] ?? '').' — '.($r['created_at'] ?? ''))?></option><?php endforeach; ?>
      </select>
      <input name="confirm_restore" placeholder="Tape RESTORE" autocomplete="off" required>
      <button class="btn danger" type="submit" onclick="return confirm('Restaurer ce backup ? Une sauvegarde de sécurité sera créée avant.');"><i class="fa-solid fa-download"></i> Installer backup</button>
    </form>
  </div>

  <div class="clean-table-box">
    <table class="clean-table excel-report-table">
      <thead><tr><th>Fichier</th><th>Note</th><th>Taille</th><th>Date</th><th>Action</th></tr></thead>
      <tbody>
      <?php if(!$rows): ?><tr><td colspan="5" class="empty-row">Aucune sauvegarde pour le moment.</td></tr><?php endif; ?>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><i class="fa-solid fa-file-shield text-blue"></i> <?=e($r['filename'] ?? '')?></td>
          <td><?=e($r['note'] ?? '')?></td>
          <td><?=number_format(((int)($r['size_bytes'] ?? 0))/1024, 1, ',', ' ')?> KB</td>
          <td><?=e($r['created_at'] ?? '')?></td>
          <td>
            <form method="post" action="index.php?page=backup_restore" class="inline-restore-form"><?=csrf_field()?>
              <input type="hidden" name="filename" value="<?=e($r['filename'] ?? '')?>">
              <input name="confirm_restore" placeholder="RESTORE" required>
              <button class="btn small danger" type="submit" onclick="return confirm('Restaurer <?=e($r['filename'] ?? '')?> ?');">Installer</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="panel backup-note"><b>Important:</b> les fichiers sont gardés dans <code>storage/backups</code>, hors dossier public. La restauration remplace les collections existantes par celles du backup choisi.</div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
