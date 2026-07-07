<?php
function receptions_all(){ return data_read('receptions', []); }
function reception_find($id){ return find_row_by_id(receptions_all(), (int)$id); }
function reception_ref($id){ return 'REC-'.date('ym').'-'.str_pad((string)$id,6,'0',STR_PAD_LEFT); }
function reception_statuses(){ return ['Brouillon','Validée','Reçue','Contrôlée','Annulée']; }
function reception_status_badge($s){ return ['Brouillon'=>'gray','Validée'=>'blue','Reçue'=>'green','Contrôlée'=>'orange','Annulée'=>'red'][$s] ?? 'gray'; }
function reception_lines($id){ return array_values(array_filter(data_read('reception_lines', []), fn($l)=>(int)($l['reception_id']??0)===(int)$id)); }
function reception_documents($id){ return array_values(array_filter(data_read('reception_documents', []), fn($d)=>(int)($d['reception_id']??0)===(int)$id)); }
function reception_qty_total($id){ $t=0; foreach(reception_lines($id) as $l){ $t+=(float)($l['qty']??0); } return $t; }
function reception_products_count($id){ $ids=[]; foreach(reception_lines($id) as $l){ $ids[(int)($l['product_id']??0)]=true; } return count(array_filter(array_keys($ids))); }
function reception_save($row){ $rows=receptions_all(); $found=false; foreach($rows as &$r){ if((int)($r['id']??0)===(int)($row['id']??0)){ $r=$row; $found=true; break; } } if(!$found) $rows[]=$row; data_write('receptions',$rows); }
function reception_delete_all($id){
    $id=(int)$id;
    $r=reception_find($id);
    if($r && !empty($r['stock_done'])){
        reception_update_stock($r,'out');
        $r['stock_done']=false;
    }
    data_write('receptions', array_values(array_filter(receptions_all(), fn($r)=>(int)($r['id']??0)!==$id)));
    data_write('reception_lines', array_values(array_filter(data_read('reception_lines', []), fn($l)=>(int)($l['reception_id']??0)!==$id)));
    $docs=data_read('reception_documents', []); $kept=[];
    foreach($docs as $d){ if((int)($d['reception_id']??0)===$id){ ge_unlink_document_file($d); continue; } $kept[]=$d; }
    data_write('reception_documents', $kept);
}
function reception_update_stock($reception, $direction='in'){
    $wid=(int)($reception['warehouse_id']??0);
    if($wid<=0) throw new RuntimeException('Choisis un entrepôt pour cette réception.');
    $products=data_read('products', []);
    $lines=reception_lines($reception['id']);
    if(!$lines) throw new RuntimeException('Aucune ligne produit dans cette réception.');
    $movements=[];
    foreach($lines as $line){
        $pid=(int)($line['product_id']??0);
        $qty=(float)($line['qty']??0);
        if(!$pid || $qty<=0) continue;
        $found=false;
        foreach($products as &$p){
            if((int)($p['id']??0)===$pid){
                $found=true;
                $qtyDelta=$direction==='in'?abs($qty):-abs($qty);
                $result=ge_apply_product_stock_delta($p,$qtyDelta,$wid);
                $moveWid=(int)($result['warehouse_id'] ?? $wid);
                $movements[]=[
                    'type'=>$direction==='in'?'Réception':'Annulation réception',
                    'warehouse_id'=>$moveWid,
                    'warehouse_name'=>$reception['warehouse_name']??'',
                    'product_id'=>$pid,
                    'product_ref'=>$p['ref']??'',
                    'product_label'=>$p['label']??'',
                    'qty'=>$qtyDelta,
                    'source_ref'=>$reception['ref']??'',
                    'user'=>ge_current_author_name(),
                    'user_id'=>ge_current_author_id(),
                    'user_username'=>ge_current_author_username()
                ];
                break;
            }
        }
        unset($p);
        if(!$found) throw new RuntimeException('Produit introuvable dans une ligne de réception.');
    }
    if(!$movements) throw new RuntimeException('Aucune ligne produit valide dans cette réception.');
    $allMoves=function_exists('warehouse_merge_movements') ? warehouse_merge_movements($movements) : data_read('warehouse_movements', []);
    if(function_exists('data_write_batch')) data_write_batch(['products'=>$products,'warehouse_movements'=>$allMoves]);
    else { data_write('products',$products); data_write('warehouse_movements',$allMoves); }
}
function reception_set_status($id,$status){
    $r=reception_find($id); if(!$r) return;
    if(!in_array($status, reception_statuses(), true)) throw new RuntimeException('Statut de réception invalide.');
    if(!empty($r['stock_done']) && !in_array($status,['Reçue','Contrôlée','Annulée'],true)){
        throw new RuntimeException('Cette réception est déjà ajoutée au stock. Annulez-la avant de revenir à un statut non stocké.');
    }
    if(in_array($status,['Reçue','Contrôlée'],true) && empty($r['stock_done'])){
        reception_update_stock($r,'in');
        $r['stock_done']=true;
        $r['received_at']=date('d/m/Y H:i');
    }
    if($status==='Annulée' && !empty($r['stock_done'])){
        reception_update_stock($r,'out');
        $r['stock_done']=false;
        $r['cancelled_stock_at']=date('d/m/Y H:i');
    }
    $r['status']=$status;
    $r['updated_at']=date('d/m/Y H:i');
    reception_save($r);
}
