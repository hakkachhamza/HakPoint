<?php
$quotes = data_read('quotes', []);
$id = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$quote = find_row_by_id($quotes, $id);
if(!$quote) redirect_to('index.php?page=quotes');
$statusMsg=''; $statusClass='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_once __DIR__.'/../../app/pdf_docs.php';
    $attachments=ge_email_attachments_with_optional_pdf('quote',$id);
    $res=send_real_email($_POST['to_email']??'', $_POST['subject']??'', $_POST['message']??'', $_POST['cc_email']??'', $attachments);
    $emails = data_read('quote_emails', []);
    $emails[] = ['id'=>next_id($emails),'quote_id'=>$id,'from'=>'contact@hakpoint.ma','to'=>$_POST['to_email'] ?? '','cc'=>$_POST['cc_email'] ?? '','subject'=>$_POST['subject'] ?? '','message'=>$_POST['message'] ?? '','attach_main'=>isset($_POST['attach_main']) ? 1 : 0,'sent'=>$res['ok']?1:0,'error'=>$res['error']??'','attachments'=>mail_attachment_names($attachments),'created_at'=>date('d/m/Y H:i')];
    data_write('quote_emails', $emails);
    if($res['ok']) redirect_to('index.php?page=quote_show&id='.$id.'&email_sent=1');
    $statusMsg='Erreur envoi email : '.($res['error'] ?? 'Erreur inconnue'); $statusClass='err';
}
$title='Envoyer email - Devis '.$quote['ref']; include __DIR__.'/../layouts/header.php';
$ref = $quote['ref'] ?? 'REF'; $client = $quote['client'] ?? '';
?>
<div class="quote-email-page">
    <div class="quote-email-icon"><i class="fa-solid fa-envelope"></i></div>
    <?php if($statusMsg): ?><div class="email-status <?=$statusClass?>"><?=e($statusMsg)?></div><?php endif; ?>
    <form method="post" action="index.php?page=quote_email" class="dol-form email-form" enctype="multipart/form-data">
        <?=csrf_field()?>
        <input type="hidden" name="id" value="<?=(int)$id?>">
        <div class="form-row"><label>De</label><select name="from_email"><option>Global Energie &lt;contact@hakpoint.ma&gt;</option></select></div>
        <div class="form-row"><label>Adressé à</label><input type="email" name="to_email" value="" placeholder="email client<?= $client ? ' - '.e($client) : '' ?>" required></div>
        <div class="form-row small-row"><label>Copie à <i class="fa-solid fa-circle-info info"></i></label><input type="text" name="cc_email"></div>
        <div class="form-row subject-row"><label>Sujet <i class="fa-solid fa-circle-info info"></i></label><input type="text" name="subject" value="Envoi de la proposition commerciale __<?=e($ref)?>__" required></div>
        <div class="form-row checkbox-row"><label>Fichiers joints</label><div><label class="inline-check"><input type="checkbox" name="attach_main"> Joindre le document principal.</label><p class="muted-help">Si coché, le PDF principal est généré automatiquement et joint à l’email.</p></div></div>
        <div class="form-row message-row"><label>Message <i class="fa-solid fa-circle-info info"></i></label><textarea name="message" rows="8">Bonjour

Veuillez trouver la proposition commerciale __<?=e($ref)?>__

Sincèrement</textarea></div>

        <div class="form-row attachment-row"><label>Pièces jointes</label><div class="attachment-box"><input type="file" name="attachments[]" multiple accept=".pdf,image/*"><small>Vous pouvez joindre des images ou des PDF (max 10 MB par fichier).</small></div></div>
        <div class="form-actions centered"><button class="purple-btn" type="submit">ENVOYER EMAIL</button><a class="purple-btn light" href="index.php?page=quote_show&id=<?=(int)$id?>">ANNULER</a></div>
    </form>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
