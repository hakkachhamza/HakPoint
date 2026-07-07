<?php
require_once __DIR__.'/pdf_simple.php';

function ge_pdf_setting(string $key, $default='') {
    if(function_exists('app_setting')) return app_setting($key, $default);
    $settings=function_exists('app_settings') ? app_settings() : [];
    return $settings[$key] ?? $default;
}
function ge_pdf_bool(string $key, bool $default=false): bool {
    if(function_exists('ge_bool_setting')) return ge_bool_setting($key, $default);
    $v=ge_pdf_setting($key, $default);
    if(is_bool($v)) return $v;
    if(is_numeric($v)) return ((int)$v)===1;
    return in_array(strtolower(trim((string)$v)), ['1','true','yes','on'], true);
}
function ge_pdf_logo_path(): string {
    if(!ge_pdf_bool('pdf_show_logo', false)) return '';
    $settings=function_exists('app_settings') ? app_settings() : [];
    $configured=trim((string)($settings['company_pdf_logo'] ?? $settings['company_logo'] ?? ''));
    if($configured!==''){
        $path=__DIR__.'/../'.ltrim($configured,'/');
        if(is_file($path)) return $path;
    }
    return '';
}
function ge_pdf_clean_ref($ref): string { return preg_replace('/[^A-Za-z0-9_-]+/', '-', (string)$ref); }
function ge_pdf_fmt($n, $dec=2): string { return number_format((float)$n, $dec, ',', ' '); }
function ge_pdf_company(): array {
    $c = [
        'name' => '',
        'subtitle' => '',
        'address1' => '',
        'city' => '',
        'country' => '',
        'phone' => '',
        'email' => '',
        'bank' => '',
        'rib' => '',
        'capital' => '',
        'rc' => '',
        'patente' => '',
        'if' => '',
        'cnss' => '',
        'ice' => '',
        'tva' => '',
        'sender_text' => '',
        'payment_text' => '',
        'footer_text' => '',
    ];
    $settings=function_exists('app_settings') ? app_settings() : [];
    foreach(['name'=>'company_name','subtitle'=>'company_subtitle','address1'=>'company_address','city'=>'company_city','country'=>'company_country','phone'=>'company_phone','email'=>'company_email','bank'=>'company_bank','rib'=>'company_rib','capital'=>'company_capital','rc'=>'company_rc','patente'=>'company_patente','if'=>'company_if','cnss'=>'company_cnss','ice'=>'company_ice','tva'=>'company_tva','sender_text'=>'pdf_sender_text','footer_text'=>'pdf_footer_text'] as $out=>$key){
        if(array_key_exists($key,$settings)) $c[$out]=trim((string)$settings[$key]);
    }
    $payment=trim((string)($settings['invoice_default_terms'] ?? ''));
    if($payment==='') $payment=trim((string)($settings['pdf_payment_text'] ?? ''));
    $c['payment_text']=$payment;
    return $c;
}
function ge_pdf_wrap_text(SimplePdf $pdf, float $x, float $y, string $text, int $size=8, int $lineHeight=11, int $maxChars=55, int $maxLines=0, bool $bold=false): float {
    $text=trim(str_replace(["\r\n","\r"], "\n", $text));
    if($text==='') return $y;
    $lines=[];
    foreach(explode("\n", $text) as $raw){
        $raw=trim($raw);
        if($raw===''){ $lines[]=''; continue; }
        $parts=explode("\n", wordwrap($raw, $maxChars, "\n", true));
        foreach($parts as $part) $lines[]=trim($part);
    }
    $count=0;
    foreach($lines as $line){
        if($maxLines>0 && $count>=$maxLines) break;
        $pdf->text($x,$y,$line,$size,$bold && $count===0);
        $y += $lineHeight;
        $count++;
    }
    return $y;
}
function ge_pdf_line_totals(array $lines): array {
    $ht=0; $tva=0;
    foreach ($lines as $l) {
        $pu=(float)($l['pu_ht'] ?? 0); $qty=(float)($l['qty'] ?? 1);
        $lineHt=(float)($l['total_ht'] ?? ($pu*$qty));
        $rate=(float)($l['tva'] ?? 20);
        $ht += $lineHt; $tva += $lineHt*$rate/100;
    }
    return [$ht,$tva,$ht+$tva];
}
function ge_pdf_client_box(SimplePdf $pdf, array $client, float $x=290, float $y=125): void {
    $pdf->text($x, $y-8, 'Adressé à', 8);
    $pdf->rect($x,$y,278,113,false);
    $pdf->text($x+10, $y+22, strtoupper((string)($client['name'] ?? '')), 12, true);
    $rowY=$y+37;
    foreach (['address','city','zip','country','email','phone'] as $k) {
        $v=trim((string)($client[$k] ?? ''));
        if ($v==='') continue;
        $pdf->text($x+10, $rowY, $v, 9, $k==='city');
        $rowY += 12;
        if ($rowY > $y+100) break;
    }
}
function ge_pdf_company_header(SimplePdf $pdf, string $docTitle, array $meta=[]): void {
    $c=ge_pdf_company();
    $logo=ge_pdf_logo_path();
    if ($logo !== '') $pdf->imageJpeg($logo, 38, 24, 175, 45);
    $headerText=trim((string)$c['subtitle']);
    if($headerText!=='') ge_pdf_wrap_text($pdf, 38, ($logo!=='' ? 78 : 42), $headerText, 7, 10, 65, 3);
    $pdf->textRight(555, 38, $docTitle, 14, true);
    $y=58;
    foreach ($meta as $label=>$value) {
        if ((string)$value==='') continue;
        $pdf->textRight(555, $y, $label.' : '.$value, 8);
        $y += 12;
    }
    $pdf->line(28, 96, 568, 96, 0.84, 0.5);
}
function ge_pdf_sender_box(SimplePdf $pdf): void {
    if(!ge_pdf_bool('pdf_show_sender_box', false)) return;
    $c=ge_pdf_company();
    $text=trim((string)$c['sender_text']);
    if($text===''){
        $parts=[];
        if($c['name']!=='') $parts[]=$c['name'];
        if($c['address1']!=='') $parts[]=$c['address1'];
        $cityCountry=trim($c['city'].' '.$c['country']); if($cityCountry!=='') $parts[]=$cityCountry;
        if($c['phone']!=='') $parts[]='Tél.: '.$c['phone'];
        if($c['email']!=='') $parts[]=$c['email'];
        $text=implode("\n", $parts);
    }
    if($text==='') return;
    $pdf->text(28, 117, 'Émetteur', 8);
    $pdf->rect(28,125,240,113,false);
    ge_pdf_wrap_text($pdf,38,145,$text,9,12,36,7,true);
}
function ge_pdf_find_tier_by_name(string $name): array {
    $name=trim($name);
    if ($name==='') return [];
    foreach (data_read('tiers', []) as $t) {
        if (strcasecmp(trim((string)($t['name'] ?? '')), $name)===0) return $t;
    }
    return [];
}
function ge_pdf_tier_data($tierId=0, string $fallbackName=''): array {
    $tier=[];
    if ((int)$tierId>0) $tier = find_row_by_id(data_read('tiers', []), (int)$tierId) ?: [];
    if (!$tier && $fallbackName!=='') $tier = ge_pdf_find_tier_by_name($fallbackName);
    return [
        'name' => $tier['name'] ?? $fallbackName,
        'ref' => $tier['code_client'] ?? $tier['ref'] ?? '',
        'address' => $tier['address'] ?? $tier['adresse'] ?? '',
        'city' => $tier['city'] ?? $tier['ville'] ?? '',
        'zip' => $tier['zip'] ?? $tier['postal_code'] ?? '',
        'country' => $tier['country'] ?? $tier['pays'] ?? '',
        'email' => $tier['email'] ?? '',
        'phone' => $tier['phone'] ?? $tier['tel'] ?? '',
    ];
}
function ge_pdf_doc_footer(SimplePdf $pdf, int $pageNo): void {
    if(!ge_pdf_bool('pdf_show_footer', false)){
        $pdf->textRight(562,832,(string)$pageNo,8);
        return;
    }
    $c=ge_pdf_company();
    $footer=trim((string)$c['footer_text']);
    if($footer===''){
        $lines=[];
        $line1=trim('Siège social: '.$c['name'].' - '.$c['address1'].' '.$c['city'].', '.$c['country'], " -,"); if($line1!=='Siège social:') $lines[]=$line1;
        if($c['phone']!=='') $lines[]='Téléphone: '.$c['phone'];
        $legal=[]; foreach(['Capital'=>$c['capital'],'R.C.'=>$c['rc'],'Patente'=>$c['patente'],'I.F.'=>$c['if'],'C.N.S.S.'=>$c['cnss'],'ICE'=>$c['ice'],'TVA'=>$c['tva']] as $label=>$v){ if(trim($v)!=='') $legal[]=$label.': '.$v; }
        if($legal) $lines[]=implode(' - ', $legal);
        $footer=implode("\n", $lines);
    }
    if($footer!==''){
        $pdf->line(28,790,568,790,0.85,0.5);
        ge_pdf_wrap_text($pdf, 80, 804, $footer, 7, 10, 96, 3);
    }
    $pdf->textRight(562,832,(string)$pageNo,8);
}
function ge_pdf_money_table_header(SimplePdf $pdf): void {
    $x=28; $y=270; $w=540; $h=342;
    $pdf->textRight(565, 262, 'Montants exprimés en Dirham', 8);
    $pdf->rect($x,$y,$w,$h,false); $pdf->line(28,287,568,287);
    $pdf->line(330,270,330,612); $pdf->line(375,270,375,612); $pdf->line(442,270,442,612); $pdf->line(490,270,490,612);
    $pdf->text(32,282,'Désignation',9,true); $pdf->text(343,282,'TVA',9,true); $pdf->text(395,282,'P.U. HT',9,true); $pdf->text(460,282,'Qté',9,true); $pdf->text(518,282,'Total HT',9,true);
}
function ge_pdf_money_doc(array $doc, string $type, string $ref, string $clientName, array $dates, array $lines, string $outDir, string $prefix, string $docKey, string $showPage, string $documentsTable, int $id, bool $returnFile=false) {
    [$calcHt,$calcTva,$calcTtc]=ge_pdf_line_totals($lines);
    $totalHt=(float)($doc['total_ht'] ?? $calcHt); $totalTva=(float)($doc['total_tva'] ?? $calcTva); $totalTtc=(float)($doc['total_ttc'] ?? $calcTtc);
    $client=ge_pdf_tier_data((int)($doc['client_id'] ?? $doc['tier_id'] ?? 0), $clientName);
    $pdf=new SimplePdf();
    $pageNo=1;
    $drawPage=function() use ($pdf,$type,$ref,$dates,$client,$doc,&$pageNo){
        ge_pdf_company_header($pdf, $type.' '.$ref, $dates + ['Code client'=>($client['ref'] ?: ($doc['client_ref'] ?? ''))]);
        ge_pdf_sender_box($pdf); ge_pdf_client_box($pdf, $client);
        ge_pdf_money_table_header($pdf);
        ge_pdf_doc_footer($pdf,$pageNo);
    };
    $drawPage();
    $yy=305;
    if (!$lines) $pdf->text(225, 425, 'Aucune ligne', 9);
    foreach ($lines as $line) {
        if ($yy>585) { $pdf->addPage(); $pageNo++; $drawPage(); $yy=305; }
        $desc=(string)($line['description'] ?? $line['product_label'] ?? $line['label'] ?? 'Article');
        $parts=preg_split('/\r\n|\r|\n/', wordwrap($desc, 52, "\n", true));
        foreach(array_slice($parts,0,3) as $i=>$d) $pdf->text(32,$yy+($i*10),$d,8,$i===0);
        $pu=(float)($line['pu_ht']??0); $qty=(float)($line['qty']??1); $rate=(float)($line['tva']??20); $th=(float)($line['total_ht']??($pu*$qty));
        $pdf->textRight(372,$yy,ge_pdf_fmt($rate,0).'%',8,true); $pdf->textRight(438,$yy,ge_pdf_fmt($pu),8,true); $pdf->textRight(486,$yy,(string)$qty,8,true); $pdf->textRight(565,$yy,ge_pdf_fmt($th),8,true);
        $pdf->line(28,$yy+24,568,$yy+24,0.78,0.3); $yy+=34;
    }
    if ($yy>545) { $pdf->addPage(); $pageNo++; ge_pdf_company_header($pdf, $type.' '.$ref, $dates); ge_pdf_doc_footer($pdf,$pageNo); $yy=120; }
    $c=ge_pdf_company();
    $baseY=max(626,$yy+20);
    if($baseY>700){ $pdf->addPage(); $pageNo++; ge_pdf_company_header($pdf, $type.' '.$ref, $dates); ge_pdf_doc_footer($pdf,$pageNo); $baseY=150; }
    if(ge_pdf_bool('pdf_show_payment_block', false)){
        $payment=trim((string)$c['payment_text']);
        if($payment===''){
            $parts=[];
            if($c['bank']!=='') $parts[]='Banque: '.$c['bank'];
            if($c['rib']!=='') $parts[]='RIB: '.$c['rib'];
            if($c['name']!=='') $parts[]='Titulaire: '.$c['name'];
            $payment=implode("\n", $parts);
        }
        if($payment!=='') ge_pdf_wrap_text($pdf,28,$baseY,$payment,7,11,58,8,true);
    }
    $pdf->text(340,$baseY,'Total HT',10,true); $pdf->textRight(555,$baseY,ge_pdf_fmt($totalHt),10,true); $pdf->text(340,$baseY+18,'Total TVA',10,true); $pdf->textRight(555,$baseY+18,ge_pdf_fmt($totalTva),10,true); $pdf->rect(338,$baseY+28,220,18,true,0.94); $pdf->text(340,$baseY+41,'Total TTC',10,true); $pdf->textRight(555,$baseY+41,ge_pdf_fmt($totalTtc),10,true);
    if(ge_pdf_bool('pdf_show_signature', false)){ $pdf->text(340,$baseY+70,'Cachet, Date, Signature',8); $pdf->rect(340,$baseY+80,228,50,false); }
    if(!is_dir($outDir)) mkdir($outDir,0777,true); $filename=$prefix.'_'.ge_pdf_clean_ref($ref).'.pdf'; $file=$outDir.'/'.$filename; $pdf->save($file);
    $docs=data_read($documentsTable, []); $docs=array_values(array_filter($docs, fn($d)=>!((int)($d[$docKey]??0)===$id && ($d['filename']??'')===$filename)));
    $docs[]=['id'=>next_id($docs),$docKey=>$id,'filename'=>$filename,'model'=>$doc['template']??'standard','size'=>filesize($file),'created_at'=>date('d/m/Y H:i'),'url'=>'uploads/'.basename($outDir).'/'.$filename,'path'=>$file];
    data_write($documentsTable,$docs);
    $docInfo=['filename'=>$filename,'path'=>$file,'url'=>'uploads/'.basename($outDir).'/'.$filename,'mime'=>'application/pdf','name'=>$filename];
    if($returnFile) return $docInfo;
    redirect_to('index.php?page='.$showPage.'&id='.$id.'&pdf_generated=1');
}

function ge_pdf_stock_table_header(SimplePdf $pdf): void {
    $pdf->rect(35,245,520,330,false); $pdf->line(35,265,555,265); $pdf->line(130,245,130,575); $pdf->line(430,245,430,575); $pdf->line(490,245,490,575);
    $pdf->text(42,258,'Réf.',9,true); $pdf->text(140,258,'Produit',9,true); $pdf->text(445,258,'Qté',9,true); $pdf->text(505,258,'Unité',9,true);
}
function ge_pdf_stock_doc(array $r, array $lines, string $title, string $partyLabel, string $warehouseLabel, string $outDir, string $prefix, string $docKey, string $showPage, string $documentsTable, int $id, bool $returnFile=false) {
    $pdf=new SimplePdf(); $pageNo=1;
    $drawPage=function() use ($pdf,$r,$title,$partyLabel,$warehouseLabel,&$pageNo){
        ge_pdf_company_header($pdf, $title.' '.($r['ref']??''), ['Date'=>($r['date']??''), 'État'=>($r['status']??'')]);
        $pdf->text(35,115,$partyLabel,8); $pdf->rect(35,125,245,85,true,0.92); $pdf->text(48,145,$r['tier_name']??'',12,true); $pdf->text(48,162,($r['shipping_method']??$r['method']??''),9); $pdf->text(48,176,'Réf. : '.($r['tracking']??$r['supplier_doc']??''),9);
        $pdf->text(315,115,$warehouseLabel,8); $pdf->rect(315,125,235,85,false); $pdf->text(328,145,$r['warehouse_name']??'',12,true); $pdf->text(328,162,'Date prévue : '.($r['delivery_date']??$r['order_date']??''),9);
        ge_pdf_stock_table_header($pdf);
        ge_pdf_doc_footer($pdf,$pageNo);
    };
    $drawPage();
    $y=285; if(!$lines) $pdf->text(235,420,'Aucune ligne',9);
    foreach($lines as $l){
        if($y>555){ $pdf->addPage(); $pageNo++; $drawPage(); $y=285; }
        $pdf->text(42,$y,$l['product_ref']??'',9); $label=wordwrap((string)($l['product_label']??''),45,"\n",true); foreach(array_slice(explode("\n",$label),0,2) as $i=>$part) $pdf->text(140,$y+$i*10,$part,9); $pdf->textRight(482,$y,(string)($l['qty']??0),9); $pdf->text(505,$y,$l['unit']??'u.',9); $pdf->line(35,$y+18,555,$y+18,0.8,0.3); $y+=28;
    }
    $pdf->text(35,610,'Note : '.($r['note_public']??''),9);
    if(!is_dir($outDir)) mkdir($outDir,0777,true); $filename=$prefix.'_'.ge_pdf_clean_ref($r['ref']??$id).'.pdf'; $file=$outDir.'/'.$filename; $pdf->save($file);
    $docs=data_read($documentsTable, []); $docs=array_values(array_filter($docs, fn($d)=>!((int)($d[$docKey]??0)===$id && ($d['filename']??'')===$filename)));
    $docs[]=['id'=>next_id($docs),$docKey=>$id,'filename'=>$filename,'size'=>filesize($file),'created_at'=>date('d/m/Y H:i'),'url'=>'uploads/'.basename($outDir).'/'.$filename,'path'=>$file];
    data_write($documentsTable,$docs);
    $docInfo=['filename'=>$filename,'path'=>$file,'url'=>'uploads/'.basename($outDir).'/'.$filename,'mime'=>'application/pdf','name'=>$filename];
    if($returnFile) return $docInfo;
    redirect_to('index.php?page='.$showPage.'&id='.$id.'&pdf_generated=1');
}


function ge_email_pdf_attachment(string $type, int $id): array {
    $type=strtolower($type);
    if($id<=0) return [];
    try{
        if($type==='quote'){
            require_once __DIR__.'/../views/quotes/_helpers.php';
            $doc=find_row_by_id(data_read('quotes',[]),$id); if(!$doc) return [];
            $info=ge_pdf_money_doc($doc,'Devis',$doc['ref']??quote_ref($id),trim((string)($doc['client']??'')),['Date de proposition'=>($doc['proposal_date']??''),'Date de fin de validité'=>($doc['end_date']??$doc['validity_end']??'')],$doc['lines']??[],__DIR__.'/../uploads/quotes','Devis','quote_id','quote_show','quote_documents',$id,true);
        }elseif($type==='order'){
            require_once __DIR__.'/../views/orders/_helpers.php';
            $doc=find_row_by_id(data_read('orders',[]),$id); if(!$doc) return [];
            $info=ge_pdf_money_doc($doc,'Commande',$doc['ref']??order_ref($id),trim((string)($doc['client']??'')),['Date de commande'=>($doc['order_date']??$doc['date']??''),'Date livraison'=>($doc['delivery_date']??'')],$doc['lines']??[],__DIR__.'/../uploads/orders','Commande','order_id','order_show','order_documents',$id,true);
        }elseif($type==='invoice'){
            require_once __DIR__.'/../views/invoices/_helpers.php';
            $doc=find_row_by_id(data_read('invoices',[]),$id); if(!$doc) return [];
            $dates=['Date facturation'=>($doc['invoice_date']??''),'Date échéance'=>($doc['due_date']??'')]; if(!empty($doc['order_id'])) $dates['Commande source']=$doc['order_ref'] ?? ('#'.$doc['order_id']);
            $info=ge_pdf_money_doc($doc,'Facture',$doc['ref']??invoice_ref($id),trim((string)($doc['client']??'')),$dates,$doc['lines']??[],__DIR__.'/../uploads/invoices','Facture','invoice_id','invoice_show','invoice_documents',$id,true);
        }elseif($type==='expedition'){
            require_once __DIR__.'/../views/expeditions/_helpers.php';
            $doc=expedition_find($id); if(!$doc) return [];
            $info=ge_pdf_stock_doc($doc, expedition_lines($id), 'Bon d\'expédition', 'Client', 'Entrepôt source', __DIR__.'/../uploads/expeditions', 'Expedition', 'expedition_id', 'expedition_show', 'expedition_documents', $id, true);
        }elseif($type==='reception'){
            require_once __DIR__.'/../views/receptions/_helpers.php';
            $doc=reception_find($id); if(!$doc) return [];
            $info=ge_pdf_stock_doc($doc, reception_lines($id), 'Bon de réception', 'Fournisseur', 'Entrepôt destination', __DIR__.'/../uploads/receptions', 'Reception', 'reception_id', 'reception_show', 'reception_documents', $id, true);
        }else return [];
        if(!empty($info['path']) && is_file($info['path'])) return ['name'=>$info['filename'] ?? basename($info['path']),'tmp'=>$info['path'],'mime'=>'application/pdf'];
    }catch(Throwable $e){ try{ audit_log('pdf_email_attachment_error',$type.' #'.$id.' : '.$e->getMessage()); }catch(Throwable $ignored){} }
    return [];
}
function ge_email_attachments_with_optional_pdf(string $type, int $id, string $field='attachments'): array {
    $attachments=mail_uploaded_attachments($field);
    if(!empty($_POST['attach_main'])){
        $pdf=ge_email_pdf_attachment($type,$id);
        if($pdf) array_unshift($attachments,$pdf);
    }
    return $attachments;
}
