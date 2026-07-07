<?php
require_once __DIR__.'/_helpers.php';
require_once __DIR__.'/../expeditions/_helpers.php';
$action=$_POST['bulk_action']??''; $ids=array_map('intval',$_POST['order_ids']??[]); $orders=data_read('orders',[]);
$status=match($action){'validate'=>'Validée','progress'=>'En cours','delivered'=>'Livrée','cancel'=>'Annulée', default=>null};
$blocked=0;
if($action==='delete'){
    $orders=array_values(array_filter($orders,fn($o)=>!in_array((int)($o['id']??0),$ids,true)));
    data_write('orders',$orders);
}elseif($status){
    if($status==='Livrée'){
        foreach($ids as $id){ order_update_delivery_status($id); $o=find_row_by_id(data_read('orders',[]),$id); if(($o['status']??'')!=='Livrée') $blocked++; }
    }else{
        foreach($orders as &$o){ if(in_array((int)($o['id']??0),$ids,true)){ $o['status']=$status; $o['updated_at']=date('d/m/Y H:i'); } } unset($o);
        data_write('orders',$orders);
    }
}
redirect_to('index.php?page=orders'.($blocked?'&need_expedition='.$blocked:''));
