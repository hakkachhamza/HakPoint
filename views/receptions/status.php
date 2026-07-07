<?php require __DIR__.'/_helpers.php'; require __DIR__.'/../warehouses/_helpers.php';
$id=(int)($_GET['id']??0);
$status=$_GET['status']??'Brouillon';
try{
    if(in_array($status, reception_statuses(), true)) reception_set_status($id,$status);
    redirect_to('index.php?page=reception_show&id='.$id.'&ok=1');
}catch(Throwable $e){
    redirect_to('index.php?page=reception_show&id='.$id.'&error='.rawurlencode($e->getMessage()));
}
