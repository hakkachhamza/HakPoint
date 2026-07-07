<?php
function warehouses_all(){ return data_read('warehouses', []); }
function warehouse_find($id){ foreach(warehouses_all() as $w){ if((int)($w['id']??0)===(int)$id) return $w; } return null; }
function warehouse_ref($id,$name=''){ $slug = strtoupper(preg_replace('/[^A-Z0-9]+/','_',trim(iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$name)))); $slug = trim($slug,'_'); return $slug ?: 'DEPOT_'.str_pad((string)$id,4,'0',STR_PAD_LEFT); }
function warehouse_statuses(){ return ['Ouvert','Fermé']; }
function product_warehouse_stock($p){
    $map=$p['warehouse_stock'] ?? null;
    if(is_string($map) && $map !== ''){
        $decoded=json_decode($map,true);
        if(is_array($decoded)) $map=$decoded;
    }
    if(is_array($map)){
        $clean=[];
        foreach($map as $k=>$v){
            $wid=(int)$k;
            if($wid>0) $clean[$wid]=(float)$v;
        }
        if($clean) return $clean;
    }
    $wid=(int)($p['warehouse_id']??0);
    $qty=(float)($p['physical_stock']??$p['stock']??0);
    return $wid>0 ? [$wid=>$qty] : [];
}
function product_total_physical_stock($p){ return (float)array_sum(product_warehouse_stock($p)); }
function save_product_warehouse_stock(&$p, $map){
    $clean=[];
    foreach((array)$map as $k=>$v){
        $wid=(int)$k;
        $qty=(float)$v;
        if($wid>0 && abs($qty) > 0.000001) $clean[$wid]=$qty;
    }
    $p['warehouse_stock']=$clean;
    $total=(float)array_sum($clean);
    $p['physical_stock']=$total;
    $p['virtual_stock']=$total;
    $p['stock']=$total;
    if($clean){ $keys=array_keys($clean); $p['warehouse_id']=(int)$keys[0]; }
}
function ge_apply_product_stock_delta(&$p, $delta, $preferredWarehouseId=0){
    $delta=(float)$delta;
    if(abs($delta) < 0.000001) return ['warehouse_id'=>(int)($p['warehouse_id']??0),'old'=>(float)($p['physical_stock']??$p['stock']??0),'new'=>(float)($p['physical_stock']??$p['stock']??0)];
    $map=product_warehouse_stock($p);
    $preferredWarehouseId=(int)$preferredWarehouseId;
    $productWarehouseId=(int)($p['warehouse_id']??0);
    $targetWid=0;

    // If a warehouse is explicitly selected, never silently take stock from another warehouse.
    if($preferredWarehouseId>0) $targetWid=$preferredWarehouseId;
    elseif($productWarehouseId>0 && isset($map[$productWarehouseId])) $targetWid=$productWarehouseId;
    elseif($map){ $keys=array_keys($map); $targetWid=(int)$keys[0]; }
    elseif($productWarehouseId>0) $targetWid=$productWarehouseId;

    $oldTotal=(float)array_sum($map);
    if($oldTotal == 0 && isset($p['physical_stock']) && !$map) $oldTotal=(float)$p['physical_stock'];
    if($targetWid>0){
        if(!$map && $oldTotal != 0 && $preferredWarehouseId<=0) $map[$targetWid]=$oldTotal;
        $available=(float)($map[$targetWid] ?? 0);
        if($delta < 0 && $available + $delta < -0.000001){
            throw new RuntimeException('Stock insuffisant dans l’entrepôt sélectionné pour '.trim(($p['ref']??'').' - '.($p['label']??''),' -').' : disponible '.$available.', demandé '.abs($delta).'.');
        }
        $map[$targetWid]=$available + $delta;
        if(abs($map[$targetWid]) < 0.000001) $map[$targetWid]=0;
        save_product_warehouse_stock($p,$map);
        return ['warehouse_id'=>$targetWid,'old'=>$oldTotal,'new'=>(float)($p['physical_stock']??0)];
    }

    if($delta < 0 && $oldTotal + $delta < -0.000001){
        throw new RuntimeException('Stock insuffisant pour '.trim(($p['ref']??'').' - '.($p['label']??''),' -').' : disponible '.$oldTotal.', demandé '.abs($delta).'.');
    }
    $new=$oldTotal + $delta;
    if($new<0) $new=0;
    $p['physical_stock']=$new;
    $p['stock']=$new;
    $p['virtual_stock']=$new;
    return ['warehouse_id'=>0,'old'=>$oldTotal,'new'=>$new];
}
function warehouse_product_rows($warehouse){
    $products=data_read('products', []); $rows=[]; $wid=(int)($warehouse['id']??0);
    foreach($products as $p){
        $stockMap=product_warehouse_stock($p);
        $qty=(int)($stockMap[$wid] ?? 0);
        if($qty===0) continue;
        $buy=(float)($p['purchase_price']??$p['buy_price']??0);
        $sell=(float)($p['sale_price']??0);
        $rows[]=['id'=>$p['id']??0,'ref'=>$p['ref']??'','label'=>$p['label']??'','qty'=>$qty,'unit'=>$p['unit']??'Pièce','pmp'=>$buy,'buy_value'=>$buy*$qty,'sale_price'=>$sell,'sale_value'=>$sell*$qty];
    }
    return $rows;
}
function warehouse_values($warehouse){
    $rows=warehouse_product_rows($warehouse); $buy=0; $sale=0; foreach($rows as $r){ $buy+=$r['buy_value']; $sale+=$r['sale_value']; } return [$buy,$sale,count($rows),array_sum(array_column($rows,'qty'))];
}
function warehouse_sync_products(){
    $warehouses=warehouses_all(); if(!$warehouses) return;
    $products=data_read('products', []); $default=(int)($warehouses[0]['id']??0); $changed=false;
    foreach($products as &$p){
        if(empty($p['warehouse_stock'])){
            $wid=(int)($p['warehouse_id']??0); if($wid<=0) $wid=$default;
            $qty=(int)($p['physical_stock']??$p['stock']??0);
            $p['warehouse_stock']=[$wid=>$qty]; $p['warehouse_id']=$wid; $changed=true;
        }
    }
    if($changed) data_write('products',$products);
}
function warehouse_prepare_movement($data, $nextId=0){
    if($nextId>0) $data['id']=$nextId;
    $data['date']=$data['date'] ?? date('d/m/Y H:i');
    if(empty($data['user'])) $data['user']=ge_current_author_name();
    if(empty($data['user_id'])) $data['user_id']=ge_current_author_id();
    if(empty($data['user_username'])) $data['user_username']=ge_current_author_username();
    return $data;
}
function warehouse_record_movement($data){
    $moves=data_read('warehouse_movements', []);
    $data=warehouse_prepare_movement($data, next_id($moves));
    $moves[]=$data;
    data_write('warehouse_movements',$moves);
}
function warehouse_merge_movements($newMovements){
    $moves=data_read('warehouse_movements', []);
    $next=next_id($moves);
    foreach((array)$newMovements as $m){ $moves[]=warehouse_prepare_movement($m, $next++); }
    return $moves;
}
