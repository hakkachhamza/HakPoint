<?php
require __DIR__.'/_save_helpers.php';
$products=data_read('products',[]);
$id=next_id($products);
$products[]=product_payload($id);
data_write('products',$products);
redirect_to(product_url($id));
