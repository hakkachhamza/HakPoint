<?php
$title='hakpoint AI';
include __DIR__.'/../layouts/header.php';
$quick = [
  'كيفاش نخلق منتج؟',
  'Donne-moi le rapport aujourd’hui',
  'Give me invoice PDF for client Ahmed',
  'Vérifier le site',
  'Create product Batterie 200Ah price 1200 stock 5',
  'Créer client Ahmed email ahmed@test.com',
];
$aiCfg = app_config()['ai'] ?? [];
$geminiReady = !empty($aiCfg['gemini_api_key']);
?>
<div class="assistant-page">
  <section class="assistant-hero panel">
    <div class="assistant-hero-left">
      <div class="assistant-robot-mini" aria-hidden="true">
        <i class="fa-solid fa-robot"></i>
      </div>
      <div>
        <div class="ge-eyebrow"><i class="fa-solid fa-sparkles"></i> hakpoint AI</div>
        <h1>hakpoint AI</h1>
        <p><?= $geminiReady ? 'Connecté à Gemini avec accès au contexte ERP.' : 'Ajoutez GE_GEMINI_API_KEY pour activer les réponses Gemini.' ?></p>
      </div>
    </div>
    <span class="assistant-status <?= $geminiReady ? 'on' : 'off' ?>">
      <i class="fa-solid <?= $geminiReady ? 'fa-circle-check' : 'fa-circle-info' ?>"></i>
      <?= $geminiReady ? 'Gemini actif' : 'Gemini non configuré' ?>
    </span>
  </section>

  <section class="assistant-shell panel">
    <div class="assistant-chat" id="assistantChat" aria-live="polite">
      <div class="assistant-bubble bot">
        <div class="assistant-avatar"><i class="fa-solid fa-robot"></i></div>
        <div class="assistant-message">
          <b>hakpoint AI</b>
          <p>مرحبا / Bonjour / Hello 👋<br>Ask me anything about the ERP, reports, products, invoices, PDF, stock, clients, purchases, validations, or site checks.</p>
        </div>
      </div>
    </div>

    <div class="assistant-quick">
      <?php foreach($quick as $q): ?>
        <button type="button" data-assistant-quick="<?=e($q)?>"><?=e($q)?></button>
      <?php endforeach; ?>
    </div>

    <form id="assistantForm" class="assistant-input-row">
      <input type="text" id="assistantInput" placeholder="Ask hakpoint AI... / اسأل هنا... / Écrivez ici..." autocomplete="off">
      <button type="submit" class="btn primary"><i class="fa-solid fa-paper-plane"></i> Send</button>
    </form>
  </section>
</div>

<script src="assets/js/assistant.js"></script>
<?php include __DIR__.'/../layouts/footer.php'; ?>
