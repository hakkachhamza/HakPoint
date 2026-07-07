<?php
$title='API & Documentation';
$settings=app_settings();
$apiKey=ge_api_key();
$settings=app_settings();
$baseUrl=ge_api_base_url();
$pingUrl=$baseUrl.'/api.php?action=ping';
$statusUrl=$baseUrl.'/api.php?action=status';
$healthUrl=$baseUrl.'/api.php?action=health';
$summaryUrl=$baseUrl.'/api.php?action=summary';
$productsUrl=$baseUrl.'/api.php?action=products&page=1&per_page=50';
$ordersUrl=$baseUrl.'/api.php?action=orders&page=1&per_page=50';
$publicProductsUrl=$baseUrl.'/api.php?action=site_products';
$counts=[
  'Produits'=>ge_api_count('products'),
  'Tiers'=>ge_api_count('tiers'),
  'Devis'=>ge_api_count('quotes'),
  'Commandes'=>ge_api_count('orders'),
  'Factures'=>ge_api_count('invoices'),
  'Entrepôts'=>ge_api_count('warehouses'),
  'Expéditions'=>ge_api_count('expeditions'),
  'Réceptions'=>ge_api_count('receptions'),
  'Utilisateurs'=>ge_api_count('users')
];
$enabled=!empty($settings['api_enabled']);
include __DIR__.'/../layouts/header.php';
?>
<div class="api-page">
  <?php if(isset($_GET['ok'])): ?><div class="api-alert ok"><i class="fa-solid fa-circle-check"></i> Modification API enregistrée.</div><?php endif; ?>
  <?php if(isset($_GET['err'])): ?><div class="api-alert err"><i class="fa-solid fa-triangle-exclamation"></i> <?=e($_GET['err'])?></div><?php endif; ?>

  <section class="api-hero" id="api-status">
    <div class="api-hero-copy">
      <div class="api-eyebrow"><i class="fa-solid fa-code"></i> GLOBAL ENERGIE API</div>
      <h1>Documentation & test de connexion</h1>
      <p>Cette section explique comment le projet fonctionne et donne une API simple à mettre sur votre site pour vérifier si GLOBAL ENERGIE est connecté.</p>
      <div class="api-actions">
        <button type="button" class="btn primary" id="apiRunTest"><i class="fa-solid fa-plug-circle-check"></i> Tester la connexion</button>
        <a class="btn secondary" href="#api-docs"><i class="fa-solid fa-book-open"></i> Voir la documentation</a>
      </div>
    </div>
    <div class="api-status-card <?= $enabled ? 'is-on' : 'is-off' ?>">
      <span class="api-dot"></span>
      <b><?= $enabled ? 'API activée' : 'API désactivée' ?></b>
      <small>Base URL</small>
      <code><?=e($baseUrl)?></code>
      <small>Dernière régénération</small>
      <code><?=e($settings['api_last_regenerated'] ?: 'Non définie')?></code>
    </div>
  </section>

  <section class="api-grid-main">
    <div class="api-panel api-key-panel">
      <div class="api-panel-head"><span><i class="fa-solid fa-key"></i></span><div><h2>Clé API</h2><p>Utilisez cette clé uniquement dans vos sites ou scripts autorisés.</p></div></div>
      <div class="api-key-box"><code id="apiKeyText"><?=e($apiKey)?></code><button type="button" data-copy="#apiKeyText"><i class="fa-solid fa-copy"></i> Copier</button></div>
      <div class="api-form-row">
        <form method="post" action="index.php?page=api_save" onsubmit="return confirm('Régénérer la clé API ? Les anciens liens API ne marcheront plus.');">
          <?=csrf_field()?>
          <input type="hidden" name="api_action" value="regenerate">
          <button class="btn warning" type="submit"><i class="fa-solid fa-rotate"></i> Régénérer la clé</button>
        </form>
        <form method="post" action="index.php?page=api_save">
          <?=csrf_field()?>
          <input type="hidden" name="api_action" value="toggle">
          <input type="hidden" name="api_enabled" value="<?= $enabled ? '0' : '1' ?>">
          <button class="btn <?= $enabled ? 'danger ghost' : 'success' ?>" type="submit"><i class="fa-solid <?= $enabled ? 'fa-toggle-off' : 'fa-toggle-on' ?>"></i> <?= $enabled ? 'Désactiver API' : 'Activer API' ?></button>
        </form>
      </div>
      <form method="post" action="index.php?page=api_save" class="api-origin-form">
        <?=csrf_field()?>
        <input type="hidden" name="api_action" value="origins">
        <label>Allowed Origins / CORS</label>
        <div><input name="api_allowed_origins" value="<?=e($settings['api_allowed_origins'] ?? '')?>" placeholder="https://votre-site.com"><button class="btn secondary" type="submit">Enregistrer</button></div>
        <small>Laissez vide pour une utilisation serveur/same-origin. Pour un site externe, ajoutez uniquement le domaine autorisé, par exemple <b>https://votre-site.com</b>.</small>
      </form>
    </div>

    <div class="api-panel api-test-panel">
      <div class="api-panel-head"><span><i class="fa-solid fa-signal"></i></span><div><h2>Test direct</h2><p>Résultat de l'endpoint <b>ping</b>.</p></div></div>
      <pre id="apiTestOutput" class="api-output">Cliquez sur “Tester la connexion”.</pre>
    </div>
  </section>

  <section class="api-kpis">
    <?php foreach($counts as $label=>$value): ?>
      <div class="api-kpi"><small><?=e($label)?></small><b><?=e($value)?></b></div>
    <?php endforeach; ?>
  </section>

  <section class="api-panel api-docs" id="api-docs">
    <div class="api-panel-head"><span><i class="fa-solid fa-diagram-project"></i></span><div><h2>Comment le projet fonctionne</h2><p>Résumé technique simple pour comprendre les connexions.</p></div></div>
    <div class="api-doc-grid">
      <article><h3>1. Authentification</h3><p>L'utilisateur se connecte avec email/username + mot de passe. Les permissions contrôlent l'accès aux sections.</p></article>
      <article><h3>2. Modules</h3><p>Les sections principales sont Produits, Tiers, Devis, Commandes, Factures, Stock, Expéditions, Réceptions, Utilisateurs et Paramètres.</p></article>
      <article><h3>3. Base de données</h3><p>Les données applicatives sont stockées dans MySQL, dans des tables MySQL dédiées <b>ge_products</b>, <b>ge_tiers</b>, <b>ge_quotes</b>, etc. Une table par module.</p></article>
      <article><h3>4. Documents PDF</h3><p>Les devis, commandes, factures, expéditions et réceptions peuvent générer des PDF depuis les informations enregistrées.</p></article>
      <article><h3>5. API</h3><p>Le fichier <b>api.php</b> retourne du JSON sécurisé par clé API. Il permet ping, health, summary, produits, commandes et création de commande API.</p></article>
      <article><h3>6. Connexion externe</h3><p>Votre site externe peut utiliser l'endpoint public <b>site_products</b>. Gardez la clé API privée uniquement côté serveur.</p></article>
    </div>
  </section>

  <section class="api-panel" id="api-endpoints">
    <div class="api-panel-head"><span><i class="fa-solid fa-link"></i></span><div><h2>Endpoints disponibles</h2><p>Copiez ces endpoints. La clé API doit être envoyée dans le header <b>X-API-Key</b> ou <b>Authorization: Bearer</b>, pas dans l’URL.</p></div></div>
    <div class="api-endpoints">
      <div><b>Ping connexion</b><code id="pingUrl"><?=e($pingUrl)?></code><button type="button" data-copy="#pingUrl">Copier</button></div>
      <div><b>Status modules</b><code id="statusUrl"><?=e($statusUrl)?></code><button type="button" data-copy="#statusUrl">Copier</button></div>
      <div><b>Health database</b><code id="healthUrl"><?=e($healthUrl)?></code><button type="button" data-copy="#healthUrl">Copier</button></div>
      <div><b>Summary totals</b><code id="summaryUrl"><?=e($summaryUrl)?></code><button type="button" data-copy="#summaryUrl">Copier</button></div>
      <div><b>Produits paginés</b><code id="productsUrl"><?=e($productsUrl)?></code><button type="button" data-copy="#productsUrl">Copier</button></div>
      <div><b>Commandes paginées</b><code id="ordersUrl"><?=e($ordersUrl)?></code><button type="button" data-copy="#ordersUrl">Copier</button></div>
      <div><b>Produits site public</b><code id="publicProductsUrl"><?=e($publicProductsUrl)?></code><button type="button" data-copy="#publicProductsUrl">Copier</button></div>
    </div>
  </section>
  <section class="api-panel" id="api-embed">
    <div class="api-panel-head"><span><i class="fa-solid fa-window-restore"></i></span><div><h2>Code public à mettre sur votre site</h2><p>Ce bloc n'expose pas la clé API. Il utilise seulement l'endpoint public des produits visibles.</p></div></div>
    <?php $embed='<div id="global-energie-products">Chargement des produits GLOBAL ENERGIE...</div>
<script>
fetch("'.$publicProductsUrl.'", {cache:"no-store"})
  .then(function(response){ return response.json(); })
  .then(function(data){
    var box = document.getElementById("global-energie-products");
    if(!data.success){ box.innerHTML = "Produits non disponibles"; return; }
    box.innerHTML = "✅ GLOBAL ENERGIE connecté — " + data.count + " produit(s) public(s)";
  })
  .catch(function(){
    document.getElementById("global-energie-products").innerHTML = "❌ API publique non accessible";
  });
</script>'; ?>
    <div class="api-code-wrap"><pre id="embedCode"><?=e($embed)?></pre><button type="button" data-copy="#embedCode"><i class="fa-solid fa-copy"></i> Copier le code</button></div>
    <p class="muted-info"><b>Important :</b> n'utilisez jamais la clé API privée dans JavaScript public. Pour les endpoints protégés, créez un proxy côté serveur.</p>
  </section>

  <section class="api-panel api-json-doc">
    <div class="api-panel-head"><span><i class="fa-solid fa-code-branch"></i></span><div><h2>Exemple réponse JSON</h2><p>Réponse attendue depuis <b>api.php?action=ping</b>.</p></div></div>
<pre>{
  "success": true,
  "connected": true,
  "app": "GLOBAL ENERGIE EVENTS",
  "message": "API connected successfully.",
  "timestamp": "<?=date('c')?>"
}</pre>
  </section>
</div>
<script>
(function(){
  const testUrl = <?=json_encode($pingUrl, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>;
  const apiKey = <?=json_encode($apiKey, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)?>;
  const out = document.getElementById('apiTestOutput');
  const btn = document.getElementById('apiRunTest');
  function runTest(){
    if(!out) return;
    out.textContent='Connexion en cours...';
    fetch(testUrl, {cache:'no-store', headers:{'X-API-Key': apiKey}})
      .then(r=>r.json())
      .then(data=>{ out.textContent=JSON.stringify(data, null, 2); })
      .catch(err=>{ out.textContent='Erreur API: '+err.message; });
  }
  if(btn) btn.addEventListener('click', runTest);
  document.querySelectorAll('[data-copy]').forEach(function(button){
    button.addEventListener('click', function(){
      const target=document.querySelector(this.dataset.copy);
      if(!target) return;
      const text=target.innerText || target.textContent || '';
      navigator.clipboard.writeText(text).then(()=>{
        const old=this.innerHTML;
        this.innerHTML='<i class="fa-solid fa-check"></i> Copié';
        setTimeout(()=>this.innerHTML=old,1200);
      });
    });
  });
})();
</script>
<?php include __DIR__.'/../layouts/footer.php'; ?>
