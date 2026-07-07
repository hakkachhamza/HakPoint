<?php
$id=(int)($_POST['id']??0);
$products=data_read('products',[]); $suppliers=data_read('suppliers',[]); $supplierName='';
foreach($suppliers as $s){ if((string)($s['id']??'')===(string)($_POST['supplier_id']??'')){ $supplierName=$s['name']??$s['company']??''; break; } }
foreach($products as &$p){
  if((int)($p['id']??0)===$id){
    $price=(float)($_POST['purchase_price']??0); $tax=(float)($_POST['tax_rate']??20);
    $p['buy_price']=$price;
    $p['purchase_prices']=$p['purchase_prices']??[];
    array_unshift($p['purchase_prices'],[
      'date'=>date('d/m/Y H:i'),'supplier_id'=>$_POST['supplier_id']??'','supplier_name'=>$supplierName ?: 'Fournisseur','supplier_ref'=>$_POST['supplier_ref']??'', 'min_qty'=>(float)($_POST['min_qty']??1),'tax'=>$tax,'price'=>$price,'unit_price'=>$price,'discount'=>(float)($_POST['discount']??0),'delivery_days'=>$_POST['delivery_days']??'','reputation'=>$_POST['reputation']??''
    ]);
    break;
  }
}
unset($p); data_write('products',$products); redirect_to(product_url($id,'buy_price'));
