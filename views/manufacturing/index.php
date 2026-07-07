<?php
$title='Fabrication / BOM';
$fields=['ref'=>['label'=>'Réf.'],'product_id'=>['label'=>'ID produit fini','type'=>'number'],'product_ref'=>['label'=>'Réf produit'],'product_label'=>['label'=>'Produit fini'],'qty'=>['label'=>'Qté à fabriquer','type'=>'qty'],'start_date'=>['label'=>'Début','type'=>'date'],'end_date'=>['label'=>'Fin','type'=>'date'],'cost'=>['label'=>'Coût','type'=>'money'],'status'=>['label'=>'Statut','type'=>'select','options'=>ge_status_options('manufacturing')],'note'=>['label'=>'Composants / note','type'=>'textarea']];
ge_simple_manager('Fabrication / BOM','manufacturing_orders',$fields,'manufacturing','MO','fa-industry');
