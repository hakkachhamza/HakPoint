<?php
$lang=$_POST['language'] ?? 'fr';
if(!in_array($lang,['fr','en','ar'],true)) $lang='fr';
save_app_settings(['language'=>$lang]);
audit_log('settings_language_changed','Language changed to '.$lang);
redirect_to('index.php?page=settings&ok=1#language-section');
