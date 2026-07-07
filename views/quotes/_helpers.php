<?php
function quote_ref($id){ return 'DEV-'.date('ym').'-'.str_pad((int)$id,6,'0',STR_PAD_LEFT); }
function quote_find_index($quotes,$id){ foreach($quotes as $k=>$q){ if((int)($q['id']??0)===(int)$id) return $k; } return -1; }
function quote_status_class($status){ return match($status){ 'Brouillon'=>'gray','Ouvert'=>'gold','Signée','Signée (à facturer)'=>'green','Non signée (fermée)','Refusée'=>'red','Facturée'=>'muted', default=>'gold'}; }
function quote_default_line(){ return ['description'=>'','tva'=>20,'pu_ht'=>0,'pu_ttc'=>0,'qty'=>1,'unit'=>'u.','reduction'=>'','cost_price'=>0,'total_ht'=>0]; }
function quote_products_options(){ $p=data_read('products',[]); return is_array($p)?$p:[]; }
function quote_normalize_lines_from_post(){
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
function quote_recalculate(&$quote){
    $ht=0; $tva=0; foreach(($quote['lines']??[]) as $l){ $lineHt=(float)($l['total_ht']??0); $rate=(float)($l['tva']??20); $ht+=$lineHt; $tva += $lineHt*$rate/100; }
    $quote['total_ht']=$ht; $quote['total_tva']=$tva; $quote['total_ttc']=$ht+$tva;
}
function quote_validity_end($date,$days){
    $d=DateTime::createFromFormat('d/m/Y',$date) ?: new DateTime(); $d->modify('+'.((int)$days).' days'); return $d->format('d/m/Y');
}
function quote_status_from_action($action){ return match($action){ 'validate'=>'Ouvert','sign'=>'Signée (à facturer)','refuse'=>'Non signée (fermée)','invoice'=>'Facturée', default=>null}; }
