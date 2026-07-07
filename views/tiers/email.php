<?php $id=(int)($_GET['id']??($_POST['id']??0)); $t=find_row_by_id(tiers_all(),$id); if(!$t) redirect_to('index.php?page=tiers'); $statusMsg=''; if($_SERVER['REQUEST_METHOD']==='POST'){ $attachments=mail_uploaded_attachments(); $res=send_real_email($_POST['to']??'', $_POST['subject']??'', $_POST['message']??'', $_POST['cc']??'', $attachments); $mails=data_read('sent_emails', []); $mails[]=['id'=>next_id($mails),'type'=>'tiers','object_id'=>$id,'to'=>$_POST['to']??'','cc'=>$_POST['cc']??'','subject'=>$_POST['subject']??'','message'=>$_POST['message']??'','sent'=>$res['ok']?1:0,'error'=>$res['error']??'','attachments'=>mail_attachment_names($attachments),'created_at'=>date('d/m/Y H:i')]; data_write('sent_emails',$mails); if($res['ok']) redirect_to('index.php?page=tiers_show&id='.$id.'&email_sent=1'); $statusMsg='Erreur envoi email : '.($res['error'] ?? 'Erreur inconnue'); } include __DIR__.'/../layouts/header.php'; ?>
<div class="panel email-form">
  <h2>Envoyer email à <?=e($t['name']??'')?></h2>
  <?php if($statusMsg): ?><div class="email-status err"><?=e($statusMsg)?></div><?php endif; ?>
  <form method="post" action="index.php?page=tiers_email" enctype="multipart/form-data"><?=csrf_field()?><input type="hidden" name="id" value="<?=$id?>">
    <div class="form-row"><label>De</label><select><option>Global Energie &lt;contact@hakpoint.ma&gt;</option></select></div>
    <div class="form-row"><label>Adressé à</label><input type="email" name="to" value="<?=e($t['email']??'')?>" required></div>
    <div class="form-row"><label>Copie à</label><input name="cc"></div>
    <div class="form-row"><label>Sujet</label><input name="subject" value="Message commercial" required></div>
    <div class="form-row message-row"><label>Message</label><textarea name="message" rows="8">Bonjour,

Veuillez trouver notre message ci-joint.

Sincèrement</textarea></div>

    <div class="form-row attachment-row"><label>Pièces jointes</label><div class="attachment-box"><input type="file" name="attachments[]" multiple accept=".pdf,image/*"><small>Vous pouvez joindre des images ou des PDF (max 10 MB par fichier).</small></div></div>
    <div class="form-actions"><button class="purple-btn">ENVOYER EMAIL</button><a class="purple-btn secondary" href="index.php?page=tiers_show&id=<?=$id?>">ANNULER</a></div>
  </form>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
