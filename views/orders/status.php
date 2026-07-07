<?php
require_once __DIR__.'/_helpers.php';
require_once __DIR__.'/../expeditions/_helpers.php';
$id=(int)($_GET['id']??0);
$status=$_GET['status']??'Brouillon';
$orders=data_read('orders',[]);
if($status==='Livrée'){
    order_update_delivery_status($id);
    $order=find_row_by_id(data_read('orders',[]),$id);
    if(($order['status'] ?? '') !== 'Livrée') redirect_to('index.php?page=order_show&id='.$id.'&need_expedition=1');
    redirect_to('index.php?page=order_show&id='.$id);
}
foreach($orders as &$o){
    if((int)($o['id']??0)===$id){
        $o['status']=$status;
        $o['updated_at']=date('d/m/Y H:i');
    }
}
unset($o);
data_write('orders',$orders);
redirect_to('index.php?page=order_show&id='.$id);
