<?php
$id=(int)($_POST['id']??0);
$products=data_read('products',[]);
foreach($products as &$p){
  if((int)($p['id']??0)===$id){
    $p['tax_rate']=(float)($_POST['tax_rate']??20);
    $p['base_price']=$_POST['base_price']??'HT';
    $p['sale_price']=(float)($_POST['sale_price']??0);
    $p['sale_price_min']=(float)($_POST['sale_price_min']??0);
    $p['sale_prices']=$p['sale_prices']??[];
    array_unshift($p['sale_prices'],[
      'date'=>date('d/m/Y H:i'),'base'=>$p['base_price'],'tax'=>$p['tax_rate'],'ht'=>$p['sale_price'],'ttc'=>$p['sale_price']*(1+$p['tax_rate']/100),'min_ht'=>$p['sale_price_min'],'min_ttc'=>$p['sale_price_min']*(1+$p['tax_rate']/100),'user'=>ge_current_author_name(),'user_id'=>ge_current_author_id(),'user_username'=>ge_current_author_username()
    ]);
    break;
  }
}
unset($p); data_write('products',$products); redirect_to(product_url($id,'sale_price'));
