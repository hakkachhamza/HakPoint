<?php require __DIR__.'/_helpers.php';
$rows=warehouses_all(); $id=next_id($rows); $name=trim($_POST['name']??'');
$row=['id'=>$id,'ref'=>trim($_POST['ref']??'') ?: warehouse_ref($id,$name),'name'=>$name,'parent_id'=>(int)($_POST['parent_id']??0),'description'=>trim($_POST['description']??''),'address'=>trim($_POST['address']??''),'zip'=>trim($_POST['zip']??''),'city'=>trim($_POST['city']??''),'country'=>trim($_POST['country']??'Maroc (MA)'),'phone'=>trim($_POST['phone']??''),'fax'=>trim($_POST['fax']??''),'status'=>$_POST['status']??'Ouvert','created_at'=>date('d/m/Y H:i')];
$rows[]=$row; data_write('warehouses',$rows); redirect_to('index.php?page=warehouse_show&id='.$id);
