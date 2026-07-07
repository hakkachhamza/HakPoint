<?php require __DIR__.'/_helpers.php'; require __DIR__.'/../warehouses/_helpers.php';
$id=(int)($_GET['id']??0);
try{
    reception_delete_all($id);
    redirect_to('index.php?page=receptions&deleted=1');
}catch(Throwable $e){
    redirect_to('index.php?page=reception_show&id='.$id.'&error='.rawurlencode($e->getMessage()));
}
