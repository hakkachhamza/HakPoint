<?php
$users=data_read('users',[]); $ids=array_map('intval',$_POST['ids']??[]); $action=$_POST['action']??'';
if($ids && $action){
 if($action==='delete') $users=array_values(array_filter($users, fn($u)=>!in_array((int)($u['id']??0),$ids,true)));
 else foreach($users as &$u){ if(in_array((int)($u['id']??0),$ids,true)) $u['status']=$action==='activate'?'Actif':'Désactivé'; } unset($u);
 data_write('users',$users);
}
redirect_to('index.php?page=users');
