</section><footer>powered by Global Energie</footer></main></div>
<?php if((function_exists('ge_should_show_onboarding') && ge_should_show_onboarding()) || (function_exists('ge_should_show_first_product_prompt') && ge_should_show_first_product_prompt())){ include __DIR__.'/../onboarding/modal.php'; } ?>
<?php
$showStockAlertOnLogin = !empty($_SESSION['ge_show_stock_alert_once']);
if ($showStockAlertOnLogin) {
    unset($_SESSION['ge_show_stock_alert_once']);
}
?>
<script>
window.GE_CSRF_TOKEN = <?=json_encode(csrf_token())?>;
window.GE_CSRF_PAGES = <?=json_encode([
 'product_store','product_update','product_delete','product_duplicate','product_save_sale_price','product_save_purchase_price',
 'tiers_store','tiers_update','tiers_delete','tiers_clone','tiers_merge','tiers_bulk_action','tiers_email',
 'warehouse_store','warehouse_update','warehouse_delete','warehouse_adjust_save','warehouse_transfer_save',
 'quote_store','quote_update','quote_delete','quote_clone','quote_pdf_generate','quote_bulk_action','quote_status','quote_document_delete','quote_email',
 'order_store','order_update','order_delete','order_clone','order_pdf_generate','order_bulk_action','order_status','order_document_delete','order_email',
 'invoice_store','invoice_update','invoice_delete','invoice_clone','invoice_pdf_generate','invoice_bulk_action','invoice_status','invoice_payment_add','invoice_document_delete','invoice_email',
 'expedition_store','expedition_update','expedition_delete','expedition_pdf_generate','expedition_bulk_action','expedition_status','expedition_document_delete','expedition_email',
 'reception_store','reception_update','reception_delete','reception_bulk_action','reception_status','reception_document_delete','reception_email','reception_pdf_generate',
 'user_store','user_update','user_delete','user_bulk_action','user_permissions_save','user_email','api_save','settings_save_language','settings_security_save','settings_profile_save','purchase_pdf_generate','purchase_document_delete','purchase_make_invoice','purchase_status','credit_note_pdf_generate','credit_note_document_delete','credit_note_status','credit_note_delete','approval_pdf_generate','approval_document_delete','approval_status','approval_delete','backup_create','backup_restore','modules_save','onboarding_save'
])?>;
window.GE_STOCK_ALERT_COUNT = <?=json_encode((int)($stockNotificationCount ?? 0))?>;
window.GE_STOCK_ALERT_SIGNATURE = <?=json_encode((string)($stockAlertSignature ?? ''))?>;
window.GE_STOCK_ALERT_ON_LOGIN = <?=json_encode((bool)$showStockAlertOnLogin)?>;
</script>
<script src="assets/js/app.js"></script></body></html><?php ge_end_translate(); ?>
