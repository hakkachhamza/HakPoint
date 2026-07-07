<?php
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf();
    if(isset($_POST['twofa_code'])){
        if(verify_2fa_code($_POST['twofa_code']??'')){
            $afterLogin = password_needs_change() ? 'index.php?page=settings&force_password=1' : 'index.php?page=dashboard';
            redirect_to($afterLogin);
        }
        else $err='Code authenticator incorrect ou expiré';
    }else{
        $res=login_attempt($_POST['login']??'', $_POST['password']??'');
        if($res===true){
            $afterLogin = password_needs_change() ? 'index.php?page=settings&force_password=1' : 'index.php?page=dashboard';
            redirect_to($afterLogin);
        }
        elseif($res==='2fa') redirect_to('index.php?page=login&twofa=1');
        else $err='Identifiant/email ou mot de passe incorrect';
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion - Global Energie</title>
    <link rel="icon" href="assets/images/global-energie-icon.png">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-shell">
        <section class="auth-hero">
            <div class="auth-brand">
                <img class="auth-logo-img" src="assets/images/global-energie-logo.png" alt="Global Energie">
            </div>

            <h1>Pilotez votre activité avec Global Energie.</h1>
            <p>Connect • Manage • Grow — gestion commerciale claire et professionnelle.</p>
        </section>

        <section class="auth-card-wrap">
            <form class="auth-card" method="post" autocomplete="on">
                <?=csrf_field()?>
                <h2>Connexion</h2>
                <p class="auth-subtitle">Accédez à votre espace administrateur.</p>

                <?php if($err): ?>
                    <div class="auth-alert"><?= e($err) ?></div>
                <?php endif; ?>

                <?php if(isset($_GET['twofa'])): ?>
                    <label for="twofa_code">Code 2FA de Google Authenticator <span>*</span></label>
                    <input id="twofa_code" name="twofa_code" type="text" required maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="Code à 6 chiffres" autocomplete="one-time-code">
                    <button type="submit" class="auth-submit">Valider le code</button>
                <?php else: ?>
                    <label for="tenant_code">Code entreprise</label>
                    <input id="tenant_code" name="tenant_code" type="text" value="<?= e($_POST['tenant_code'] ?? (getenv('GE_DEFAULT_TENANT_SLUG') ?: 'global-energie')) ?>" autocomplete="organization" placeholder="global-energie">

                    <label for="login">Email ou identifiant <span>*</span></label>
                    <input id="login" name="login" type="text" required value="<?= e($_POST['login'] ?? '') ?>" autocomplete="username" placeholder="admin">

                    <label for="password">Mot de passe <span>*</span></label>
                    <input id="password" name="password" type="password" required autocomplete="current-password" placeholder="admin1234">

                    <div class="auth-options">
                        <label class="remember"><input type="checkbox" name="remember" value="1"><span>Se souvenir de moi</span></label>
                        <a href="index.php?page=forgot_password">Mot de passe oublié ?</a>
                    </div>
                    <button type="submit" class="auth-submit">Connexion</button>
                    <p class="auth-switch">No company account yet? <a href="index.php?page=register">Register company</a></p>
                <?php endif; ?>
            </form>
        </section>
    </main>
</body>
</html>
