<?php
require_once __DIR__.'/_helpers.php';
$users=data_read('users',[]);
$id=(int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$u=find_row_by_id($users,$id);
if(!$u) redirect_to('index.php?page=users');
$statusMsg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $attachments=mail_uploaded_attachments();
    $res=send_real_email($_POST['to']??'', $_POST['subject']??'', $_POST['message']??'', $_POST['cc']??'', $attachments);
    $emails=data_read('user_emails', []);
    $emails[]=['id'=>next_id($emails),'user_id'=>$id,'from'=>'contact@hakpoint.ma','to'=>$_POST['to']??'','cc'=>$_POST['cc']??'','subject'=>$_POST['subject']??'','message'=>$_POST['message']??'','sent'=>$res['ok']?1:0,'error'=>$res['error']??'','attachments'=>mail_attachment_names($attachments),'created_at'=>date('d/m/Y H:i')];
    data_write('user_emails',$emails);
    if($res['ok']) redirect_to('index.php?page=user_show&id='.$id.'&email_sent=1');
    $statusMsg='Erreur envoi email : '.($res['error'] ?? 'Erreur inconnue');
}
$title='Envoyer email utilisateur';
include __DIR__.'/../layouts/header.php';
$name=user_display_name($u);
?>
<div class="panel email-form">
  <h2>Envoyer email à <?=e($name)?></h2>
  <?php if($statusMsg): ?><div class="email-status err"><?=e($statusMsg)?></div><?php endif; ?>
  <form method="post" action="index.php?page=user_email" enctype="multipart/form-data">
    <?=csrf_field()?>
    <input type="hidden" name="id" value="<?=(int)$id?>">
    <div class="form-row"><label>De</label><select><option>Global Energie &lt;contact@hakpoint.ma&gt;</option></select></div>
    <div class="form-row"><label>Adressé à</label><input type="email" name="to" value="<?=e($u['email'] ?? '')?>" required></div>
    <div class="form-row"><label>Copie à</label><input name="cc"></div>
    <div class="form-row"><label>Sujet</label><input name="subject" value="Message Global Energie" required></div>
    <div class="form-row message-row"><label>Message</label><textarea name="message" rows="8">Bonjour <?=e($name)?>,

Veuillez trouver notre message ci-joint.

Sincèrement</textarea></div>
    <div class="form-row attachment-row"><label>Pièces jointes</label><div class="attachment-box"><input type="file" name="attachments[]" multiple accept=".pdf,image/*"><small>Vous pouvez joindre des images ou des PDF (max 10 MB par fichier).</small></div></div>
    <div class="form-actions"><button class="purple-btn">ENVOYER EMAIL</button><a class="purple-btn secondary" href="index.php?page=user_show&id=<?=(int)$id?>">ANNULER</a></div>
  </form>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
