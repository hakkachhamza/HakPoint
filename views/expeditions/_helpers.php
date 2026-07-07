<?php
function expeditions_all(){ return data_read('expeditions', []); }
function expedition_find($id){ return find_row_by_id(expeditions_all(), (int)$id); }
function expedition_ref($id){ return 'SH'.date('ym').'-'.str_pad((string)$id,4,'0',STR_PAD_LEFT); }
function expedition_statuses(){ return ['Brouillon','Validée','Préparée','Expédiée','Livrée','Annulée']; }
function expedition_status_badge($s){ return ['Brouillon'=>'gray','Validée'=>'blue','Préparée'=>'orange','Expédiée'=>'green','Livrée'=>'green','Annulée'=>'red'][$s] ?? 'gray'; }
function expedition_lines($id){ return array_values(array_filter(data_read('expedition_lines', []), fn($l)=>(int)($l['expedition_id']??0)===(int)$id)); }
function expedition_documents($id){ return array_values(array_filter(data_read('expedition_documents', []), fn($d)=>(int)($d['expedition_id']??0)===(int)$id)); }
function expedition_qty_total($id){ $t=0; foreach(expedition_lines($id) as $l){ $t+=(float)($l['qty']??0); } return $t; }
function expedition_products_count($id){ $ids=[]; foreach(expedition_lines($id) as $l){ $ids[(int)($l['product_id']??0)]=true; } return count(array_filter(array_keys($ids))); }
function expedition_save($row){ $rows=expeditions_all(); $found=false; foreach($rows as &$r){ if((int)($r['id']??0)===(int)($row['id']??0)){ $r=$row; $found=true; break; } } if(!$found) $rows[]=$row; data_write('expeditions',$rows); }
function order_update_delivery_status($orderId){
    $orderId=(int)$orderId; if($orderId<=0) return;
    $orders=data_read('orders', []); $orderIdx=-1; $order=null;
    foreach($orders as $k=>$o){ if((int)($o['id']??0)===$orderId){ $orderIdx=$k; $order=$o; break; } }
    if($orderIdx<0 || !$order) return;
    $ordered=[];
    foreach(($order['lines'] ?? []) as $l){
        $pid=(int)($l['product_id'] ?? 0); $qty=(float)($l['qty'] ?? 0);
        if($pid>0 && $qty>0) $ordered[$pid]=($ordered[$pid] ?? 0)+$qty;
    }
    if(!$ordered) return;
    $shipped=[];
    foreach(data_read('expeditions', []) as $e){
        if((int)($e['order_id']??0)!==$orderId || empty($e['stock_done']) || !in_array(($e['status']??''), ['Expédiée','Livrée'], true)) continue;
        foreach(expedition_lines((int)($e['id']??0)) as $l){
            $pid=(int)($l['product_id']??0); $qty=(float)($l['qty']??0);
            if($pid>0 && $qty>0) $shipped[$pid]=($shipped[$pid] ?? 0)+$qty;
        }
    }
    $any=false; $all=true;
    foreach($ordered as $pid=>$qty){
        $sq=(float)($shipped[$pid] ?? 0);
        if($sq>0) $any=true;
        if($sq + 0.000001 < $qty) $all=false;
    }
    $newStatus=$all ? 'Livrée' : ($any ? 'Partiellement livrée' : (($orders[$orderIdx]['status'] ?? '')==='Livrée' ? 'En cours' : ($orders[$orderIdx]['status'] ?? 'Brouillon')));
    $orders[$orderIdx]['status']=$newStatus;
    $orders[$orderIdx]['delivered_qty_by_product']=$shipped;
    $orders[$orderIdx]['updated_at']=date('d/m/Y H:i');
    data_write('orders',$orders);
}
function expedition_delete_all($id){
    $id=(int)$id;
    $e=expedition_find($id);
    if($e && !empty($e['stock_done'])){
        expedition_update_stock($e,'in');
        $e['stock_done']=false;
    }
    data_write('expeditions', array_values(array_filter(expeditions_all(), fn($r)=>(int)($r['id']??0)!==$id)));
    data_write('expedition_lines', array_values(array_filter(data_read('expedition_lines', []), fn($l)=>(int)($l['expedition_id']??0)!==$id)));
    $docs=data_read('expedition_documents', []); $kept=[];
    foreach($docs as $d){ if((int)($d['expedition_id']??0)===$id){ ge_unlink_document_file($d); continue; } $kept[]=$d; }
    data_write('expedition_documents', $kept);
    if($e && !empty($e['order_id']) && function_exists('order_update_delivery_status')) order_update_delivery_status((int)$e['order_id']);
}
function expedition_resolve_product($line, $products){
    $pid=(int)($line['product_id']??0);
    if($pid>0){ $p=find_row_by_id($products,$pid); if($p) return $p; }
    $needles=[];
    foreach(['product_ref','product_label','description','label'] as $k){
        $v=trim((string)($line[$k]??'')); if($v!=='') $needles[]=mb_strtolower($v,'UTF-8');
    }
    if(!$needles) return null;
    foreach($products as $p){
        $ref=mb_strtolower(trim((string)($p['ref']??'')),'UTF-8');
        $label=mb_strtolower(trim((string)($p['label']??'')),'UTF-8');
        foreach($needles as $n){
            if($ref!=='' && (str_contains($n,$ref) || str_contains($ref,$n))) return $p;
            if($label!=='' && (str_contains($n,$label) || str_contains($label,$n))) return $p;
        }
    }
    return null;
}
function expedition_stock_errors($expedition){
    $wid=(int)($expedition['warehouse_id']??0);
    $products=data_read('products', []);
    $errors=[];
    if($wid<=0) $errors[]='Choisis un entrepôt source pour cette expédition.';
    foreach(expedition_lines($expedition['id']) as $line){
        $qty=(float)($line['qty']??0); if($qty<=0) continue;
        $p=expedition_resolve_product($line,$products);
        if(!$p){ $errors[]='Produit introuvable pour une ligne. Choisis un vrai produit dans les lignes d’expédition.'; continue; }
        $map=function_exists('product_warehouse_stock') ? product_warehouse_stock($p) : [];
        $available=$wid>0 ? (float)($map[$wid] ?? 0) : (float)($p['physical_stock']??$p['stock']??0);
        if($available < $qty){
            $errors[]='Stock insuffisant dans l’entrepôt sélectionné pour '.trim(($p['ref']??'').' - '.($p['label']??''),' -').' : disponible '.$available.', demandé '.$qty.'.';
        }
    }
    return $errors;
}
function expedition_update_stock($expedition, $direction='out'){
    $wid=(int)($expedition['warehouse_id']??0);
    if($wid<=0) throw new RuntimeException('Choisis un entrepôt source pour cette expédition.');
    $products=data_read('products', []);
    $lines=expedition_lines($expedition['id']);
    if(!$lines) throw new RuntimeException('Aucune ligne produit dans cette expédition. Crée une expédition avec un produit réel.');

    $lineOps=[];
    foreach($lines as $line){
        $qty=(float)($line['qty']??0); if($qty<=0) continue;
        $p=expedition_resolve_product($line,$products);
        if(!$p) throw new RuntimeException('Produit introuvable pour une ligne. Choisis un produit réel dans l’expédition.');
        $pid=(int)($p['id']??0);
        if($pid<=0) continue;
        $lineOps[]=['product_id'=>$pid,'qty'=>$qty,'line'=>$line];
    }
    if(!$lineOps) throw new RuntimeException('Aucune ligne produit valide dans cette expédition.');

    $movements=[];
    foreach($lineOps as $op){
        foreach($products as &$p){
            if((int)($p['id']??0)===(int)$op['product_id']){
                $qtyDelta = $direction==='out' ? -abs((float)$op['qty']) : abs((float)$op['qty']);
                $result=ge_apply_product_stock_delta($p,$qtyDelta,$wid);
                $moveWid=(int)($result['warehouse_id'] ?? $wid);
                $movements[]=[
                    'type'=>$direction==='out'?'Expédition':'Annulation expédition',
                    'warehouse_id'=>$moveWid,
                    'warehouse_name'=>$expedition['warehouse_name']??'',
                    'product_id'=>(int)($p['id']??0),
                    'product_ref'=>$p['ref']??'',
                    'product_label'=>$p['label']??'',
                    'qty'=>$qtyDelta,
                    'source_ref'=>$expedition['ref']??'',
                    'user'=>ge_current_author_name(),
                    'user_id'=>ge_current_author_id(),
                    'user_username'=>ge_current_author_username()
                ];
                break;
            }
        }
        unset($p);
    }
    if(!$movements) throw new RuntimeException('Aucun mouvement de stock valide pour cette expédition.');
    $allMoves=function_exists('warehouse_merge_movements') ? warehouse_merge_movements($movements) : data_read('warehouse_movements', []);
    if(function_exists('data_write_batch')) data_write_batch(['products'=>$products,'warehouse_movements'=>$allMoves]);
    else { data_write('products',$products); data_write('warehouse_movements',$allMoves); }
}
function expedition_set_status($id,$status,&$error=''){
    $e=expedition_find($id); if(!$e) return false;
    try{
        if(in_array($status,['Expédiée','Livrée'],true) && empty($e['stock_done'])){
            expedition_update_stock($e,'out');
            $e['stock_done']=true; $e['shipped_at']=date('d/m/Y H:i');
        }
        if($status==='Annulée' && !empty($e['stock_done'])){
            expedition_update_stock($e,'in');
            $e['stock_done']=false; $e['cancelled_stock_at']=date('d/m/Y H:i');
        }
        $e['status']=$status; $e['updated_at']=date('d/m/Y H:i'); expedition_save($e); if(!empty($e['order_id'])) order_update_delivery_status((int)$e['order_id']); return true;
    }catch(Throwable $ex){
        $error=$ex->getMessage();
        try{ audit_log('shipment_stock_blocked', 'Expédition '.($e['ref']??$id).' : '.$error); }catch(Throwable $ignored){}
        return false;
    }
}
if(!function_exists('format_date_fr')){
    function format_date_fr($date){
        if(!$date) return '';
        if(preg_match('/^\d{4}-\d{2}-\d{2}/',$date)){
            $ts=strtotime($date); return $ts?date('d/m/Y',$ts):$date;
        }
        return $date;
    }
}
function expedition_display_ref($r){ $ref=$r['ref']??''; return substr($ref,0,2)==='SH' ? $ref : preg_replace('/^EXP-?/','SH',$ref); }
