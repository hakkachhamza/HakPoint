<?php
try{
    ge_create_backup(trim((string)($_POST['note'] ?? 'manual')));
    redirect_to('index.php?page=backups&created=1');
}catch(Throwable $e){
    redirect_to('index.php?page=backups&err='.urlencode($e->getMessage()));
}
