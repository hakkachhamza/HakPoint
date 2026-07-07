<?php
function tier_filter_from_page(){
    $page=current_page();
    if(in_array($page,['prospects','prospect_new'])) return 'prospect';
    if(in_array($page,['clients','client_new'])) return 'client';
    if(in_array($page,['suppliers','supplier_new'])) return 'supplier';
    return $_GET['type'] ?? '';
}
function tier_form_type(){
    $page=current_page();
    if($page==='prospect_new') return 'prospect';
    if($page==='client_new') return 'client';
    if($page==='supplier_new') return 'supplier';
    return $_GET['type'] ?? 'client';
}
function tier_statuses(){ return ['Ouvert','Actif','Inactif','Prospect chaud','Prospect froid','Bloqué']; }
function tier_payment_terms(){ return ['','À la commande','À réception','10 jours','10 jours fin de mois','30 jours','30 jours fin de mois','50/50 Fin T','60 jours','60 jours fin de mois']; }
function tier_payment_modes(){ return ['','Virement bancaire','Chèque','Espèce','Carte bancaire','Ordre de prélèvement']; }
function tier_types(){ return ['','PME','Grande entreprise','Particulier','Administration','Association','Revendeur','Installateur','Autre']; }
function tier_legal_forms(){ return ['','SARL','SARL AU','SA','SAS','SNC','Auto-entrepreneur','Personne physique','Association','Administration']; }
function tier_staff_sizes(){ return ['','1-5','6-10','11-50','51-100','101-500','500+']; }

function tier_morocco_departments(){
    return [
        ''=>'-- Sélectionner --',
        'MA1'=>'Province de Benslimane','MA1A'=>'Préfecture de Casablanca','MA10'=>'Province de Sidi Slimane','MA11'=>'Préfecture de Casablanca','MA12'=>'Préfecture de Mohammédia','MA13'=>'Province de Médiouna','MA14'=>'Province de Nouaceur','MA15'=>'Province d’Assa-Zag','MA15A'=>'Province d’Assa-Zag','MA16'=>'Province de Laâyoune','MA16A'=>'Province d’Es-Semara','MA17'=>'Province de Tarfaya','MA17A'=>'Province de Guelmim','MA18'=>'Préfecture de Marrakech','MA18A'=>'Préfecture de Marrakech','MA19'=>'Province d’Al Haouz','MA19A'=>'Province de Tan-Tan','MA19B'=>'Province de Tan-Tan',
        'MA2'=>'Province de Khouribga','MA20'=>'Province de Chichaoua','MA21'=>'Province d’El Kelâa des Sraghna','MA22'=>'Province d’Essaouira','MA23'=>'Province de Rehamna','MA24'=>'Préfecture de Meknès','MA25'=>'Province d’El Hajeb','MA26'=>'Province d’Errachidia','MA27'=>'Province d’Ifrane','MA28'=>'Province de Khénifra','MA29'=>'Province de Midelt',
        'MA3'=>'Province de Settat','MA30'=>'Préfecture d’Oujda-Angad','MA31'=>'Province de Berkane','MA32'=>'Province de Driouch','MA33'=>'Province de Figuig','MA34'=>'Province de Jerada','MA35'=>'Province de Nador','MA36'=>'Province de Taourirt','MA37'=>'Province d’Aousserd','MA38'=>'Province d’Oued Ed-Dahab','MA39'=>'Préfecture de Rabat',
        'MA4'=>'Province d’El Jadida','MA40'=>'Préfecture de Skhirat-Témara','MA41'=>'Préfecture de Salé','MA42'=>'Province de Khémisset','MA43'=>'Préfecture d’Agadir Ida-Outanane','MA44'=>'Préfecture d’Inezgane-Aït Melloul','MA45'=>'Province de Chtouka-Aït Baha','MA46'=>'Province d’Ouarzazate','MA47'=>'Province de Sidi Ifni','MA48'=>'Province de Taroudant','MA49'=>'Province de Tinghir',
        'MA5'=>'Province de Safi','MA50'=>'Province de Tiznit','MA51'=>'Province de Zagora','MA52'=>'Province d’Azilal','MA53'=>'Province de Béni Mellal','MA54'=>'Province de Fquih Ben Salah','MA55'=>'Préfecture de M’diq-Fnideq','MA56'=>'Préfecture de Tanger-Assilah','MA57'=>'Province de Chefchaouen','MA58'=>'Province de Fahs-Anjra','MA59'=>'Province de Larache',
        'MA6'=>'Province de Sidi Bennour','MA60'=>'Province d’Ouezzane','MA61'=>'Province de Tétouan','MA62'=>'Province de Guercif','MA63'=>'Province d’Al Hoceïma','MA64'=>'Province de Taounate','MA65'=>'Province de Taza','MA66'=>'Préfecture de Fès','MA67'=>'Province de Moulay Yacoub','MA68'=>'Préfecture de Meknès','MA69'=>'Province d’El Jadida',
        'MA7'=>'Province de Youssoufia','MA7A'=>'Province de Boulemane','MA7B'=>'Province de Boulemane','MA8'=>'Province de Moulay Yacoub','MA8A'=>'Province de Kénitra','MA9'=>'Province de Sefrou','MA9A'=>'Province de Sidi Kacem'
    ];
}

function tier_countries(){ return ['Maroc (MA)','Afghanistan (AF)','Afrique du Sud (ZA)','Albanie (AL)','Algérie (DZ)','Allemagne (DE)','Andorre (AD)','Angola (AO)','Arabie Saoudite (SA)','Argentine (AR)','Arménie (AM)','Australie (AU)','Autriche (AT)','Belgique (BE)','Bénin (BJ)','Brésil (BR)','Bulgarie (BG)','Burkina Faso (BF)','Cameroun (CM)','Canada (CA)','Chili (CL)','Chine (CN)','Colombie (CO)','Côte d’Ivoire (CI)','Croatie (HR)','Danemark (DK)','Égypte (EG)','Émirats Arabes Unis (AE)','Espagne (ES)','États-Unis (US)','Finlande (FI)','France (FR)','Gabon (GA)','Ghana (GH)','Grèce (GR)','Guinée (GN)','Hongrie (HU)','Inde (IN)','Irlande (IE)','Italie (IT)','Japon (JP)','Kenya (KE)','Liban (LB)','Mali (ML)','Mauritanie (MR)','Mexique (MX)','Niger (NE)','Nigeria (NG)','Norvège (NO)','Pays-Bas (NL)','Pologne (PL)','Portugal (PT)','Qatar (QA)','Royaume-Uni (GB)','Sénégal (SN)','Suède (SE)','Suisse (CH)','Tunisie (TN)','Turquie (TR)']; }
function tier_nature_badges($t){
    $out=[];
    if(!empty($t['is_prospect'])) $out[]='<span class="mini-nature p">P</span>';
    if(!empty($t['is_client']) || ($t['type']??'')==='client') $out[]='<span class="mini-nature c">C</span>';
    if(!empty($t['is_supplier']) || ($t['type']??'')==='supplier') $out[]='<span class="mini-nature f">F</span>';
    if(!$out && ($t['type']??'')==='prospect') $out[]='<span class="mini-nature p">P</span>';
    return implode(' ', $out);
}
function tier_best_code($t){ return $t['code_client'] ?? $t['code_supplier'] ?? $t['code'] ?? ''; }
function tier_logo_src($t){ $logo=$t['logo']??''; return $logo ? 'uploads/tiers/'.rawurlencode($logo) : ''; }
function tier_default_codes($type,$id){
    return [
        'code_client'=>'CU'.date('ym').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT),
        'code_supplier'=>'SU'.date('ym').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT),
        'code_prospect'=>'PR'.date('ym').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT),
    ];
}
function tier_collect_post($old=[],$id=0){
    $type=$_POST['type'] ?? ($old['type'] ?? 'client');
    $isProspect=!empty($_POST['is_prospect']) || $type==='prospect';
    $isClient=!empty($_POST['is_client']) || $type==='client';
    $isSupplier=!empty($_POST['is_supplier']) || $type==='supplier';
    if(!$isProspect && !$isClient && !$isSupplier) $isClient=true;
    if($isClient) $type='client'; elseif($isSupplier) $type='supplier'; else $type='prospect';
    $defaults=tier_default_codes($type,$id ?: (int)($old['id']??0));
    return array_merge($old,[
        'type'=>$type,
        'is_prospect'=>$isProspect,'is_client'=>$isClient,'is_supplier'=>$isSupplier,
        'name'=>trim($_POST['name']??''),'alias'=>trim($_POST['alias']??''),
        'code'=>trim($_POST['code']??'') ?: ($old['code']??($type==='supplier'?$defaults['code_supplier']:($type==='prospect'?$defaults['code_prospect']:$defaults['code_client']))),
        'code_client'=>trim($_POST['code_client']??'') ?: ($old['code_client']??$defaults['code_client']),
        'code_supplier'=>trim($_POST['code_supplier']??'') ?: ($old['code_supplier']??$defaults['code_supplier']),
        'status'=>$_POST['status']??($old['status']??'Ouvert'),
        'address'=>trim($_POST['address']??''),'zip'=>trim($_POST['zip']??''),'city'=>trim($_POST['city']??''),'country'=>$_POST['country']??'Maroc (MA)','state'=>$_POST['state']??'',
        'phone'=>trim($_POST['phone']??''),'mobile'=>trim($_POST['mobile']??''),'fax'=>trim($_POST['fax']??''),'web'=>trim($_POST['web']??''),'email'=>trim($_POST['email']??''),
        'rc'=>trim($_POST['rc']??''),'patente'=>trim($_POST['patente']??''),'tax_id'=>trim($_POST['tax_id']??''),'cnss'=>trim($_POST['cnss']??''),'ice'=>trim($_POST['ice']??''),'vat_enabled'=>!empty($_POST['vat_enabled']),'vat_number'=>trim($_POST['vat_number']??''),'euid'=>trim($_POST['euid']??''),
        'tier_type'=>$_POST['tier_type']??'','legal_form'=>$_POST['legal_form']??'','created_company'=>trim($_POST['created_company']??''),'capital'=>trim($_POST['capital']??''),'staff_size'=>$_POST['staff_size']??'',
        'payment_terms'=>$_POST['payment_terms']??'','payment_mode'=>$_POST['payment_mode']??'','parent_company'=>trim($_POST['parent_company']??''),'owner'=>trim($_POST['owner']??ge_current_author_name()),
        'note'=>trim($_POST['note']??''),'updated_at'=>date('Y-m-d H:i:s')
    ]);
}
function tier_handle_logo_upload($row){
    try{
        $saved = ge_secure_save_upload('logo', 'tiers', ['jpg','jpeg','png','gif','webp','svg'], (int)(app_config()['uploads']['max_image_mb'] ?? 5), 'tier_'.(int)($row['id'] ?? 0));
        if($saved) $row['logo']=$saved;
    }catch(Throwable $e){
        try{ audit_log('upload_rejected', 'Tier logo: '.$e->getMessage()); }catch(Throwable $ignored){}
    }
    return $row;
}

?>
