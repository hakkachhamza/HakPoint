<?php require __DIR__.'/_helpers.php'; require __DIR__.'/../warehouses/_helpers.php';
$action=$_POST['action']??''; $ids=array_map('intval', $_POST['ids']??[]); $errors=[];
foreach($ids as $id){
    try{
        if($action==='delete') reception_delete_all($id);
        elseif(in_array($action, reception_statuses(), true)) reception_set_status($id,$action);
    }catch(Throwable $e){ $errors[]=$e->getMessage(); }
}
if($errors) redirect_to('index.php?page=receptions&error='.rawurlencode(implode(' | ', array_slice($errors,0,3))));
redirect_to('index.php?page=receptions&ok=1');
