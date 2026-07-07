<?php
$title='Paramètres';
$settings=app_settings();
$cu=current_user();
$displayName=ge_user_full_name($cu);
$account=$cu['email'] ?? ($cu['username'] ?? 'admin@hakpoint.ma');
$username=trim((string)($cu['username'] ?? ''));
$email=trim((string)($cu['email'] ?? ''));
$roleName=trim((string)($cu['role'] ?? 'Utilisateur'));
$status=trim((string)($cu['status'] ?? 'Actif'));
$tenant=trim((string)($cu['tenant_slug'] ?? (function_exists('ge_current_tenant_slug') ? ge_current_tenant_slug() : '')));
$userTwofaEnabled=!empty($cu['twofa_enabled']) && !empty($cu['twofa_secret']);
$setupSecret=$userTwofaEnabled ? (string)$cu['twofa_secret'] : totp_generate_secret();
$setupUri=totp_uri($setupSecret, $account);
$qrUrl='https://api.qrserver.com/v1/create-qr-code/?size=210x210&data='.rawurlencode($setupUri);
$avatar=user_avatar_src($cu);
$lang=$settings['language'] ?? 'fr';
include __DIR__.'/../layouts/header.php';
?>
<div class="settings-page pro-settings refined-settings">
  <?php if(isset($_GET['force_password']) || password_needs_change($cu)): ?><div class="email-status err">Sécurité: changez le mot de passe initial avant d’utiliser le système en production.</div><?php endif; ?>
  <?php if(isset($_GET['ok'])): ?><div class="email-status ok">Paramètres enregistrés avec succès.</div><?php endif; ?>
  <?php if(isset($_GET['err'])): ?><div class="email-status err"><?=e($_GET['err'])?></div><?php endif; ?>

  <div class="settings-top-clean">
    <div>
      <span class="settings-eyebrow">Global Energie</span>
      <h2>Paramètres du compte</h2>
      <p>Gérez uniquement les éléments essentiels : profil, langue et sécurité.</p>
    </div>
    <div class="settings-top-actions">
      <a href="#profile-section"><i class="fa-solid fa-circle-user"></i> Profil</a>
      <a href="#language-section"><i class="fa-solid fa-language"></i> Langue</a>
      <a href="#security-section"><i class="fa-solid fa-shield-halved"></i> Sécurité</a>
    </div>
  </div>

  <section class="settings-section-card" id="profile-section">
    <div class="settings-section-head">
      <span class="settings-section-icon"><i class="fa-solid fa-circle-user"></i></span>
      <div>
        <h3>Profil</h3>
        <p>Photo de profil, identité du compte et informations de connexion.</p>
      </div>
      <span class="settings-status-pill"><i class="fa-solid fa-circle-check"></i> <?=e($status ?: 'Actif')?></span>
    </div>

    <div class="settings-profile-layout">
      <form method="post" action="index.php?page=settings_profile_save" enctype="multipart/form-data" class="settings-form profile-upload-panel"><?=csrf_field()?>
        <div class="profile-upload-box refined">
          <div class="profile-big-avatar refined-avatar">
            <?php if($avatar): ?><img src="<?=e($avatar)?>" alt="Profile"><?php else: ?><i class="fa-solid fa-user"></i><?php endif; ?>
          </div>
          <div class="profile-upload-copy">
            <label>Photo de profil</label>
            <input type="file" name="profile_picture" accept="image/png,image/jpeg,image/webp">
            <small>Formats acceptés : JPG, PNG, WEBP. Taille max 2 MB. Utilisez une photo carrée pour un meilleur rendu.</small>
            <div class="profile-actions">
              <button class="btn primary" type="submit" name="profile_action" value="upload"><i class="fa-solid fa-upload"></i> Enregistrer profil</button>
              <?php if($avatar): ?><button class="btn danger ghost" type="submit" name="profile_action" value="remove" onclick="return confirm('Supprimer la photo de profil ?')"><i class="fa-solid fa-trash"></i> Supprimer la photo</button><?php endif; ?>
            </div>
          </div>
        </div>
      </form>

      <div class="account-info-panel">
        <div class="account-info-title"><i class="fa-solid fa-id-card"></i> Informations du compte</div>
        <div class="account-info-grid">
          <div><span>Nom affiché</span><b><?=e($displayName)?></b></div>
          <div><span>Nom utilisateur</span><b><?=e($username ?: '—')?></b></div>
          <div><span>Email</span><b><?=e($email ?: '—')?></b></div>
          <div><span>Rôle</span><b><?=e($roleName ?: 'Utilisateur')?></b></div>
          <div><span>Espace</span><b><?=e($tenant ?: 'Global Energie')?></b></div>
          <div><span>2FA</span><b class="<?= $userTwofaEnabled ? 'good' : 'muted' ?>"><?= $userTwofaEnabled ? 'Activée' : 'Désactivée' ?></b></div>
        </div>
      </div>
    </div>
  </section>

  <section class="settings-section-card" id="language-section">
    <div class="settings-section-head">
      <span class="settings-section-icon"><i class="fa-solid fa-language"></i></span>
      <div>
        <h3>Langue</h3>
        <p>Choisissez la langue principale de l’interface.</p>
      </div>
      <span class="settings-status-pill"><i class="fa-solid fa-globe"></i> <?=strtoupper(e($lang))?></span>
    </div>

    <form method="post" action="index.php?page=settings_save_language" class="settings-form language-pro-form"><?=csrf_field()?>
      <div class="language-choice-grid">
        <label class="language-option <?= $lang==='fr'?'selected':'' ?>">
          <input type="radio" name="language" value="fr" <?= $lang==='fr'?'checked':'' ?>>
          <span>FR</span>
          <strong>Français</strong>
          <small>Interface complète en français.</small>
        </label>
        <label class="language-option <?= $lang==='en'?'selected':'' ?>">
          <input type="radio" name="language" value="en" <?= $lang==='en'?'checked':'' ?>>
          <span>EN</span>
          <strong>English</strong>
          <small>Professional English interface.</small>
        </label>
        <label class="language-option <?= $lang==='ar'?'selected':'' ?>">
          <input type="radio" name="language" value="ar" <?= $lang==='ar'?'checked':'' ?>>
          <span>AR</span>
          <strong>العربية</strong>
          <small>واجهة عربية مع اتجاه RTL.</small>
        </label>
      </div>
      <div class="settings-save-row">
        <button class="btn primary" type="submit"><i class="fa-solid fa-floppy-disk"></i> Enregistrer la langue</button>
        <small>Le changement s’applique automatiquement après l’enregistrement.</small>
      </div>
    </form>
  </section>

  <section class="settings-section-card" id="security-section">
    <div class="settings-section-head">
      <span class="settings-section-icon"><i class="fa-solid fa-shield-halved"></i></span>
      <div>
        <h3>Sécurité</h3>
        <p>Mot de passe, double authentification et niveau de protection du compte.</p>
      </div>
      <span class="settings-status-pill <?= $userTwofaEnabled ? 'success' : '' ?>"><i class="fa-solid fa-key"></i> <?= $userTwofaEnabled ? '2FA activée' : '2FA optionnelle' ?></span>
    </div>

    <form method="post" action="index.php?page=settings_security_save" class="settings-form security-form refined-security-form"><?=csrf_field()?>
      <div class="security-layout">
        <div class="security-password-card">
          <h4><i class="fa-solid fa-lock"></i> Changer le mot de passe</h4>
          <div class="settings-two-cols">
            <div><label>Mot de passe actuel</label><input type="password" name="current_password" autocomplete="current-password" placeholder="Mot de passe actuel"></div>
            <div><label>Nouveau mot de passe</label><input type="password" name="new_password" autocomplete="new-password" placeholder="Min. 8 caractères"></div>
            <div><label>Confirmer le nouveau mot de passe</label><input type="password" name="confirm_password" autocomplete="new-password" placeholder="Répéter le mot de passe"></div>
            <div><label>Double authentification 2FA</label><select name="twofa_enabled" id="twofaSelect"><option value="0" <?=!$userTwofaEnabled?'selected':''?>>Désactivé</option><option value="1" <?=$userTwofaEnabled?'selected':''?>>Activé avec Google Authenticator</option></select></div>
          </div>
          <div class="security-tips">
            <span><i class="fa-solid fa-check"></i> 8 caractères minimum</span>
            <span><i class="fa-solid fa-check"></i> Évitez le mot de passe par défaut</span>
            <span><i class="fa-solid fa-check"></i> Activez 2FA pour les admins</span>
          </div>
        </div>

        <div class="security-status-card">
          <h4><i class="fa-solid fa-user-shield"></i> État sécurité</h4>
          <div class="security-check-list">
            <div><span><i class="fa-solid fa-user-tie"></i> Rôle</span><b><?=e($roleName ?: 'Utilisateur')?></b></div>
            <div><span><i class="fa-solid fa-circle-check"></i> Statut</span><b><?=e($status ?: 'Actif')?></b></div>
            <div><span><i class="fa-solid fa-mobile-screen-button"></i> 2FA</span><b class="<?= $userTwofaEnabled ? 'good' : 'warning' ?>"><?= $userTwofaEnabled ? 'Activée' : 'Non activée' ?></b></div>
            <div><span><i class="fa-solid fa-envelope"></i> Compte</span><b><?=e($account)?></b></div>
          </div>
        </div>
      </div>

      <div id="twofaSetupBox" class="twofa-setup" style="display:<?=$userTwofaEnabled?'block':'none'?>">
        <div class="twofa-box professional refined-twofa">
          <div class="twofa-copy">
            <b>Scanner ce QR code avec Google Authenticator</b>
            <p>Nom affiché dans l'application : <b>Global Energie</b></p>
            <p class="muted">Compte : <?=e($account)?></p>
            <input type="hidden" name="twofa_secret" value="<?=e($setupSecret)?>">
            <label>Code OTP de l'application</label>
            <input type="text" name="twofa_otp" maxlength="6" inputmode="numeric" placeholder="123456">
            <small>Pour activer la 2FA, entrez le code à 6 chiffres après le scan.</small>
          </div>
          <div class="qr-frame"><img class="twofa-qr" src="<?=e($qrUrl)?>" alt="QR code Google Authenticator"></div>
        </div>
      </div>

      <div class="settings-save-row">
        <button class="btn primary" type="submit"><i class="fa-solid fa-lock"></i> Enregistrer sécurité</button>
        <small>Les modifications sensibles sont enregistrées uniquement pour votre compte.</small>
      </div>
    </form>
  </section>
</div>
<script>
(function(){
  var sel=document.getElementById('twofaSelect');
  var box=document.getElementById('twofaSetupBox');
  if(sel&&box){ sel.addEventListener('change', function(){ box.style.display=this.value==='1'?'block':'none'; }); }
})();
</script>
<?php include __DIR__.'/../layouts/footer.php'; ?>
