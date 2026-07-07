<?php
$settings=app_settings();
$cu=current_user();
$companyName=trim((string)($settings['company_name'] ?? '')); if($companyName==='') $companyName=$cu['company_name'] ?? ge_default_tenant_name();
$companyCountry=trim((string)($settings['company_country'] ?? '')); if($companyCountry==='') $companyCountry=$cu['country'] ?? 'Maroc';
$companyCity=trim((string)($settings['company_city'] ?? '')); if($companyCity==='') $companyCity=$cu['city'] ?? '';
$companyPhone=trim((string)($settings['company_phone'] ?? '')); if($companyPhone==='') $companyPhone=$cu['phone'] ?? '';
$companyEmail=trim((string)($settings['company_email'] ?? '')); if($companyEmail==='') $companyEmail=$cu['email'] ?? '';
$pdfShowLogo = function_exists('ge_bool_setting') ? ge_bool_setting('pdf_show_logo', false) : !empty($settings['pdf_show_logo']);
$pdfShowSender = function_exists('ge_bool_setting') ? ge_bool_setting('pdf_show_sender_box', false) : !empty($settings['pdf_show_sender_box']);
$pdfShowPayment = function_exists('ge_bool_setting') ? ge_bool_setting('pdf_show_payment_block', false) : !empty($settings['pdf_show_payment_block']);
$pdfShowFooter = function_exists('ge_bool_setting') ? ge_bool_setting('pdf_show_footer', false) : !empty($settings['pdf_show_footer']);
$pdfShowSignature = function_exists('ge_bool_setting') ? ge_bool_setting('pdf_show_signature', false) : !empty($settings['pdf_show_signature']);
$forceWizard = function_exists('ge_should_show_onboarding') && ge_should_show_onboarding();
$showProductOnly = !$forceWizard && function_exists('ge_should_show_first_product_prompt') && ge_should_show_first_product_prompt();
?>
<style id="ge-onboarding-critical-css">
body.modal-lock{overflow:hidden!important}
.ge-onboarding-overlay{display:none!important;position:fixed!important;inset:0!important;z-index:2147483000!important;background:rgba(0,0,0,.35)!important;align-items:center!important;justify-content:center!important;padding:18px!important;overflow:auto!important;box-sizing:border-box!important;font-family:Arial,Helvetica,sans-serif!important;color:#111827!important}
.ge-onboarding-overlay.is-open{display:flex!important}
.ge-onboarding-modal,.ge-product-modal{width:min(1060px,calc(100vw - 36px))!important;max-height:calc(100vh - 36px)!important;overflow:auto!important;background:#fff!important;border-radius:0!important;border:1px solid #111827!important;box-shadow:none!important;padding:18px!important;position:relative!important;box-sizing:border-box!important;margin:auto!important}
.ge-product-modal{width:min(720px,calc(100vw - 36px))!important}
.ge-onboarding-close{position:absolute!important;right:10px!important;top:10px!important;border:1px solid #111827!important;background:#fff!important;color:#111827!important;width:32px!important;height:32px!important;border-radius:0!important;font-size:22px!important;line-height:1!important;cursor:pointer!important;display:grid!important;place-items:center!important;z-index:2!important}
.ge-steps-head{display:block!important;margin-bottom:14px!important;padding-right:0!important;border-bottom:1px solid #d1d5db!important;padding-bottom:12px!important}.ge-steps-head h2{margin:0 0 4px!important;color:#111827!important;font-size:22px!important;line-height:1.2!important}.ge-steps-head p{margin:0!important;color:#4b5563!important;font-size:13px!important}
.ge-step-pills{display:none!important}
.ge-step{display:none!important}.ge-step.active{display:block!important}.ge-step[hidden]{display:none!important}.ge-step-title{display:flex!important;align-items:flex-start!important;gap:10px!important;margin:8px 0 14px!important}.ge-step-title.centered{justify-content:center!important;text-align:center!important;flex-direction:column!important;align-items:center!important}.ge-step-title i{width:34px!important;height:34px!important;border-radius:0!important;display:grid!important;place-items:center!important;background:#fff!important;color:#15803d!important;font-size:16px!important;border:1px solid #15803d!important;flex:0 0 auto!important}.ge-step-title h3{margin:0!important;color:#111827!important;font-size:18px!important;line-height:1.2!important}.ge-step-title p{margin:3px 0 0!important;color:#4b5563!important;font-size:13px!important}
.ge-onboarding-grid{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr))!important;gap:10px!important;align-items:start!important}.ge-onboarding-grid.compact{gap:10px!important}.ge-onboarding-grid label{display:flex!important;flex-direction:column!important;gap:5px!important;color:#111827!important;font-weight:700!important;font-size:13px!important;line-height:1.25!important;margin:0!important}.ge-onboarding-grid label.wide{grid-column:1/-1!important}.ge-onboarding-grid input,.ge-onboarding-grid select,.ge-onboarding-grid textarea{width:100%!important;max-width:100%!important;box-sizing:border-box!important;border:1px solid #6b7280!important;border-radius:0!important;background:#fff!important;padding:9px 10px!important;min-height:40px!important;font:inherit!important;font-weight:500!important;color:#111827!important;outline:none!important;margin:0!important}.ge-onboarding-grid textarea{resize:vertical!important;min-height:76px!important}.ge-onboarding-grid input:focus,.ge-onboarding-grid select:focus,.ge-onboarding-grid textarea:focus{border-color:#15803d!important;box-shadow:none!important}.ge-check{display:flex!important;flex-direction:row!important;align-items:center!important;gap:8px!important;border:1px solid #d1d5db!important;padding:9px 10px!important;min-height:40px!important;background:#fff!important}.ge-check input{width:16px!important;min-height:16px!important;height:16px!important;padding:0!important;margin:0!important;accent-color:#15803d!important}
.ge-pdf-layout{display:grid!important;grid-template-columns:.9fr 1.1fr!important;gap:16px!important;align-items:start!important}.ge-pdf-preview{background:#fff!important;border:1px solid #111827!important;border-radius:0!important;padding:10px!important;box-sizing:border-box!important}.ge-pdf-paper{background:#fff!important;min-height:520px!important;border:1px solid #9ca3af!important;box-shadow:none!important;padding:20px!important;color:#111827!important;box-sizing:border-box!important}.ge-pdf-top{display:flex!important;justify-content:space-between!important;gap:14px!important;align-items:flex-start!important;border-bottom:1px solid #d1d5db!important;padding-bottom:12px!important}.ge-pdf-logo{width:170px!important;height:54px!important;border:1px solid #9ca3af!important;border-radius:0!important;display:grid!important;place-items:center!important;overflow:hidden!important;background:#fff!important;color:#4b5563!important}.ge-pdf-logo img{max-width:100%!important;max-height:100%!important;object-fit:contain!important}.ge-pdf-doc-title{text-align:right!important;display:flex!important;flex-direction:column!important;gap:3px!important}.ge-pdf-doc-title b{font-size:20px!important}.ge-pdf-doc-title span{color:#4b5563!important}.ge-pdf-sub{text-align:left!important;margin:10px 0 12px!important;color:#4b5563!important;font-size:12px!important;white-space:pre-wrap!important}.ge-pdf-boxes{display:grid!important;grid-template-columns:1fr 1fr!important;gap:12px!important;margin:14px 0!important}.ge-pdf-boxes>div{border:1px solid #9ca3af!important;border-radius:0!important;min-height:76px!important;padding:10px!important;display:flex!important;flex-direction:column!important;gap:4px!important;box-sizing:border-box!important;white-space:pre-wrap!important}.ge-pdf-boxes small{color:#4b5563!important}.ge-pdf-table{width:100%!important;border-collapse:collapse!important;font-size:12px!important;margin-top:14px!important}.ge-pdf-table th,.ge-pdf-table td{border:1px solid #9ca3af!important;padding:8px!important;text-align:left!important}.ge-pdf-table th{background:#fff!important}.ge-pdf-payment,.ge-pdf-footer{border:1px solid #9ca3af!important;border-radius:0!important;margin-top:12px!important;padding:8px!important;font-size:11px!important;white-space:pre-wrap!important;color:#111827!important}.ge-pdf-total{width:min(250px,100%)!important;margin:16px 0 0 auto!important;display:grid!important;grid-template-columns:1fr auto!important;gap:7px!important;border:1px solid #9ca3af!important;border-radius:0!important;padding:10px!important}.ge-pdf-total span{color:#374151!important}.ge-pdf-signature{border:1px solid #9ca3af!important;width:260px!important;height:52px!important;margin:12px 0 0 auto!important;font-size:10px!important;padding:8px!important;box-sizing:border-box!important;color:#374151!important}.is-hidden{display:none!important}.ge-pro-card{width:min(520px,100%)!important;margin:20px auto!important;background:#fff!important;border:1px solid #111827!important;border-radius:0!important;padding:18px!important;display:flex!important;justify-content:space-between!important;gap:14px!important;align-items:center!important;box-sizing:border-box!important}.ge-pro-card b{display:block!important;font-size:20px!important;color:#111827!important}.ge-pro-card span{display:block!important;color:#4b5563!important;margin-top:4px!important}.ge-success-step{text-align:center!important;padding:34px 10px!important}.ge-success-circle{width:82px!important;height:82px!important;border-radius:999px!important;margin:0 auto 16px!important;background:#16a34a!important;color:#fff!important;display:grid!important;place-items:center!important;font-size:38px!important;box-shadow:none!important}.ge-success-step h3{font-size:22px!important;margin:0 0 8px!important;color:#111827!important}.ge-success-step p{color:#4b5563!important;margin:0!important}.ge-onboarding-actions{display:flex!important;justify-content:flex-end!important;gap:8px!important;border-top:1px solid #d1d5db!important;margin-top:16px!important;padding-top:12px!important}.ge-primary,.ge-secondary{border:1px solid #15803d!important;border-radius:0!important;min-height:40px!important;padding:0 16px!important;font-weight:800!important;cursor:pointer!important;font:inherit!important}.ge-primary{background:#15803d!important;color:#fff!important}.ge-primary:hover{background:#166534!important}.ge-secondary{background:#fff!important;color:#15803d!important}.ge-secondary:hover{background:#f0fdf4!important}.ge-onboarding-error{background:#fff!important;border:1px solid #dc2626!important;color:#991b1b!important;border-radius:0!important;padding:10px 12px!important;margin-top:12px!important;font-weight:700!important}
@media(max-width:860px){.ge-onboarding-modal,.ge-product-modal{padding:14px!important;width:calc(100vw - 20px)!important;max-height:calc(100vh - 20px)!important}.ge-onboarding-overlay{padding:10px!important}.ge-steps-head,.ge-pro-card{flex-direction:column!important}.ge-pdf-layout,.ge-onboarding-grid{grid-template-columns:1fr!important}.ge-pdf-paper{padding:14px!important;min-height:420px!important}.ge-pdf-boxes{grid-template-columns:1fr!important}.ge-onboarding-actions{justify-content:stretch!important;flex-wrap:wrap!important}.ge-primary,.ge-secondary{flex:1 1 140px!important}.ge-onboarding-grid label.wide{grid-column:auto!important}}
/* v62: setup buttons without boxes - green text only */
/* v63: removed progress pills/back/finish; only Skip/Next until success, Close on success */
.ge-onboarding-overlay button.ge-primary,
.ge-onboarding-overlay button.ge-secondary{
  background:transparent!important;
  border:0!important;
  box-shadow:none!important;
  border-radius:0!important;
  color:#15803d!important;
  min-height:0!important;
  height:auto!important;
  width:auto!important;
  padding:7px 8px!important;
  font-weight:800!important;
  line-height:1.25!important;
  display:inline-flex!important;
  align-items:center!important;
  justify-content:center!important;
}
.ge-onboarding-overlay button.ge-primary:hover,
.ge-onboarding-overlay button.ge-secondary:hover{
  background:transparent!important;
  color:#166534!important;
  text-decoration:underline!important;
}
.ge-onboarding-overlay button.ge-onboarding-close{
  background:transparent!important;
  border:0!important;
  box-shadow:none!important;
  color:#111827!important;
  width:auto!important;
  height:auto!important;
  min-height:0!important;
  padding:2px 6px!important;
}
.ge-onboarding-overlay button.ge-onboarding-close:hover{
  background:transparent!important;
  color:#15803d!important;
}
.ge-onboarding-actions{gap:14px!important;align-items:center!important}
@media(max-width:860px){.ge-primary,.ge-secondary{flex:0 0 auto!important}}
</style>
<div class="ge-onboarding-overlay <?= $forceWizard ? 'is-open' : '' ?>" id="geOnboardingOverlay" aria-hidden="<?= $forceWizard ? 'false' : 'true' ?>">
  <div class="ge-onboarding-modal" role="dialog" aria-modal="true" aria-labelledby="geOnboardingTitle">
    <form id="geOnboardingForm" method="post" action="index.php?page=onboarding_save" enctype="multipart/form-data">
      <?=csrf_field()?>
      <input type="hidden" name="onboarding_action" value="save_all">
      <input type="hidden" name="pro_choice" id="geProChoice" value="skip">
      <div class="ge-steps-head">
        <div>
          <h2 id="geOnboardingTitle">Configure your company panel</h2>
          <p>Simple setup. You can skip any step and change it later.</p>
        </div>
      </div>

      <section class="ge-step active" data-step="0">
        <div class="ge-step-title"><i class="fa-solid fa-building"></i><div><h3>Company information</h3><p>Put only the company information you want to show in documents.</p></div></div>
        <div class="ge-onboarding-grid">
          <label>Company name<input name="company_name" value="<?=e($companyName)?>" placeholder="Company name"></label>
          <label>Company email<input name="company_email" type="email" value="<?=e($companyEmail)?>" placeholder="contact@company.com"></label>
          <label>Phone<input name="company_phone" value="<?=e($companyPhone)?>" placeholder="+212..."></label>
          <label>Address<input name="company_address" value="<?=e($settings['company_address'] ?? '')?>" placeholder="Address / siège social"></label>
          <label>City<input name="company_city" value="<?=e($companyCity)?>" placeholder="Casablanca"></label>
          <label>Pays<input name="company_country" value="<?=e($companyCountry)?>" placeholder="Maroc"></label>
          <label>Id. prof. 1 (R.C.)<input name="company_rc" value="<?=e($settings['company_rc'] ?? '')?>" placeholder="R.C."></label>
          <label>Id. prof. 2 (Patente)<input name="company_patente" value="<?=e($settings['company_patente'] ?? '')?>" placeholder="Patente"></label>
          <label>Id. prof. 3 (I.F.)<input name="company_if" value="<?=e($settings['company_if'] ?? '')?>" placeholder="I.F."></label>
          <label>Id. prof. 4 (C.N.S.S.)<input name="company_cnss" value="<?=e($settings['company_cnss'] ?? '')?>" placeholder="C.N.S.S."></label>
          <label>Identifiant Commun d’Entreprise (ICE)<input name="company_ice" value="<?=e($settings['company_ice'] ?? '')?>" placeholder="ICE"></label>
          <label>Numéro TVA<input name="company_tva" value="<?=e($settings['company_tva'] ?? '')?>" placeholder="TVA"></label>
        </div>
      </section>

      <section class="ge-step" data-step="1" hidden>
        <div class="ge-step-title"><i class="fa-solid fa-file-pdf"></i><div><h3>PDF / invoice model</h3><p>The preview changes immediately. Logo, issuer, payment and footer are hidden unless you enable them.</p></div></div>
        <div class="ge-pdf-layout">
          <div class="ge-onboarding-grid compact">
            <label class="ge-check"><input type="checkbox" name="pdf_show_logo" value="1" data-pdf-toggle="logo" <?=$pdfShowLogo?'checked':''?>> Show logo</label>
            <label>Logo for PDF<input type="file" name="company_logo" id="geLogoInput" accept="image/png,image/jpeg,image/webp,image/gif"></label>
            <label class="wide">Header text / subtitle<textarea name="company_subtitle" id="gePdfSubtitle" rows="2" placeholder="Write any header text you want, or leave empty."><?=e($settings['company_subtitle'] ?? '')?></textarea></label>
            <label>Capital<input name="company_capital" value="<?=e($settings['company_capital'] ?? '')?>" placeholder="Capital"></label>
            <label>Bank<input name="company_bank" value="<?=e($settings['company_bank'] ?? '')?>" placeholder="Bank name"></label>
            <label>RIB<input name="company_rib" value="<?=e($settings['company_rib'] ?? '')?>" placeholder="RIB / account number"></label>
            <label class="ge-check"><input type="checkbox" name="pdf_show_sender_box" value="1" data-pdf-toggle="sender" <?=$pdfShowSender?'checked':''?>> Show issuer box</label>
            <label class="ge-check"><input type="checkbox" name="pdf_show_payment_block" value="1" data-pdf-toggle="payment" <?=$pdfShowPayment?'checked':''?>> Show payment block</label>
            <label class="wide">Issuer box text<textarea name="pdf_sender_text" id="gePdfSenderText" rows="3" placeholder="Leave empty to use company name, address, city and phone."><?=e($settings['pdf_sender_text'] ?? '')?></textarea></label>
            <label class="wide">Payment / invoice terms<textarea name="invoice_default_terms" id="gePdfPaymentText" rows="3" placeholder="Write payment terms, bank text, delay, note... or leave empty."><?=e($settings['invoice_default_terms'] ?? '')?></textarea></label>
            <label class="ge-check"><input type="checkbox" name="pdf_show_footer" value="1" data-pdf-toggle="footer" <?=$pdfShowFooter?'checked':''?>> Show footer</label>
            <label class="ge-check"><input type="checkbox" name="pdf_show_signature" value="1" data-pdf-toggle="signature" <?=$pdfShowSignature?'checked':''?>> Show signature box</label>
            <label class="wide">Footer text<textarea name="pdf_footer_text" id="gePdfFooterText" rows="3" placeholder="Write any footer/legal text you want, or leave empty."><?=e($settings['pdf_footer_text'] ?? '')?></textarea></label>
          </div>
          <div class="ge-pdf-preview" aria-label="Empty invoice PDF preview">
            <div class="ge-pdf-paper">
              <div class="ge-pdf-top">
                <div class="ge-pdf-logo <?=$pdfShowLogo?'':'is-hidden'?>" id="gePdfLogoBox"><?php if($pdfShowLogo && !empty($settings['company_logo'])): ?><img src="<?=e($settings['company_logo'])?>" alt="Logo"><?php else: ?><strong>LOGO</strong><?php endif; ?></div>
                <div class="ge-pdf-doc-title"><b>FACTURE</b><span>INV-000001</span></div>
              </div>
              <div class="ge-pdf-sub" id="gePdfSubText"><?=e($settings['company_subtitle'] ?? '')?></div>
              <div class="ge-pdf-boxes">
                <div id="gePdfSenderBox" class="<?=$pdfShowSender?'':'is-hidden'?>"><small>Émetteur</small><b id="gePdfCompany"><?=e($companyName)?></b><span id="gePdfSenderPreview"><?=e(($settings['pdf_sender_text'] ?? '') ?: trim(($settings['company_address'] ?? '')."\n".$companyCity.' '.$companyCountry."\n".$companyPhone))?></span></div>
                <div><small>Adressé à</small><b>CLIENT</b><span>Adresse client</span></div>
              </div>
              <table class="ge-pdf-table"><thead><tr><th>Désignation</th><th>TVA</th><th>P.U. HT</th><th>Qté</th><th>Total HT</th></tr></thead><tbody><tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr><tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr><tr><td>&nbsp;</td><td></td><td></td><td></td><td></td></tr></tbody></table>
              <div id="gePdfPaymentPreview" class="ge-pdf-payment <?=$pdfShowPayment?'':'is-hidden'?>"><?=e($settings['invoice_default_terms'] ?? '')?></div>
              <div class="ge-pdf-total"><span>Total HT</span><b>0,00</b><span>Total TVA</span><b>0,00</b><span>Total TTC</span><b>0,00</b></div>
              <div id="gePdfSignaturePreview" class="ge-pdf-signature <?=$pdfShowSignature?'':'is-hidden'?>">Cachet, Date, Signature</div>
              <div id="gePdfFooterPreview" class="ge-pdf-footer <?=$pdfShowFooter?'':'is-hidden'?>"><?=e($settings['pdf_footer_text'] ?? '')?></div>
            </div>
          </div>
        </div>
      </section>

      <section class="ge-step" data-step="2" hidden>
        <div class="ge-step-title"><i class="fa-solid fa-envelope-circle-check"></i><div><h3>SMTP email configuration</h3><p>Used to send invoices, quotes, orders and signatures by email.</p></div></div>
        <div class="ge-onboarding-grid">
          <label>SMTP host<input name="smtp_host" value="<?=e($settings['smtp_host'] ?? '')?>" placeholder="smtp.example.com"></label>
          <label>SMTP port<input name="smtp_port" type="number" value="<?=e($settings['smtp_port'] ?? 587)?>" placeholder="587"></label>
          <label>SMTP security<select name="smtp_secure"><option value="tls" <?=(($settings['smtp_secure'] ?? 'tls')==='tls')?'selected':''?>>TLS / STARTTLS</option><option value="ssl" <?=(($settings['smtp_secure'] ?? '')==='ssl')?'selected':''?>>SSL</option><option value="none" <?=(($settings['smtp_secure'] ?? '')==='none')?'selected':''?>>None</option></select></label>
          <label>SMTP user<input name="smtp_username" value="<?=e($settings['smtp_username'] ?? '')?>" placeholder="username"></label>
          <label>SMTP password<input name="smtp_password" type="password" value="<?=e($settings['smtp_password'] ?? '')?>" placeholder="password"></label>
          <label>Email sender<input name="smtp_from_email" type="email" value="<?=e($settings['smtp_from_email'] ?? $companyEmail)?>" placeholder="no-reply@company.com"></label>
          <label>Sender name<input name="smtp_from_name" value="<?=e($settings['smtp_from_name'] ?? $companyName)?>" placeholder="Company name"></label>
          <label>Resend API key optional<input name="smtp_resend_api_key" value="<?=e($settings['smtp_resend_api_key'] ?? '')?>" placeholder="re_..."></label>
          <label class="wide">Default email letter<textarea name="smtp_default_letter" rows="5" placeholder="Bonjour,&#10;Veuillez trouver ci-joint votre document.&#10;Cordialement,"><?=e($settings['smtp_default_letter'] ?? '')?></textarea></label>
        </div>
      </section>

      <section class="ge-step ge-pro-step" data-step="3" hidden>
        <div class="ge-step-title centered"><i class="fa-solid fa-crown"></i><div><h3>Upgrade to Pro</h3><p>Unlock advanced automation, extra users, document approval flows and premium PDF branding.</p></div></div>
        <div class="ge-pro-card"><div><b>Pro Panel</b><span>More modules, faster team workflow, advanced reporting.</span></div><button type="button" class="ge-primary" data-pro-upgrade>Upgrade to Pro now</button></div>
      </section>

      <section class="ge-step ge-success-step" data-step="4" hidden>
        <div class="ge-success-circle"><i class="fa-solid fa-check"></i></div>
        <h3>Configuration saved successfully</h3>
        <p>Your company panel is ready. Close this window to add your first product.</p>
      </section>

      <div class="ge-onboarding-error" id="geOnboardingError" hidden></div>
      <div class="ge-onboarding-actions">
        <button type="button" class="ge-secondary" data-skip>Skip</button>
        <button type="button" class="ge-primary" data-next>Next</button>
        <button type="button" class="ge-primary" data-close-success hidden>Close</button>
      </div>
    </form>
  </div>
</div>

<div class="ge-onboarding-overlay <?= $showProductOnly ? 'is-open' : '' ?>" id="geFirstProductOverlay" aria-hidden="<?= $showProductOnly ? 'false' : 'true' ?>">
  <div class="ge-product-modal" role="dialog" aria-modal="true" aria-labelledby="geFirstProductTitle">
    <button type="button" class="ge-onboarding-close" data-product-skip aria-label="Close">×</button>
    <form id="geFirstProductForm" method="post" action="index.php?page=onboarding_save">
      <?=csrf_field()?>
      <input type="hidden" name="onboarding_action" value="first_product_add">
      <div class="ge-step-title"><i class="fa-solid fa-box-open"></i><div><h3 id="geFirstProductTitle">Add your first product</h3><p>You can add one product now or skip and do it later.</p></div></div>
      <div class="ge-onboarding-grid compact">
        <label>Product name<input name="first_product_label" placeholder="Example: Midea 12000 BTU" required></label>
        <label>Reference<input name="first_product_ref" placeholder="Auto if empty"></label>
        <label>Type<input name="first_product_type" value="Produit manufacturé"></label>
        <label>Unit<input name="first_product_unit" value="Pièce"></label>
        <label>Sale price<input name="first_product_sale_price" type="number" step="0.001" value="0"></label>
        <label>Buy price<input name="first_product_buy_price" type="number" step="0.001" value="0"></label>
        <label>Stock<input name="first_product_stock" type="number" step="0.001" value="0"></label>
        <label>TVA<input name="first_product_tax" value="20%"></label>
      </div>
      <div class="ge-onboarding-error" id="geProductError" hidden></div>
      <div class="ge-onboarding-actions"><button type="button" class="ge-secondary" data-product-skip>Skip</button><button type="submit" class="ge-primary">Add product</button></div>
    </form>
  </div>
</div>

<script>
(function(){
  const wizard=document.getElementById('geOnboardingOverlay');
  const product=document.getElementById('geFirstProductOverlay');
  const form=document.getElementById('geOnboardingForm');
  const productForm=document.getElementById('geFirstProductForm');
  if(!form && !productForm) return;
  let step=0;
  const maxStep=4;
  const err=document.getElementById('geOnboardingError');
  function openModal(el){ if(el){ el.classList.add('is-open'); el.setAttribute('aria-hidden','false'); document.body.classList.add('modal-lock'); } }
  function closeModal(el){ if(el){ el.classList.remove('is-open'); el.setAttribute('aria-hidden','true'); if(!document.querySelector('.ge-onboarding-overlay.is-open')) document.body.classList.remove('modal-lock'); } }
  function showError(box,msg){ if(!box) return; box.textContent=msg||'Error'; box.hidden=false; }
  function clearError(box){ if(!box) return; box.textContent=''; box.hidden=true; }
  function setStep(n){
    step=Math.max(0,Math.min(maxStep,n));
    form.querySelectorAll('.ge-step').forEach(s=>{
      const active = Number(s.dataset.step)===step;
      s.classList.toggle('active', active);
      s.hidden = !active;
    });
    const skipBtn=form.querySelector('[data-skip]');
    const nextBtn=form.querySelector('[data-next]');
    const closeBtn=form.querySelector('[data-close-success]');
    if(skipBtn) skipBtn.hidden = step===4;
    if(nextBtn) nextBtn.hidden = step===4;
    if(closeBtn) closeBtn.hidden = step!==4;
    clearError(err);
  }
  async function saveWizard(){
    clearError(err);
    const fd=new FormData(form);
    try{
      const r=await fetch(form.action,{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}});
      const j=await r.json();
      if(!j.ok) throw new Error(j.error||'Unable to save setup.');
      setStep(4);
    }catch(ex){ showError(err, ex.message||'Unable to save setup.'); }
  }
  if(wizard && wizard.classList.contains('is-open')) document.body.classList.add('modal-lock');
  if(product && product.classList.contains('is-open')) document.body.classList.add('modal-lock');
  if(form){
    form.querySelector('[data-next]').addEventListener('click',()=>{ if(step>=3) saveWizard(); else setStep(step+1); });
    form.querySelector('[data-skip]').addEventListener('click',()=>{ if(step>=3) saveWizard(); else setStep(step+1); });
    form.querySelector('[data-close-success]').addEventListener('click',()=>{ closeModal(wizard); openModal(product); });
    form.querySelector('[data-pro-upgrade]').addEventListener('click',()=>{ document.getElementById('geProChoice').value='pro_requested'; saveWizard(); });
    setStep(0);
  }
  const logoInput=document.getElementById('geLogoInput');
  const logoBox=document.getElementById('gePdfLogoBox');
  function q(name){ return form ? form.querySelector('[name="'+name+'"]') : null; }
  function val(name){ const el=q(name); return el ? el.value.trim() : ''; }
  function checked(name){ const el=q(name); return !!(el && el.checked); }
  function toggleBox(name, id){ const box=document.getElementById(id); if(box) box.classList.toggle('is-hidden', !checked(name)); }
  function updatePreview(){
    toggleBox('pdf_show_logo','gePdfLogoBox');
    toggleBox('pdf_show_sender_box','gePdfSenderBox');
    toggleBox('pdf_show_payment_block','gePdfPaymentPreview');
    toggleBox('pdf_show_footer','gePdfFooterPreview');
    toggleBox('pdf_show_signature','gePdfSignaturePreview');
    const cn=val('company_name') || 'Company';
    const address=[val('company_address'), (val('company_city')+' '+val('company_country')).trim(), val('company_phone')].filter(Boolean).join('\n');
    const customSender=val('pdf_sender_text');
    const companyOut=document.getElementById('gePdfCompany'); if(companyOut) companyOut.textContent=cn;
    const senderOut=document.getElementById('gePdfSenderPreview'); if(senderOut) senderOut.textContent=customSender || address;
    const subOut=document.getElementById('gePdfSubText'); if(subOut) subOut.textContent=val('company_subtitle');
    const paymentOut=document.getElementById('gePdfPaymentPreview'); if(paymentOut) paymentOut.textContent=val('invoice_default_terms');
    const footerOut=document.getElementById('gePdfFooterPreview'); if(footerOut) footerOut.textContent=val('pdf_footer_text');
  }
  if(logoInput && logoBox){ logoInput.addEventListener('change',()=>{ const f=logoInput.files&&logoInput.files[0]; if(!f) return; const url=URL.createObjectURL(f); logoBox.innerHTML='<img src="'+url+'" alt="Logo">'; const show=q('pdf_show_logo'); if(show){ show.checked=true; } updatePreview(); }); }
  ['company_name','company_address','company_city','company_country','company_phone','company_subtitle','pdf_sender_text','invoice_default_terms','pdf_footer_text'].forEach(name=>{ const input=q(name); if(input) input.addEventListener('input',updatePreview); });
  form && form.querySelectorAll('[data-pdf-toggle]').forEach(ch=>ch.addEventListener('change',updatePreview));
  updatePreview();
  async function productSkip(){
    const fd=new FormData();
    const csrf=productForm ? productForm.querySelector('[name="csrf_token"]').value : (window.GE_CSRF_TOKEN||'');
    fd.append('csrf_token',csrf); fd.append('onboarding_action','first_product_skip');
    try{ await fetch('index.php?page=onboarding_save',{method:'POST',body:fd,headers:{'X-Requested-With':'XMLHttpRequest'}}); }catch(e){}
    closeModal(product);
  }
  document.querySelectorAll('[data-product-skip]').forEach(btn=>btn.addEventListener('click',productSkip));
  if(productForm){
    productForm.addEventListener('submit',async function(e){
      e.preventDefault(); const box=document.getElementById('geProductError'); clearError(box);
      try{
        const r=await fetch(productForm.action,{method:'POST',body:new FormData(productForm),headers:{'X-Requested-With':'XMLHttpRequest'}});
        const j=await r.json(); if(!j.ok) throw new Error(j.error||'Unable to add product.');
        closeModal(product);
        if(j.product_url) window.location.href=j.product_url;
      }catch(ex){ showError(box, ex.message||'Unable to add product.'); }
    });
  }
})();
</script>
