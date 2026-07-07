<?php
require_login();
require_csrf();
header('Content-Type: application/json; charset=utf-8');

function ge_onboarding_json($ok, $data=[]){
    echo json_encode(array_merge(['ok'=>(bool)$ok], $data), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

function ge_onboarding_clean($key){
    return trim((string)($_POST[$key] ?? ''));
}

function ge_onboarding_logo_to_jpeg($source, $dest): bool {
    if(!is_file($source)) return false;
    $info=@getimagesize($source);
    if(!$info) return false;
    $type=(int)($info[2] ?? 0);
    if($type === IMAGETYPE_JPEG){
        return @copy($source, $dest);
    }
    if(!function_exists('imagecreatetruecolor')) return false;
    $img=false;
    if($type === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) $img=@imagecreatefrompng($source);
    elseif($type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) $img=@imagecreatefromwebp($source);
    elseif($type === IMAGETYPE_GIF && function_exists('imagecreatefromgif')) $img=@imagecreatefromgif($source);
    if(!$img) return false;
    $w=imagesx($img); $h=imagesy($img);
    $canvas=imagecreatetruecolor($w,$h);
    $white=imagecolorallocate($canvas,255,255,255);
    imagefilledrectangle($canvas,0,0,$w,$h,$white);
    imagecopy($canvas,$img,0,0,0,0,$w,$h);
    $ok=@imagejpeg($canvas,$dest,90);
    imagedestroy($img);
    imagedestroy($canvas);
    return (bool)$ok;
}

function ge_onboarding_save_logo(): array {
    if(empty($_FILES['company_logo']) || ($_FILES['company_logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [];
    $saved=ge_secure_save_upload('company_logo','company',['jpg','jpeg','png','webp','gif'],5,'company_logo');
    if(!$saved) return [];
    $src=__DIR__.'/../../uploads/company/'.$saved;
    $pdfName='company_pdf_logo_'.date('Ymd_His').'_'.bin2hex(random_bytes(4)).'.jpg';
    $pdfPath=__DIR__.'/../../uploads/company/'.$pdfName;
    $out=[
        'company_logo'=>'uploads/company/'.$saved,
        'company_logo_file'=>$saved,
    ];
    if(ge_onboarding_logo_to_jpeg($src,$pdfPath)){
        @chmod($pdfPath,0644);
        $out['company_pdf_logo']='uploads/company/'.$pdfName;
        $out['company_pdf_logo_file']=$pdfName;
    }
    return $out;
}

try{
    if(!is_admin()) ge_onboarding_json(false, ['error'=>'Only administrator can save setup.']);
    $action=ge_onboarding_clean('onboarding_action') ?: 'save_all';

    if($action === 'first_product_skip'){
        save_app_settings(['first_product_prompt_done'=>true,'first_product_prompt_at'=>date('Y-m-d H:i:s')]);
        ge_onboarding_json(true, ['message'=>'First product skipped.']);
    }

    if($action === 'first_product_add'){
        $label=ge_onboarding_clean('first_product_label');
        if($label==='') ge_onboarding_json(false, ['error'=>'Product name is required.']);
        $products=data_read('products',[]);
        $id=next_id($products);
        $ref=ge_onboarding_clean('first_product_ref');
        if($ref==='') $ref='PRD'.date('ym').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT);
        $warehouses=data_read('warehouses',[]);
        $warehouseName=$warehouses[0]['name'] ?? 'Entrepôt principal';
        $warehouseId=(int)($warehouses[0]['id'] ?? 1);
        $stock=(float)ge_parse_number($_POST['first_product_stock'] ?? 0);
        $taxRaw=ge_onboarding_clean('first_product_tax') ?: '20%';
        $taxRate=ge_parse_number($taxRaw);
        $products[]=[
            'id'=>$id,
            'ref'=>$ref,
            'label'=>$label,
            'sale_status'=>'En vente',
            'buy_status'=>'En achat',
            'site_visible'=>'Oui',
            'type'=>ge_onboarding_clean('first_product_type') ?: 'Produit manufacturé',
            'unit'=>ge_onboarding_clean('first_product_unit') ?: 'Pièce',
            'warehouse'=>$warehouseName,
            'warehouse_id'=>$warehouseId,
            'warehouse_stock'=>[$warehouseId=>$stock],
            'sale_price'=>(float)ge_parse_number($_POST['first_product_sale_price'] ?? 0),
            'buy_price'=>(float)ge_parse_number($_POST['first_product_buy_price'] ?? 0),
            'tax'=>is_numeric($taxRaw) ? ($taxRaw.'%') : $taxRaw,
            'tax_rate'=>$taxRate,
            'vat'=>$taxRate,
            'physical_stock'=>$stock,
            'stock'=>$stock,
            'virtual_stock'=>$stock,
            'country'=>app_setting('company_country','Maroc'),
            'created_at'=>date('Y-m-d H:i:s'),
            'updated_at'=>date('Y-m-d H:i:s')
        ];
        data_write('products',$products);
        save_app_settings(['first_product_prompt_done'=>true,'first_product_id'=>$id,'first_product_prompt_at'=>date('Y-m-d H:i:s')]);
        ge_onboarding_json(true, ['message'=>'Product created.','product_id'=>$id,'product_url'=>product_url($id)]);
    }

    $settings=[];

    // Step 1: company legal identity. Save even empty values so old demo data can be removed.
    foreach([
        'company_name','company_address','company_city','company_country','company_phone','company_email',
        'company_rc','company_patente','company_if','company_cnss','company_ice','company_tva',
        'company_capital','company_bank','company_rib'
    ] as $key){
        $settings[$key]=ge_onboarding_clean($key);
    }

    // Step 2: PDF / invoice identity and branding. Everything is user-controlled.
    foreach([
        'company_subtitle','pdf_sender_text','pdf_footer_text','pdf_footer_note','invoice_default_terms','invoice_default_note','invoice_color'
    ] as $key){
        $settings[$key]=ge_onboarding_clean($key);
    }
    foreach(['pdf_show_logo','pdf_show_sender_box','pdf_show_payment_block','pdf_show_footer','pdf_show_signature'] as $key){
        $settings[$key]=isset($_POST[$key]) ? true : false;
    }
    $settings=array_merge($settings, ge_onboarding_save_logo());

    // Step 3: SMTP / email sender config. Save even empty values so users can clear config.
    foreach([
        'smtp_host','smtp_username','smtp_password','smtp_from_email','smtp_from_name','smtp_secure','smtp_default_letter','smtp_resend_api_key'
    ] as $key){
        $settings[$key]=ge_onboarding_clean($key);
    }
    $port=ge_onboarding_clean('smtp_port');
    if($port!=='' && is_numeric($port)) $settings['smtp_port']=(int)$port;

    // Step 4: pro screen choice.
    $proChoice=ge_onboarding_clean('pro_choice');
    if($proChoice!=='') $settings['plan_intent']=$proChoice;

    $settings['onboarding_required']=true;
    $settings['onboarding_complete']=true;
    $settings['onboarding_completed_at']=date('Y-m-d H:i:s');
    $settings['onboarding_step']='done';
    if(!isset($settings['first_product_prompt_done'])) $settings['first_product_prompt_done']=false;
    save_app_settings($settings);

    if(session_status() === PHP_SESSION_ACTIVE){
        $_SESSION['ge_onboarding_force']=0;
        $_SESSION['ge_show_first_product_prompt']=1;
    }

    ge_onboarding_json(true, ['message'=>'Configuration saved.','show_product'=>true]);
}catch(Throwable $e){
    ge_onboarding_json(false, ['error'=>app_debug() ? $e->getMessage() : 'Unable to save setup.']);
}
