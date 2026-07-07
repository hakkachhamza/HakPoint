<?php
require_once __DIR__.'/_helpers.php';
$users=data_read('users',[]);
$id=(int)($_GET['id']??0);
$allowed=function_exists('user_all_permission_keys') ? user_all_permission_keys() : [];
$posted=is_array($_POST['permissions'] ?? null) ? $_POST['permissions'] : [];
$permissions=[];
foreach($posted as $permission){
    $permission=(string)$permission;
    if(!$allowed || in_array($permission,$allowed,true)) $permissions[]=$permission;
}
$permissions=array_values(array_unique($permissions));
foreach($users as &$u){
    if((int)($u['id']??0)===$id){
        $u['permissions']=$permissions;
        $u['updated_at']=date('Y-m-d H:i:s');
        break;
    }
}
unset($u);
data_write('users',$users);
refresh_session_user();
redirect_to('index.php?page=user_permissions&id='.$id);
