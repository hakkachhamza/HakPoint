<?php
require_once __DIR__.'/_helpers.php';
$title='Avoirs clients';
$pdo=db(); ge_credit_ensure_tables($pdo);
$editId=(int)($_GET['edit'] ?? 0);
$edit=$editId>0 ? ge_credit_row($pdo,$editId) : null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    $action=$_POST['action'] ?? 'store';
    if($action==='store'){
        $data=ge_credit_normalize_post($pdo);
        $id=ge_credit_insert($pdo,$data);
        audit_log('credit_note_created','Ref: '.$data['ref']);
        redirect_to('index.php?page=credit_note_show&id='.$id.'&ok=1');
    }
    if($action==='update'){
        $id=(int)($_POST['id'] ?? 0);
        $old=$id>0 ? (ge_credit_row($pdo,$id) ?: []) : [];
        if($id>0){
            $data=ge_credit_normalize_post($pdo,$old);
            ge_credit_update($pdo,$id,$data);
            audit_log('credit_note_updated','Ref: '.$data['ref']);
            redirect_to('index.php?page=credit_note_show&id='.$id.'&ok=1');
        }
    }
}
$rows=ge_fetch_tenant_rows($pdo, 'ge_credit_notes', 'id ASC', 20);
$invoices=ge_credit_invoice_options();
include __DIR__.'/../layouts/header.php';
?>
<div class="clean-list-page">
  <?php if(isset($_GET['ok'])): ?><div class="email-status ok">Avoir enregistré.</div><?php endif; ?>
  <div class="clean-list-head">
    <div class="clean-title"><i class="fa-solid fa-file-circle-minus"></i><span>Avoirs clients</span></div>
  </div>

  <div class="panel" style="margin-bottom:14px">
    <form method="post" class="settings-form">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="<?=$edit ? 'update' : 'store'?>">
      <?php if($edit): ?><input type="hidden" name="id" value="<?=(int)$edit['id']?>"><?php endif; ?>
      <div class="settings-two-cols">
        <div><label>Référence</label><input name="ref" value="<?=e($edit['ref'] ?? '')?>" placeholder="Auto si vide"></div>
        <div><label>Facture source</label><select name="invoice_id"><option value="">-- aucune --</option><?php foreach($invoices as $inv): ?><option value="<?=(int)$inv['id']?>"<?=ge_credit_selected($edit['invoice_id'] ?? '', $inv['id'])?>><?=e($inv['ref'].' - '.$inv['client'].' - '.ge_credit_fmt($inv['amount_ttc']))?></option><?php endforeach; ?></select></div>
        <div><label>Réf. facture source</label><input name="invoice_ref" value="<?=e($edit['invoice_ref'] ?? '')?>"></div>
        <div><label>Client</label><input name="client_name" value="<?=e($edit['client_name'] ?? '')?>"></div>
        <div><label>Date avoir</label><input type="date" name="credit_date" value="<?=e(ge_credit_date($edit['credit_date'] ?? date('Y-m-d')))?>"></div>
        <div><label>Statut</label><select name="status"><?php foreach(ge_credit_status_options() as $s): ?><option<?=ge_credit_selected($edit['status'] ?? 'Brouillon',$s)?>><?=e($s)?></option><?php endforeach; ?></select></div>
        <div><label>Montant HT</label><input name="amount_ht" value="<?=e($edit['amount_ht'] ?? '0')?>"></div>
        <div><label>TVA</label><input name="amount_tva" value="<?=e($edit['amount_tva'] ?? '0')?>"></div>
        <div><label>Montant TTC</label><input name="amount_ttc" value="<?=e($edit['amount_ttc'] ?? '0')?>"></div>
        <div><label>Modèle</label><select name="template"><option value="simple">Simple</option><option value="detail"<?=ge_credit_selected($edit['template'] ?? '', 'detail')?>>Détaillé</option></select></div>
      </div>
      <label>Raison</label><textarea name="reason" rows="2"><?=e($edit['reason'] ?? '')?></textarea>
      <label>Note interne</label><textarea name="note" rows="2"><?=e($edit['note'] ?? '')?></textarea>
      <button class="btn primary" type="submit"><i class="fa-solid fa-save"></i> <?=$edit ? 'Enregistrer modification' : 'Ajouter avoir'?></button>
      <?php if($edit): ?><a class="btn" href="index.php?page=credit_notes">Annuler</a><?php endif; ?>
    </form>
  </div>

  <div class="clean-table-box"><table class="clean-table"><caption style="caption-side:top;text-align:left;padding:8px 0;font-weight:700">Derniers 20 avoirs clients <a class="btn small" style="float:right" href="index.php?page=credit_notes_list">Voir liste complète</a></caption>
    <thead><tr><th>Réf.</th><th>Facture</th><th>Client</th><th>Date</th><th>Statut</th><th>HT</th><th>TTC</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($rows as $r): ?>
      <tr>
        <td><a href="index.php?page=credit_note_show&id=<?=(int)$r['id']?>"><?=e($r['ref'])?></a></td>
        <td><?=e($r['invoice_ref'] ?: $r['invoice_id'])?></td>
        <td><?=e($r['client_name'])?></td>
        <td><?=e($r['credit_date'])?></td>
        <td><span class="badge gray"><?=e($r['status'])?></span></td>
        <td><?=money($r['amount_ht'])?></td>
        <td><?=money($r['amount_ttc'])?></td>
        <td style="white-space:nowrap">
          <a class="btn small" href="index.php?page=credit_note_show&id=<?=(int)$r['id']?>">Ouvrir</a>
          <a class="btn small" href="index.php?page=credit_notes&edit=<?=(int)$r['id']?>">Modifier</a>
          <form method="post" action="index.php?page=credit_note_pdf_generate" style="display:inline"><?=csrf_field()?><input type="hidden" name="id" value="<?=(int)$r['id']?>"><button class="btn small" type="submit">PDF</button></form>
        </td>
      </tr>
    <?php endforeach; if(!$rows): ?><tr><td colspan="8" class="empty-row">Aucun avoir</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
