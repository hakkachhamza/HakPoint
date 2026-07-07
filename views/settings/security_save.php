<?php
$enable2fa=($_POST['twofa_enabled'] ?? '0') === '1';
$current=$_POST['current_password']??''; $new=$_POST['new_password']??''; $confirm=$_POST['confirm_password']??'';
$cu=current_user();
$users=data_read('users', []);
$currentUserSecret=(string)($cu['twofa_secret'] ?? '');
$currentUser2fa=!empty($cu['twofa_enabled']) && $currentUserSecret !== '';
$secret=$currentUserSecret;

if($new!=='' || $confirm!=='' || $current!==''){
    if(!password_verify($current, $cu['password'] ?? '')) redirect_to('index.php?page=settings&err='.urlencode('Mot de passe actuel incorrect').'#security-section');
    if(strlen($new)<8) redirect_to('index.php?page=settings&err='.urlencode('Le nouveau mot de passe doit contenir au moins 8 caractères').'#security-section');
    if($new!==$confirm) redirect_to('index.php?page=settings&err='.urlencode('La confirmation du mot de passe ne correspond pas').'#security-section');
    foreach($users as &$u){ if((int)($u['id']??0)===(int)($cu['id']??0)){ $u['password']=password_hash($new,PASSWORD_DEFAULT); $u['force_password_change']=false; $u['password_changed_at']=date('Y-m-d H:i:s'); unset($u['plain_password'], $u['password_plain']); $_SESSION['user']=$u; $cu=$u; break; }}
    unset($u); data_write('users',$users); audit_log('password_changed','Current user changed password');
}

if($enable2fa){
    $postedSecret=trim((string)($_POST['twofa_secret'] ?? $secret));
    if(!$postedSecret) redirect_to('index.php?page=settings&err='.urlencode('Secret 2FA introuvable').'#security-section');

    // Verify OTP when enabling 2FA for this user or replacing this user's secret.
    // Existing enabled users can save other security changes without entering OTP again.
    if(!$currentUser2fa || $postedSecret !== $currentUserSecret){
        $otp=$_POST['twofa_otp'] ?? '';
        if(!totp_verify($postedSecret, $otp)) redirect_to('index.php?page=settings&err='.urlencode('Code OTP incorrect. Scanne le QR code puis entre le code Google Authenticator.').'#security-section');
    }
    $secret=$postedSecret;
}else{
    $secret='';
}

foreach($users as &$u){
    if((int)($u['id']??0)===(int)($cu['id']??0)){
        // Store 2FA only on this user record. Never save it into app/global settings.
        $u['twofa_enabled']=$enable2fa;
        $u['twofa_secret']=$secret;
        $_SESSION['user']=$u;
        break;
    }
}
unset($u);
data_write('users',$users);
audit_log('2fa_setting_changed','Authenticator 2FA for user '.($cu['username']??$cu['id']??'current').' is '.($enable2fa?'enabled':'disabled'));
redirect_to('index.php?page=settings&ok=1#security-section');
