<?php
function invoice_badge_class($status){
    return match($status){
        'Brouillon'=>'gray','Impayée'=>'gold','Payée'=>'muted','Abandonnée'=>'red', default=>'gold'
    };
}
function invoice_status_label($status){ return $status ?: 'Brouillon'; }
function invoice_ref($id){ return 'FAC-'.date('y-m').'-'.str_pad((int)$id,6,'0',STR_PAD_LEFT); }
function invoice_default_lines(){
    return [
        ['description'=>'','tva'=>20,'pu_ht'=>0,'pu_ttc'=>0,'qty'=>1,'unit'=>'u.','reduction'=>'','cost_price'=>0,'total_ht'=>0]
    ];
}

function invoice_lines_from_order(array $order): array{
    $lines=[];
    $products=data_read('products', []);
    foreach(($order['lines'] ?? []) as $l){
        $pid=(int)($l['product_id'] ?? 0);
        $p=$pid>0 ? (find_row_by_id($products,$pid) ?: []) : [];
        $desc=trim((string)($l['description'] ?? $l['product_label'] ?? ''));
        if($desc==='' && $p) $desc=trim((string)(($p['ref'] ?? '').' - '.($p['label'] ?? '')), ' -');
        if($desc==='') continue;
        $qty=(float)($l['qty'] ?? 0);
        $pu=(float)($l['pu_ht'] ?? ($p['sale_price'] ?? 0));
        $tva=(float)($l['tva'] ?? ($p['vat'] ?? $p['tax_rate'] ?? 20));
        $cost=(float)($l['cost_price'] ?? ($p['buy_price'] ?? $p['purchase_price'] ?? 0));
        $lines[]=[
            'product_id'=>$pid,
            'product_ref'=>$l['product_ref'] ?? ($p['ref'] ?? ''),
            'product_label'=>$l['product_label'] ?? ($p['label'] ?? ''),
            'description'=>$desc,
            'tva'=>$tva,
            'pu_ht'=>$pu,
            'pu_ttc'=>(float)($l['pu_ttc'] ?? ($pu*(1+$tva/100))),
            'qty'=>$qty,
            'unit'=>trim((string)($l['unit'] ?? ($p['unit'] ?? 'u.'))),
            'reduction'=>$l['reduction'] ?? '',
            'cost_price'=>$cost,
            'total_ht'=>(float)($l['total_ht'] ?? ($qty*$pu))
        ];
    }
    return $lines ?: invoice_default_lines();
}

function invoice_lines_from_expedition(array $expedition): array{
    $lines=[];
    $products=data_read('products', []);
    foreach(data_read('expedition_lines', []) as $l){
        if((int)($l['expedition_id'] ?? 0) !== (int)($expedition['id'] ?? 0)) continue;
        $pid=(int)($l['product_id'] ?? 0);
        $p=find_row_by_id($products, $pid) ?: [];
        $desc=trim((string)(($l['product_ref'] ?? '') . ' - ' . ($l['product_label'] ?? '')));
        $desc=trim($desc, ' -');
        if($desc==='') $desc=trim((string)(($p['ref'] ?? '') . ' - ' . ($p['label'] ?? '')));
        $desc=trim($desc, ' -');
        if($desc==='') continue;
        $qty=(float)($l['qty'] ?? 0);
        $pu=(float)($p['sale_price'] ?? $p['price'] ?? 0);
        $tva=(float)($p['vat'] ?? 20);
        $cost=(float)($p['buy_price'] ?? $p['purchase_price'] ?? 0);
        $lines[]=[
            'product_id'=>$pid,
            'product_ref'=>$l['product_ref'] ?? ($p['ref'] ?? ''),
            'product_label'=>$l['product_label'] ?? ($p['label'] ?? ''),
            'description'=>$desc,
            'tva'=>$tva,
            'pu_ht'=>$pu,
            'pu_ttc'=>$pu*(1+$tva/100),
            'qty'=>$qty,
            'unit'=>trim((string)($l['unit'] ?? ($p['unit'] ?? 'u.'))),
            'reduction'=>'',
            'cost_price'=>$cost,
            'total_ht'=>$qty*$pu
        ];
    }
    return $lines ?: invoice_default_lines();
}
function invoice_mark_expedition_link(int $expeditionId, int $invoiceId, string $invoiceRef): void{
    if($expeditionId<=0) return;
    $rows=data_read('expeditions', []);
    $changed=false;
    foreach($rows as &$e){
        if((int)($e['id'] ?? 0) !== $expeditionId) continue;
        $ids=$e['invoice_ids'] ?? [];
        if(!is_array($ids)) $ids=[];
        if(!in_array($invoiceId, array_map('intval',$ids), true)) $ids[]=$invoiceId;
        $e['invoice_ids']=$ids;
        $e['invoice_id']=$invoiceId;
        $e['invoice_ref']=$invoiceRef;
        $e['facture']='Oui';
        $e['invoiced_at']=date('d/m/Y H:i');
        $changed=true;
        break;
    }
    unset($e);
    if($changed) data_write('expeditions', $rows);
}

function invoice_mark_order_link(int $orderId, int $invoiceId, string $invoiceRef): void{
    if($orderId<=0) return;
    $orders=data_read('orders',[]);
    $changed=false;
    foreach($orders as &$o){
        if((int)($o['id'] ?? 0) !== $orderId) continue;
        $ids=$o['invoice_ids'] ?? [];
        if(!is_array($ids)) $ids=[];
        if(!in_array($invoiceId, array_map('intval',$ids), true)) $ids[]=$invoiceId;
        $o['invoice_ids']=$ids;
        $o['invoice_id']=$invoiceId;
        $o['invoice_ref']=$invoiceRef;
        $o['facture']='Oui';
        $o['invoiced_at']=date('d/m/Y H:i');
        $changed=true;
        break;
    }
    unset($o);
    if($changed) data_write('orders',$orders);
}

function invoice_totals($invoice){
    $lines = $invoice['lines'] ?? [];
    $ht = 0;
    foreach($lines as $l){ $ht += (float)($l['total_ht'] ?? ((float)($l['pu_ht']??0)*(float)($l['qty']??1))); }
    if(!$ht) $ht = (float)($invoice['total_ht'] ?? 0);
    $tva = (float)($invoice['total_tva'] ?? ($ht * 0.20));
    $ttc = (float)($invoice['total_ttc'] ?? ($ht + $tva));
    return [$ht,$tva,$ttc];
}

function invoice_products_options(){
    $products=data_read('products',[]);
    return is_array($products)?$products:[];
}
function invoice_normalize_lines_from_post(){
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
    return $lines ?: invoice_default_lines();
}
function invoice_recalculate(&$invoice){
    $lines=$invoice['lines']??[];
    $ht=0; $tva=0;
    foreach($lines as $l){
        $lineHt=(float)($l['total_ht']??((float)($l['pu_ht']??0)*(float)($l['qty']??0)));
        $rate=(float)($l['tva']??20);
        $ht += $lineHt;
        $tva += $lineHt*($rate/100);
    }
    $invoice['total_ht']=$ht;
    $invoice['total_tva']=$tva;
    $invoice['total_ttc']=$ht+$tva;
}
function invoice_find_index($invoices,$id){ foreach($invoices as $k=>$i){ if((int)($i['id']??0)===(int)$id) return $k; } return -1; }
