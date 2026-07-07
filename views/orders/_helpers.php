<?php
function order_ref($id){ return 'SO'.date('ym').'-'.str_pad((int)$id,4,'0',STR_PAD_LEFT); }
function order_find_index($orders,$id){ foreach($orders as $k=>$o){ if((int)($o['id']??0)===(int)$id) return $k; } return -1; }
function order_status_class($status){ return match($status){ 'Brouillon'=>'gray','Validée'=>'green','En cours'=>'green','Partiellement livrée'=>'orange','Livrée'=>'muted','Annulée'=>'red', default=>'green'}; }
function order_products_options(){ $p=data_read('products',[]); return is_array($p)?$p:[]; }
function order_default_line(){ return ['description'=>'','tva'=>20,'pu_ht'=>0,'pu_ttc'=>0,'qty'=>1,'unit'=>'u.','reduction'=>'','cost_price'=>0,'total_ht'=>0]; }
function order_available_clients(){
    $clients=[];
    if(function_exists('tiers_by_type')){
        foreach(tiers_by_type('client') as $t){
            $id=(int)($t['id']??0); $name=trim((string)($t['name']??$t['label']??''));
            if($name!=='') $clients[$id?:count($clients)+1]=['id'=>$id,'name'=>$name,'ref'=>$t['ref']??'','email'=>$t['email']??''];
        }
    }
    foreach(data_read('clients',[]) as $c){
        $id=(int)($c['id']??0); $name=trim((string)($c['name']??$c['label']??''));
        if($name==='') continue;
        $key=$id?:('n_'.$name);
        if(!isset($clients[$key])) $clients[$key]=['id'=>$id,'name'=>$name,'ref'=>$c['ref']??'','email'=>$c['email']??''];
    }
    return array_values($clients);
}
function order_client_from_post(){
    $clientId=(int)($_POST['client_id']??0); $clientName=trim((string)($_POST['client']??''));
    foreach(order_available_clients() as $c){
        if($clientId>0 && (int)($c['id']??0)===$clientId) return ['client_id'=>$clientId,'client'=>$c['name']];
        if($clientName!=='' && $clientName===$c['name']) return ['client_id'=>(int)($c['id']??0),'client'=>$c['name']];
    }
    return ['client_id'=>0,'client'=>''];
}
function order_normalize_lines_from_post(){
    $productIds=$_POST['line_product_id']??[];
    $descs=$_POST['line_description']??[]; $qtys=$_POST['line_qty']??[]; $units=$_POST['line_unit']??[]; $prices=$_POST['line_pu_ht']??[]; $tvas=$_POST['line_tva']??[]; $costs=$_POST['line_cost_price']??[]; $reds=$_POST['line_reduction']??[];
    $products=data_read('products',[]);
    $lines=[]; $max=max(count($descs), count($productIds));
    for($i=0;$i<$max;$i++){
        $pid=(int)($productIds[$i]??0); $p=$pid>0?find_row_by_id($products,$pid):null;
        $desc=trim((string)($descs[$i]??''));
        if($p && $desc==='') $desc=trim((string)(($p['ref']??'').' - '.($p['label']??'')), ' -');
        if($desc==='' && !$pid) continue;
        $qty=max(0,ge_parse_number($qtys[$i]??1)); $pu=max(0,ge_parse_number($prices[$i]??($p['sale_price']??0))); $tva=max(0,ge_parse_number($tvas[$i]??($p['vat']??$p['tax_rate']??20))); $cost=max(0,ge_parse_number($costs[$i]??($p['buy_price']??$p['purchase_price']??0))); $red=max(0,ge_parse_number($reds[$i]??0));
        if($qty<=0) continue;
        $unit=trim((string)($units[$i]??($p['unit']??'u.')));
        $total=$qty*$pu*(1-$red/100);
        $lines[]=['product_id'=>$pid,'product_ref'=>$p['ref']??'','product_label'=>$p['label']??'','description'=>$desc,'tva'=>$tva,'pu_ht'=>$pu,'pu_ttc'=>$pu*(1+$tva/100),'qty'=>$qty,'unit'=>$unit,'reduction'=>$red?($red.'%'):'','cost_price'=>$cost,'total_ht'=>$total];
    }
    return $lines;
}
function order_recalculate(&$order){
    $ht=0; $tva=0; foreach(($order['lines']??[]) as $l){ $lineHt=(float)($l['total_ht']??0); $rate=(float)($l['tva']??20); $ht+=$lineHt; $tva += $lineHt*$rate/100; }
    $order['total_ht']=$ht; $order['total_tva']=$tva; $order['total_ttc']=$ht+$tva;
}
