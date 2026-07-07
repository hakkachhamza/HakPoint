<?php
require_once __DIR__.'/_helpers.php';
$users=data_read('users',[]); $id=(int)($_GET['id']??0);
foreach($users as &$u){ if((int)($u['id']??0)===$id){ $u=user_collect_post($u,$id); break; }} unset($u);
data_write('users',$users); redirect_to('index.php?page=user_show&id='.$id);
