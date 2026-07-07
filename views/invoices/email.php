<?php
$invoices=data_read('invoices',[]); $id=(int)($_GET['id']??($_POST['id']??0)); $invoice=find_row_by_id($invoices,$id); if(!$invoice) redirect_to('index.php?page=invoices');
$statusMsg=''; $statusClass='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_once __DIR__.'/../../app/pdf_docs.php';
    $attachments=ge_email_attachments_with_optional_pdf('invoice',$id);
    $res=send_real_email($_POST['to']??'', $_POST['subject']??'', $_POST['message']??'', $_POST['cc']??'', $attachments);
    $emails=data_read('invoice_emails',[]);
    $emails[]=['id'=>next_id($emails),'invoice_id'=>$id,'from'=>'contact@hakpoint.ma','to'=>$_POST['to']??'','cc'=>$_POST['cc']??'','subject'=>$_POST['subject']??'','message'=>$_POST['message']??'','sent'=>$res['ok']?1:0,'error'=>$res['error']??'','attachments'=>mail_attachment_names($attachments),'created_at'=>date('d/m/Y H:i')];
    data_write('invoice_emails',$emails);
    if($res['ok']) redirect_to('index.php?page=invoice_show&id='.$id.'&email_sent=1');
    $statusMsg='Erreur envoi email : '.($res['error'] ?? 'Erreur inconnue'); $statusClass='err';
}
$title='Envoyer email facture'; include __DIR__.'/../layouts/header.php'; $ref=$invoice['ref']??'REF'; $client=$invoice['client']??'';
?>
<div class="quote-email-page invoice-email-page">
    <div class="quote-email-icon"><i class="fa-solid fa-envelope"></i></div>
    <?php if($statusMsg): ?><div class="email-status <?=$statusClass?>"><?=e($statusMsg)?></div><?php endif; ?>
    <form method="post" class="email-form" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>">
        <div class="form-row"><label>De</label><select name="from"><option>Global Energie &lt;contact@hakpoint.ma&gt;</option></select></div>
        <div class="form-row"><label>Adressé à</label><input type="email" name="to" placeholder="email client<?= $client ? ' - '.e($client) : '' ?>" required></div>
        <div class="form-row small-row"><label>Copie à <i class="fa-solid fa-circle-info info"></i></label><input name="cc"></div>
        <div class="form-row subject-row"><label>Sujet <i class="fa-solid fa-circle-info info"></i></label><input name="subject" value="Envoi de la facture __<?=e($ref)?>__" required></div>
        <div class="form-row checkbox-row"><label>Fichiers joints</label><div><label class="inline-check"><input type="checkbox" name="attach_main" checked> Joindre la facture PDF.</label></div></div>
        <div class="form-row message-row"><label>Message <i class="fa-solid fa-circle-info info"></i></label><textarea name="message" rows="8">Bonjour

Veuillez trouver la facture __<?=e($ref)?>__

Sincèrement</textarea></div>

        <div class="form-row attachment-row"><label>Pièces jointes</label><div class="attachment-box"><input type="file" name="attachments[]" multiple accept=".pdf,image/*"><small>Vous pouvez joindre des images ou des PDF (max 10 MB par fichier).</small></div></div>
        <div class="centered"><button class="purple-btn">ENVOYER EMAIL</button> <a class="purple-btn light" href="index.php?page=invoice_show&id=<?=$id?>">ANNULER</a></div>
    </form>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
