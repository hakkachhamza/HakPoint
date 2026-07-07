<?php
try{
    require_csrf();
    $confirm = trim((string)($_POST['confirm_restore'] ?? ''));
    if($confirm !== 'RESTORE') throw new Exception('Tape RESTORE pour confirmer la restauration.');
    $filename = (string)($_POST['filename'] ?? '');
    ge_restore_backup($filename);
    redirect_to('index.php?page=backups&restored=1');
}catch(Throwable $e){
    redirect_to('index.php?page=backups&err='.urlencode($e->getMessage()));
}
