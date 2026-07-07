<?php
$token=(string)($_GET['token'] ?? $_POST['token'] ?? '');
$tokenHash=$token ? hash('sha256',$token) : '';
$err=''; $ok=false; $valid=false; $resetIndex=null; $resetRow=null;
$resets=data_read('password_resets', []);
foreach($resets as $i=>$r){
    if(($r['token_hash'] ?? '') === $tokenHash && empty($r['used']) && time() <= (int)($r['expires_at'] ?? 0)){
        $valid=true; $resetIndex=$i; $resetRow=$r; break;
    }
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    if(!$valid){ $err='Lien invalide ou expiré.'; }
    else{
        $p1=$_POST['password'] ?? ''; $p2=$_POST['password_confirm'] ?? '';
        if(strlen($p1)<8) $err='Le mot de passe doit contenir au moins 8 caractères.';
        elseif($p1!==$p2) $err='Les deux mots de passe ne sont pas identiques.';
        else{
            $users=data_read('users', []); $updated=false;
            foreach($users as $k=>$u){
                if((int)($u['id'] ?? 0)===(int)($resetRow['user_id'] ?? 0)){
                    $users[$k]['password']=password_hash($p1, PASSWORD_DEFAULT);
                    unset($users[$k]['plain_password'], $users[$k]['password_plain']);
                    $updated=true; break;
                }
            }
            if($updated){
                $resets[$resetIndex]['used']=true;
                $resets[$resetIndex]['used_at']=date('d/m/Y H:i');
                data_write('users',$users);
                data_write('password_resets',$resets);
                audit_log('password_reset_success','Password reset completed for '.$resetRow['email']);
                $ok=true; $valid=false;
            }else $err='Utilisateur introuvable.';
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Nouveau mot de passe - Global Energie</title>
  <link rel="icon" href="assets/images/global-energie-icon.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
  <main class="auth-shell">
    <section class="auth-hero"><div class="auth-brand"><img class="auth-logo-img" src="assets/images/global-energie-logo.png" alt="Global Energie"></div><h1>Créer un nouveau mot de passe.</h1><p>Le lien de sécurité expire après 1 heure.</p></section>
    <section class="auth-card-wrap">
      <form class="auth-card" method="post" autocomplete="off">
        <?=csrf_field()?>
        <h2>Nouveau mot de passe</h2>
        <?php if($err): ?><div class="auth-alert"><?=e($err)?></div><?php endif; ?>
        <?php if($ok): ?>
          <div class="auth-alert success">Mot de passe changé avec succès.</div>
          <a class="auth-submit auth-link-button" href="index.php?page=login">Se connecter</a>
        <?php elseif(!$valid): ?>
          <div class="auth-alert">Lien invalide ou expiré.</div>
          <a class="auth-submit auth-link-button" href="index.php?page=forgot_password">Demander un nouveau lien</a>
        <?php else: ?>
          <input type="hidden" name="token" value="<?=e($token)?>">
          <label for="password">Nouveau mot de passe <span>*</span></label>
          <input id="password" name="password" type="password" required minlength="8">
          <label for="password_confirm">Confirmer le mot de passe <span>*</span></label>
          <input id="password_confirm" name="password_confirm" type="password" required minlength="8">
          <button type="submit" class="auth-submit">Changer le mot de passe</button>
        <?php endif; ?>
        <div class="auth-options only-link"><a href="index.php?page=login">Retour à la connexion</a></div>
      </form>
    </section>
  </main>
</body>
</html>
