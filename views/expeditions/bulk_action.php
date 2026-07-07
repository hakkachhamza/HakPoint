<?php
require __DIR__.'/_helpers.php';
require __DIR__.'/../warehouses/_helpers.php';
$action=$_POST['action']??'';
$ids=array_map('intval', $_POST['ids']??[]);
$blocked=0;
foreach($ids as $id){
    if($action==='delete') expedition_delete_all($id);
    elseif(in_array($action, expedition_statuses(), true)){
        $error=''; if(!expedition_set_status($id,$action,$error)) $blocked++;
    }
}
redirect_to('index.php?page=expeditions'.($blocked?'&stock_blocked='.$blocked:''));
