<?php
function save_product_image($current=''){
    try{
        $saved = ge_secure_save_upload('product_image', 'products', ['jpg','jpeg','png','gif','webp'], (int)(app_config()['uploads']['max_image_mb'] ?? 5), 'product');
        return $saved ?: $current;
    }catch(Throwable $e){
        try{ audit_log('upload_rejected', 'Product image: '.$e->getMessage()); }catch(Throwable $ignored){}
        return $current;
    }
}

function product_payload($id,$current=[]){
    $physical=(float)ge_parse_number($_POST['physical_stock'] ?? ($current['physical_stock'] ?? 0));
    $warehouses = function_exists('warehouses_all') ? warehouses_all() : data_read('warehouses', []);
    $warehouseName = trim((string)($_POST['warehouse'] ?? ($current['warehouse'] ?? '')));
    $warehouseId = (int)($_POST['warehouse_id'] ?? ($current['warehouse_id'] ?? 0));
    if($warehouseId > 0){
        foreach($warehouses as $w){ if((int)($w['id'] ?? 0)===$warehouseId){ $warehouseName=(string)($w['name'] ?? $warehouseName); break; } }
    }
    if($warehouseId <= 0 && $warehouseName !== ''){
        foreach($warehouses as $w){ if(trim((string)($w['name'] ?? '')) === $warehouseName){ $warehouseId=(int)($w['id'] ?? 0); break; } }
    }
    if($warehouseId <= 0 && $warehouses){ $warehouseId=(int)($warehouses[0]['id'] ?? 0); $warehouseName=$warehouseName ?: (string)($warehouses[0]['name'] ?? ''); }
    $stockMap = [];
    if(function_exists('product_warehouse_stock')) $stockMap = product_warehouse_stock($current);
    elseif(!empty($current['warehouse_stock']) && is_array($current['warehouse_stock'])) $stockMap = $current['warehouse_stock'];
    if($warehouseId > 0){
        // Important: update only the selected warehouse and preserve all other warehouses.
        $stockMap[$warehouseId] = $physical;
    }
    $totalPhysical = $stockMap ? (float)array_sum(array_map('floatval',$stockMap)) : $physical;
    $taxRaw = $_POST['tax'] ?? ($current['tax'] ?? $current['tax_rate'] ?? $current['vat'] ?? '20%');
    $taxRate = function_exists('ge_parse_number') ? ge_parse_number($taxRaw) : (float)str_replace(',','.',preg_replace('/[^0-9,.-]+/','',(string)$taxRaw));
    return array_merge($current,[
        'id'=>$id,
        'ref'=>trim($_POST['ref'] ?? ($current['ref'] ?? ('P'.$id))),
        'label'=>trim($_POST['label'] ?? ($current['label'] ?? 'Produit')),
        'sale_status'=>$_POST['sale_status'] ?? ($current['sale_status'] ?? 'En vente'),
        'buy_status'=>$_POST['buy_status'] ?? ($current['buy_status'] ?? 'En achat'),
        'description'=>$_POST['description'] ?? ($current['description'] ?? ''),
        'public_url'=>$_POST['public_url'] ?? ($current['public_url'] ?? ''),
        'site_visible'=>$_POST['site_visible'] ?? ($current['site_visible'] ?? 'Oui'),
        'warehouse'=>$warehouseName,
        'warehouse_id'=>$warehouseId,
        'warehouse_stock'=>$stockMap,
        'alert_stock'=>(int)($_POST['alert_stock'] ?? ($current['alert_stock'] ?? 0)),
        'desired_stock'=>(int)($_POST['desired_stock'] ?? ($current['desired_stock'] ?? 0)),
        'type'=>$_POST['type'] ?? ($current['type'] ?? 'Produit manufacturé'),
        'product_type'=>trim($_POST['product_type'] ?? ($current['product_type'] ?? '')),
        'weight'=>$_POST['weight'] ?? ($current['weight'] ?? ''),
        'length'=>$_POST['length'] ?? ($current['length'] ?? ''),
        'width'=>$_POST['width'] ?? ($current['width'] ?? ''),
        'height'=>$_POST['height'] ?? ($current['height'] ?? ''),
        'surface'=>$_POST['surface'] ?? ($current['surface'] ?? ''),
        'volume'=>$_POST['volume'] ?? ($current['volume'] ?? ''),
        'unit'=>$_POST['unit'] ?? ($current['unit'] ?? 'unitF'),
        'customs_code'=>$_POST['customs_code'] ?? ($current['customs_code'] ?? ''),
        'country'=>$_POST['country'] ?? ($current['country'] ?? 'Maroc'),
        'note'=>$_POST['note'] ?? ($current['note'] ?? ''),
        'sale_price'=>(float)($_POST['sale_price'] ?? ($current['sale_price'] ?? 0)),
        'sale_tax_mode'=>$_POST['sale_tax_mode'] ?? ($current['sale_tax_mode'] ?? 'HT'),
        'min_sale_price'=>(float)($_POST['min_sale_price'] ?? ($current['min_sale_price'] ?? 0)),
        'tax'=>is_numeric($taxRaw) ? ($taxRaw.'%') : (string)$taxRaw,
        'tax_rate'=>$taxRate,
        'vat'=>$taxRate,
        'buy_price'=>(float)($_POST['buy_price'] ?? ($current['buy_price'] ?? 0)),
        'physical_stock'=>$totalPhysical,
        'stock'=>$totalPhysical,
        'virtual_stock'=>$totalPhysical,
        'image'=>save_product_image($current['image'] ?? ''),
        'updated_at'=>date('Y-m-d H:i:s'),
        'created_at'=>$current['created_at'] ?? date('Y-m-d H:i:s')
    ]);
}
