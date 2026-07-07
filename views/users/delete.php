<?php
$id=(int)($_GET['id']??0); $users=data_read('users',[]);
$users=array_values(array_filter($users, fn($u)=>(int)($u['id']??0)!==$id));
data_write('users',$users); redirect_to('index.php?page=users');
