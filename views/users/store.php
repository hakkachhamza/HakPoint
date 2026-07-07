<?php
require_once __DIR__.'/_helpers.php';
$users=data_read('users',[]); $id=next_id($users);
$row=user_collect_post([], $id); $row['created_at']=date('Y-m-d H:i:s');
$users[]=$row; data_write('users',$users); redirect_to('index.php?page=user_show&id='.$id);
