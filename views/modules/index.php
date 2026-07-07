<?php
$title='Modules';
$modules=ge_modules_state();
include __DIR__.'/../layouts/header.php';
?>
<div class="erp-page">
  <?php if(isset($_GET['ok'])): ?><div class="email-status ok">Modules enregistrés.</div><?php endif; ?>
  <div class="erp-head"><div><h2><i class="fa-solid fa-sliders"></i> Modules</h2><p>Active seulement les sections dont la société a besoin. Les modules désactivés disparaissent du menu.</p></div></div>
  <section class="panel erp-card">
    <form method="post" action="index.php?page=modules_save" class="module-grid-form">
      <?=csrf_field()?>
      <div class="module-grid">
      <?php foreach(ge_default_modules() as $key=>$label): ?>
        <label class="module-tile">
          <input type="checkbox" name="modules[]" value="<?=e($key)?>" <?=!empty($modules[$key])?'checked':''?>>
          <span><b><?=e($label)?></b><small><?=e($key)?></small></span>
        </label>
      <?php endforeach; ?>
      </div>
      <div class="erp-actions"><button class="btn primary"><i class="fa-solid fa-floppy-disk"></i> Enregistrer modules</button></div>
    </form>
  </section>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
