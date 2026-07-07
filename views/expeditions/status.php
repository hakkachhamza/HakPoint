<?php
require __DIR__.'/_helpers.php';
require __DIR__.'/../warehouses/_helpers.php';
$id=(int)($_GET['id']??0);
$status=$_GET['status']??'Brouillon';
$error='';
if(in_array($status, expedition_statuses(), true)){
    $ok=expedition_set_status($id,$status,$error);
    if(!$ok && $error!=='') redirect_to('index.php?page=expedition_show&id='.$id.'&stock_error='.urlencode($error));
}
redirect_to('index.php?page=expedition_show&id='.$id);
