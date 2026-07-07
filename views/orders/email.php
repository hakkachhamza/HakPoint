<?php
require_once __DIR__.'/_helpers.php';
$orders=data_read('orders',[]);
$id=(int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$order=find_row_by_id($orders,$id);
if(!$order) redirect_to('index.php?page=orders');
$statusMsg=''; $statusClass='';
$to='';
$clientId=(int)($order['client_id'] ?? 0);
if($clientId>0){ $tier=find_row_by_id(tiers_all(), $clientId); if($tier) $to=$tier['email'] ?? ''; }
if($to===''){
    $clientName=trim((string)($order['client'] ?? ''));
    foreach(tiers_all() as $t){ if(trim((string)($t['name'] ?? ''))===$clientName){ $to=$t['email'] ?? ''; break; } }
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_once __DIR__.'/../../app/pdf_docs.php';
    $attachments=ge_email_attachments_with_optional_pdf('order',$id);
    $res=send_real_email($_POST['to']??'', $_POST['subject']??'', $_POST['message']??'', $_POST['cc']??'', $attachments);
    $emails=data_read('order_emails', []);
    $emails[]=['id'=>next_id($emails),'order_id'=>$id,'from'=>'contact@hakpoint.ma','to'=>$_POST['to']??'','cc'=>$_POST['cc']??'','subject'=>$_POST['subject']??'','message'=>$_POST['message']??'','sent'=>$res['ok']?1:0,'error'=>$res['error']??'','attachments'=>mail_attachment_names($attachments),'created_at'=>date('d/m/Y H:i')];
    data_write('order_emails', $emails);
    if($res['ok']) redirect_to('index.php?page=order_show&id='.$id.'&email_sent=1');
    $statusMsg='Erreur envoi email : '.($res['error'] ?? 'Erreur inconnue'); $statusClass='err';
}
$title='Envoyer email commande '.$order['ref'];
include __DIR__.'/../layouts/header.php';
?>
<div class="quote-email-page order-email-page">
  <div class="quote-email-icon"><i class="fa-solid fa-envelope"></i></div>
  <?php if($statusMsg): ?><div class="email-status <?=$statusClass?>"><?=e($statusMsg)?></div><?php endif; ?>
  <form method="post" action="index.php?page=order_email" class="dol-form email-form" enctype="multipart/form-data">
    <?=csrf_field()?>
    <input type="hidden" name="id" value="<?=(int)$id?>">
    <div class="form-row"><label>De</label><select name="from"><option>Global Energie &lt;contact@hakpoint.ma&gt;</option></select></div>
    <div class="form-row"><label>Adressé à</label><input type="email" name="to" value="<?=e($to)?>" placeholder="email client<?= !empty($order['client']) ? ' - '.e($order['client']) : '' ?>" required></div>
    <div class="form-row small-row"><label>Copie à</label><input name="cc"></div>
    <div class="form-row subject-row"><label>Sujet</label><input name="subject" value="Envoi de la commande __<?=e($order['ref'] ?? '')?>__" required></div>
    <div class="form-row checkbox-row"><label>Fichiers joints</label><div><label class="inline-check"><input type="checkbox" name="attach_main" checked> Joindre le document principal PDF.</label></div></div>
    <div class="form-row message-row"><label>Message</label><textarea name="message" rows="8">Bonjour,

Veuillez trouver votre commande __<?=e($order['ref'] ?? '')?>__.

Sincèrement</textarea></div>
    <div class="form-row attachment-row"><label>Pièces jointes</label><div class="attachment-box"><input type="file" name="attachments[]" multiple accept=".pdf,image/*"><small>Vous pouvez joindre des images ou des PDF (max 10 MB par fichier).</small></div></div>
    <div class="form-actions centered"><button class="purple-btn">ENVOYER EMAIL</button><a class="purple-btn light" href="index.php?page=order_show&id=<?=(int)$id?>">ANNULER</a></div>
  </form>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
