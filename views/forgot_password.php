<?php
$sent=false; $err='';
function ge_current_base_url(){
    $cfg=app_config();
    if(!empty($cfg['base_url'])) return rtrim($cfg['base_url'],'/');
    $https = function_exists('ge_is_https') ? ge_is_https() : ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443) || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https'));
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
    return $scheme.'://'.$host.($path && $path !== '/' ? $path : '');
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    $email=strtolower(trim($_POST['email'] ?? ''));
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $err='Adresse email invalide.';
    }else{
        $users=data_read('users', []); $found=null;
        foreach($users as $u){
            $active = ($u['status'] ?? 'Actif') !== 'Désactivé';
            if($active && strtolower($u['email'] ?? '') === $email){ $found=$u; break; }
        }
        if($found){
            $token=bin2hex(random_bytes(32));
            $resets=data_read('password_resets', []);
            $resets[]=[
                'id'=>next_id($resets),
                'user_id'=>(int)($found['id'] ?? 0),
                'email'=>$email,
                'token_hash'=>hash('sha256',$token),
                'expires_at'=>time()+3600,
                'used'=>false,
                'created_at'=>date('d/m/Y H:i')
            ];
            data_write('password_resets',$resets);
            $link=ge_current_base_url().'/index.php?page=reset_password&token='.urlencode($token);
            $name=trim(($found['firstname']??'').' '.($found['name']??'')) ?: ($found['username']??'');
            $msg="Bonjour ".$name.",\n\nVous avez demandé la réinitialisation de votre mot de passe Global Energie.\n\nCliquez sur ce lien pour créer un nouveau mot de passe :\n".$link."\n\nCe lien expire dans 1 heure. Si vous n'avez pas demandé cette action, ignorez cet email.\n\nGlobal Energie";
            $res=send_real_email($email, 'Réinitialisation mot de passe - Global Energie', $msg);
            audit_log($res['ok']?'password_reset_email_sent':'password_reset_email_failed', 'Password reset requested for existing account'.($res['ok']?'':' | '.($res['error']??'')));
            // Do not reveal whether the account exists or whether SMTP failed.
        }
        $sent=true;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mot de passe oublié - Global Energie</title>
  <link rel="icon" href="assets/images/global-energie-icon.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
  <main class="auth-shell">
    <section class="auth-hero"><div class="auth-brand"><img class="auth-logo-img" src="assets/images/global-energie-logo.png" alt="Global Energie"></div><h1>Réinitialiser votre mot de passe.</h1><p>Entrez l’email enregistré dans la base de données.</p></section>
    <section class="auth-card-wrap">
      <form class="auth-card" method="post" autocomplete="on">
        <?=csrf_field()?>
        <h2>Mot de passe oublié</h2>
        <p class="auth-subtitle">Si l’email existe, un lien de réinitialisation sera envoyé.</p>
        <?php if($err): ?><div class="auth-alert"><?=e($err)?></div><?php endif; ?>
        <?php if($sent): ?><div class="auth-alert success">Si cette adresse existe, un lien de réinitialisation sera envoyé.</div><?php endif; ?>
        <label for="email">Votre email <span>*</span></label>
        <input id="email" name="email" type="email" required placeholder="exemple@hakpoint.ma" value="<?=e($_POST['email'] ?? '')?>">
        <button type="submit" class="auth-submit">Envoyer le lien</button>
        <div class="auth-options only-link"><a href="index.php?page=login">Retour à la connexion</a></div>
      </form>
    </section>
  </main>
</body>
</html>
