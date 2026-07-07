<?php
require_once __DIR__.'/../../app/signatures.php';
$title = 'Signatures';
$tab = $_GET['tab'] ?? 'send';
if (!in_array($tab, ['send','signed','pending'], true)) $tab = 'send';
$statusMsg = '';
$statusClass = '';
$pdfOptions = ge_signature_pdf_options();
$selectedPdf = (string)($_POST['pdf_file'] ?? ($_GET['pdf'] ?? ''));
$selectedOption = null;
foreach ($pdfOptions as $opt) { if ($opt['value'] === $selectedPdf) { $selectedOption = $opt; break; } }
if (!$selectedOption && $pdfOptions) $selectedOption = $pdfOptions[0];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? 'send_signature';
    if ($action === 'send_signature') {
        require_permission('signatures.send');
        $pdfFile = (string)($_POST['pdf_file'] ?? '');
        $upload = ge_signature_handle_uploaded_pdf('pdf_upload');
        if (!empty($upload['ok'])) {
            $pdfFile = (string)$upload['relative'];
        }
        $realPdf = ge_signature_safe_pdf_real($pdfFile);
        $clientName = trim((string)($_POST['client_name'] ?? ''));
        $to = trim((string)($_POST['to_email'] ?? ''));
        $cc = trim((string)($_POST['cc_email'] ?? ''));
        $subject = trim((string)($_POST['subject'] ?? ''));
        $messageTpl = (string)($_POST['message'] ?? '');
        $expiresDays = max(1, min(365, (int)($_POST['expires_days'] ?? 30)));
        if (empty($upload['ok']) && empty($upload['empty']) && !empty($upload['error'])) {
            $statusMsg = (string)$upload['error'];
            $statusClass = 'err';
        } elseif (!$realPdf) {
            $statusMsg = 'PDF introuvable ou non autorisé. Choisis un PDF existant ou upload un nouveau PDF.';
            $statusClass = 'err';
        } elseif (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $statusMsg = 'Adresse email client invalide.';
            $statusClass = 'err';
        } else {
            $token = bin2hex(random_bytes(32));
            $publicUrl = ge_signature_public_url($token);
            $docLabel = ge_signature_pdf_type_label($pdfFile).' — '.basename($pdfFile);
            if ($subject === '') $subject = 'Signature électronique - '.$docLabel;
            if (trim($messageTpl) === '') $messageTpl = ge_signature_default_message($clientName, $docLabel, '{link}');
            $message = str_replace(['{link}','{{link}}','[link]'], $publicUrl, $messageTpl);
            if (!str_contains($message, $publicUrl)) $message .= "\n\nLien de signature :\n".$publicUrl;

            $rows = ge_signature_rows();
            $id = next_id($rows);
            $row = [
                'id' => $id,
                'ref' => 'SIG'.date('ymd').'-'.str_pad((string)$id, 5, '0', STR_PAD_LEFT),
                'token_hash' => ge_signature_token_hash($token),
                'public_url' => $publicUrl,
                'pdf_file' => $pdfFile,
                'pdf_name' => basename($pdfFile),
                'doc_type' => ge_signature_pdf_type_label($pdfFile),
                'client_name' => $clientName,
                'client_email' => $to,
                'cc_email' => $cc,
                'subject' => $subject,
                'message' => $message,
                'status' => 'sent',
                'created_by' => (int)(current_user()['id'] ?? 0),
                'created_by_name' => ge_user_full_name(current_user()),
                'created_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', time() + ($expiresDays * 86400)),
                'sent_at' => '',
                'opened_at' => '',
                'signed_at' => '',
                'email_sent' => 0,
                'email_error' => '',
            ];
            $res = send_real_email($to, $subject, $message, $cc);
            $row['email_sent'] = !empty($res['ok']) ? 1 : 0;
            $row['sent_at'] = !empty($res['ok']) ? date('Y-m-d H:i:s') : '';
            $row['email_error'] = $res['error'] ?? '';
            $rows[] = $row;
            data_write(ge_signature_collection(), $rows, false);
            try {
                $mails=data_read('sent_emails', []);
                $mails[]=['id'=>next_id($mails),'type'=>'signature','object_id'=>$id,'to'=>$to,'cc'=>$cc,'subject'=>$subject,'message'=>$message,'sent'=>!empty($res['ok'])?1:0,'error'=>$res['error']??'','attachments'=>'Lien PDF sécurisé','created_at'=>date('d/m/Y H:i')];
                data_write('sent_emails',$mails,false);
                audit_log('signature_request_created', ($row['ref'] ?? '').' → '.$to.' | '.$row['pdf_name']);
            } catch(Throwable $ignored) {}
            if (!empty($res['ok'])) redirect_to('index.php?page=signatures&tab=pending&sent=1');
            $statusMsg = 'Signature créée, mais l’email n’a pas été envoyé : '.($res['error'] ?? 'Erreur inconnue').'. Le lien reste disponible dans Non signés.';
            $statusClass = 'err';
        }
    }
}

$rows = ge_signature_rows();
usort($rows, fn($a,$b) => strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? '')));
$signedRowsAll = array_values(array_filter($rows, fn($r) => (string)($r['status'] ?? '') === 'signed'));
$pendingRowsAll = array_values(array_filter($rows, fn($r) => (string)($r['status'] ?? '') !== 'signed'));
[$signedRows, $signedTotal, $signedPage, $signedPages] = ge_list_paginate_current($signedRowsAll);
[$pendingRows, $pendingTotal, $pendingPage, $pendingPages] = ge_list_paginate_current($pendingRowsAll);
if (isset($_GET['sent'])) { $statusMsg = 'Lien de signature envoyé au client.'; $statusClass='ok'; }
include __DIR__.'/../layouts/header.php';
?>
<div class="erp-page signatures-page">
  <?php if($statusMsg): ?><div class="email-status <?=$statusClass?>"><?=e($statusMsg)?></div><?php endif; ?>
  <?php if($tab==='send'): ?>
  <section class="panel erp-card signature-send-card">
    <h3><i class="fa-solid fa-file-signature"></i> Envoyer un PDF pour signature</h3>
    <?php if(!$pdfOptions): ?>
      <div class="empty-state compact"><i class="fa-regular fa-file-pdf"></i><b>Aucun PDF existant trouvé.</b><p>Tu peux uploader un nouveau PDF directement ici.</p></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="signature-form">
      <?=csrf_field()?>
      <input type="hidden" name="action" value="send_signature">
      <div class="signature-form-grid">
        <label>PDF à signer</label>
        <div class="signature-pdf-picker">
          <select name="pdf_file" class="smart-select">
            <option value="">Choisir un PDF existant...</option>
            <?php foreach($pdfOptions as $opt): ?>
              <option value="<?=e($opt['value'])?>" <?=($selectedOption && $selectedOption['value']===$opt['value'])?'selected':''?>><?=e($opt['label'])?> — <?=round(((int)$opt['size'])/1024)?> Ko</option>
            <?php endforeach; ?>
          </select>
          <div class="signature-upload-line">
            <span>ou</span>
            <label class="signature-upload-btn">
              <i class="fa-regular fa-file-pdf"></i> Uploader un PDF
              <input type="file" name="pdf_upload" accept="application/pdf,.pdf">
            </label>
            <small class="signature-upload-name">Aucun fichier choisi</small>
          </div>
        </div>
        <label>Nom client</label>
        <input name="client_name" placeholder="Nom du client ou société">
        <label>Email client</label>
        <input type="email" name="to_email" placeholder="client@email.com" required>
        <label>Copie à</label>
        <input name="cc_email" placeholder="optionnel">
        <label>Sujet</label>
        <input name="subject" value="Signature électronique - document Global Energie">
        <label>Validité du lien</label>
        <select name="expires_days"><option value="7">7 jours</option><option value="15">15 jours</option><option value="30" selected>30 jours</option><option value="60">60 jours</option><option value="90">90 jours</option></select>
        <label>Message</label>
        <textarea name="message" rows="8"><?=e(ge_signature_default_message('', 'Votre document PDF', '{link}'))?></textarea>
      </div>
      <div class="erp-actions centered"><button class="btn-orange" type="submit"><i class="fa-solid fa-paper-plane"></i> Envoyer au client</button></div>
    </form>
  </section>
  <?php endif; ?>

  <?php if($tab==='signed'): ?>
  <section class="panel erp-card signature-list-card">
    <div class="clean-list-head signature-list-head">
      <div class="clean-title"><i class="fa-solid fa-circle-check text-green"></i><span>Liste des documents signés (<?=$signedTotal?>)</span></div>
      <div class="clean-tools"><span class="clean-page"><?=e($signedPage)?> / <?=e($signedPages)?></span><a class="clean-add" href="index.php?page=signatures&tab=send"><i class="fa-solid fa-plus"></i></a></div>
    </div>
    <div class="excel-panel-head"><div><b>Documents signés</b><span>20 signatures par page, avec preuve de signature et accès PDF.</span></div></div>
    <div class="table-wrap"><table class="clean-table erp-table strong-lines signature-table"><thead><tr><th>Réf.</th><th>Client</th><th>PDF</th><th>Signature</th><th>Date signature</th><th>Actions</th></tr></thead><tbody>
      <?php foreach($signedRows as $r): ?>
      <tr>
        <td><b><?=e($r['ref'] ?? '')?></b><small><?=e($r['doc_type'] ?? '')?></small></td>
        <td><?=e(($r['client_name'] ?? '') ?: ($r['signer_name'] ?? 'Client'))?><small><?=e($r['client_email'] ?? '')?></small></td>
        <td><?=e($r['pdf_name'] ?? basename($r['pdf_file'] ?? ''))?></td>
        <td><?php if(!empty($r['signature_data'])): ?><img class="signature-preview" src="<?=e($r['signature_data'])?>" alt="Signature"><?php else: ?><span class="muted">Signature textuelle</span><?php endif; ?><small><?=e($r['signer_name'] ?? '')?></small></td>
        <td><?=e($r['signed_at'] ?? '')?></td>
        <td class="nowrap"><a class="mini-action" target="_blank" href="<?=e($r['public_url'] ?? '#')?>"><i class="fa-solid fa-eye"></i> Lien</a> <a class="mini-action" target="_blank" href="<?=e(ge_signature_row_url($r, 'signature_pdf'))?>"><i class="fa-regular fa-file-pdf"></i> PDF signé</a></td>
      </tr>
      <?php endforeach; if(!$signedRowsAll): ?><tr><td colspan="6">Aucun document signé pour le moment.</td></tr><?php endif; ?>
    </tbody></table></div>
    <?=ge_list_pager($signedTotal,$signedPage,$signedPages,'p',['page'=>'signatures','tab'=>'signed'])?>
  </section>
  <?php endif; ?>

  <?php if($tab==='pending'): ?>
  <section class="panel erp-card signature-list-card">
    <div class="clean-list-head signature-list-head">
      <div class="clean-title"><i class="fa-regular fa-clock text-orange"></i><span>Liste des documents non signés (<?=$pendingTotal?>)</span></div>
      <div class="clean-tools"><span class="clean-page"><?=e($pendingPage)?> / <?=e($pendingPages)?></span><a class="clean-add" href="index.php?page=signatures&tab=send"><i class="fa-solid fa-plus"></i></a></div>
    </div>
    <div class="excel-panel-head"><div><b>Documents envoyés / non signés</b><span>20 demandes par page. Les lignes restent ici jusqu’à confirmation du client.</span></div></div>
    <div class="table-wrap"><table class="clean-table erp-table strong-lines signature-table"><thead><tr><th>Réf.</th><th>Client</th><th>PDF</th><th>Statut</th><th>Dates</th><th>Lien</th><th>Actions</th></tr></thead><tbody>
      <?php foreach($pendingRows as $r): ?>
      <tr>
        <td><b><?=e($r['ref'] ?? '')?></b><small><?=e($r['doc_type'] ?? '')?></small></td>
        <td><?=e(($r['client_name'] ?? '') ?: 'Client')?><small><?=e($r['client_email'] ?? '')?></small></td>
        <td><?=e($r['pdf_name'] ?? basename($r['pdf_file'] ?? ''))?></td>
        <td><span class="signature-status <?=e(ge_signature_status_class($r))?>"><?=e(ge_signature_status_label($r))?></span><?php if(!empty($r['email_error'])): ?><small class="danger-text"><?=e($r['email_error'])?></small><?php endif; ?></td>
        <td><small>Créé: <?=e($r['created_at'] ?? '')?></small><small>Ouvert: <?=e(($r['opened_at'] ?? '') ?: '-')?></small><small>Expire: <?=e($r['expires_at'] ?? '-')?></small></td>
        <td><input class="signature-link-input" value="<?=e($r['public_url'] ?? '')?>" readonly onclick="this.select()"></td>
        <td class="nowrap"><a class="mini-action" target="_blank" href="<?=e($r['public_url'] ?? '#')?>"><i class="fa-solid fa-arrow-up-right-from-square"></i> Ouvrir</a><?php if(!empty($r['pdf_file'])): ?> <a class="mini-action" href="index.php?page=pdf_view&file=<?=urlencode($r['pdf_file'])?>"><i class="fa-regular fa-file-pdf"></i> PDF</a><?php endif; ?></td>
      </tr>
      <?php endforeach; if(!$pendingRowsAll): ?><tr><td colspan="7">Aucun document non signé.</td></tr><?php endif; ?>
    </tbody></table></div>
    <?=ge_list_pager($pendingTotal,$pendingPage,$pendingPages,'p',['page'=>'signatures','tab'=>'pending'])?>
  </section>
  <?php endif; ?>
</div>
<script>
document.addEventListener('change', function(e){
  if (!e.target || e.target.name !== 'pdf_upload') return;
  var box = e.target.closest('.signature-upload-line');
  var name = box ? box.querySelector('.signature-upload-name') : null;
  if (name) name.textContent = (e.target.files && e.target.files[0]) ? e.target.files[0].name : 'Aucun fichier choisi';
});
</script>
<?php include __DIR__.'/../layouts/footer.php'; ?>
