<?php
/**
 * Real relational schema layer for GLOBAL ENERGIE.
 *
 * The previous version stored most business rows as one JSON payload per row.
 * This layer keeps the current UI compatible, but writes the important business
 * fields into real SQL columns. JSON is now only a compatibility/extra field for
 * non-critical or complex values such as arrays.
 */

function ge_real_schema(){
    static $schema=null;
    if($schema!==null) return $schema;

    $str90='VARCHAR(90) NULL'; $str120='VARCHAR(120) NULL'; $str190='VARCHAR(190) NULL'; $str255='VARCHAR(255) NULL';
    $text='TEXT NULL'; $money='DECIMAL(15,3) NOT NULL DEFAULT 0'; $qty='DECIMAL(15,3) NOT NULL DEFAULT 0';
    $int='INT NULL'; $bool='TINYINT(1) NOT NULL DEFAULT 0'; $dt='VARCHAR(40) NULL';

    $tierCols=[
        'type'=>$str90,'is_prospect'=>$bool,'is_client'=>$bool,'is_supplier'=>$bool,
        'name'=>$str190,'alias'=>$str190,'code'=>$str90,'code_client'=>$str90,'code_supplier'=>$str90,
        'status'=>$str90,'address'=>$text,'zip'=>$str90,'city'=>$str120,'country'=>$str120,'state'=>$str120,
        'phone'=>$str120,'mobile'=>$str120,'fax'=>$str120,'web'=>$str190,'email'=>$str190,
        'rc'=>$str90,'patente'=>$str90,'tax_id'=>$str90,'cnss'=>$str90,'ice'=>$str90,'vat_enabled'=>$bool,'vat_number'=>$str90,'euid'=>$str120,
        'tier_type'=>$str120,'legal_form'=>$str120,'created_company'=>$str90,'capital'=>$str90,'staff_size'=>$str90,
        'payment_terms'=>$str120,'payment_mode'=>$str120,'parent_company'=>$str190,'owner'=>$str190,
        'logo'=>$str255,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt
    ];

    $schema=[
        'users'=>[
            'columns'=>[
                'ref'=>$str90,'civility'=>$str90,'name'=>$str190,'firstname'=>$str190,'username'=>$str190,'gender'=>$str90,
                'employee'=>$bool,'manager_id'=>$int,'expense_validator'=>$str90,'external_user'=>$str90,
                'valid_from'=>$dt,'valid_to'=>$dt,'address'=>$text,'zip'=>$str90,'city'=>$str120,'country'=>$str120,'state'=>$str120,
                'phone'=>$str120,'mobile'=>$str120,'fax'=>$str120,'email'=>$str190,'signature'=>$text,
                'note_public'=>$text,'note_private'=>$text,'job'=>$str190,'weekly_hours'=>$str90,'hire_date'=>$dt,'birth_date'=>$dt,
                'salary'=>$str90,'hourly_rate'=>$str90,'daily_rate'=>$str90,'company'=>$str190,'role'=>$str120,'status'=>$str90,
                'password'=>$str255,'force_password_change'=>$bool,'profile_picture'=>$str255,'twofa_secret'=>$str190,'twofa_enabled'=>$bool,
                'last_login'=>$dt,'created_at'=>$dt,'updated_at'=>$dt
            ],
            'extra_keep'=>['permissions','security_questions','preferences']
        ],
        'warehouses'=>[
            'columns'=>[
                'ref'=>$str90,'name'=>$str190,'parent_id'=>$int,'description'=>$text,'address'=>$text,'zip'=>$str90,'city'=>$str120,
                'country'=>$str120,'phone'=>$str120,'fax'=>$str120,'status'=>$str90,'created_at'=>$dt,'updated_at'=>$dt
            ]
        ],
        'warehouse_movements'=>[
            'columns'=>[
                'warehouse_id'=>$int,'warehouse_name'=>$str190,'product_id'=>$int,'product_ref'=>$str90,'product_label'=>$str190,
                'qty'=>$qty,'type'=>$str90,'movement_type'=>$str90,'source_type'=>$str90,'source_id'=>$int,'note'=>$text,'date'=>$dt,'created_at'=>$dt
            ]
        ],
        'products'=>[
            'columns'=>[
                'ref'=>$str90,'label'=>$str190,'sale_status'=>$str90,'buy_status'=>$str90,'description'=>$text,'public_url'=>$str255,
                'site_visible'=>$str90,'warehouse'=>$str190,'warehouse_id'=>$int,'alert_stock'=>$qty,'desired_stock'=>$qty,
                'type'=>$str120,'product_type'=>$str120,'weight'=>$str90,'length'=>$str90,'width'=>$str90,'height'=>$str90,'surface'=>$str90,'volume'=>$str90,
                'unit'=>$str90,'customs_code'=>$str120,'country'=>$str120,'note'=>$text,'sale_price'=>$money,'sale_tax_mode'=>$str90,
                'min_sale_price'=>$money,'tax'=>$str90,'tax_rate'=>$qty,'vat'=>$qty,'buy_price'=>$money,'physical_stock'=>$qty,'stock'=>$qty,
                'virtual_stock'=>$qty,'image'=>$str255,'created_at'=>$dt,'updated_at'=>$dt
            ],
            'extra_keep'=>['warehouse_stock','sale_prices','purchase_prices','files','events']
        ],
        'tiers'=>['columns'=>$tierCols],
        'clients'=>['columns'=>$tierCols],
        'suppliers'=>['columns'=>$tierCols],

        'quotes'=>[
            'columns'=>[
                'ref'=>$str90,'client_id'=>$int,'client_ref'=>$str90,'client'=>$str190,'proposal_date'=>$dt,'validity'=>$str90,'end_date'=>$dt,
                'payment_terms'=>$str120,'payment_mode'=>$str120,'origin'=>$str120,'delivery_delay'=>$str120,'shipping_method'=>$str120,
                'delivery_date'=>$dt,'template'=>$str120,'public_note'=>$text,'private_note'=>$text,'status'=>$str90,
                'total_ht'=>$money,'total_tva'=>$money,'total_ttc'=>$money,'amount_ht'=>$money,'amount_ttc'=>$money,
                'author'=>$str190,'author_id'=>$int,'author_username'=>$str190,'created_at'=>$dt,'updated_at'=>$dt
            ],
            'line_collection'=>'quote_lines','line_fk'=>'quote_id','extra_exclude'=>['lines']
        ],
        'quote_lines'=>[
            'columns'=>[
                'quote_id'=>$int,'line_no'=>$int,'product_id'=>$int,'product_ref'=>$str90,'product_label'=>$str190,
                'description'=>$text,'tva'=>$qty,'pu_ht'=>$money,'qty'=>$qty,'unit'=>$str90,'reduction'=>$str90,
                'cost_price'=>$money,'total_ht'=>$money,'total_ttc'=>$money
            ]
        ],
        'orders'=>[
            'columns'=>[
                'ref'=>$str90,'client_id'=>$int,'client_ref'=>$str90,'client'=>$str190,'order_date'=>$dt,'delivery_date'=>$dt,'delivery_delay'=>$str120,
                'payment_terms'=>$str120,'payment_mode'=>$str120,'shipping_method'=>$str120,'channel'=>$str120,'template'=>$str120,
                'public_note'=>$text,'private_note'=>$text,'status'=>$str90,'expediable'=>$str90,'facture'=>$str90,
                'delivery_status'=>$str90,'invoice_id'=>$int,'invoice_ref'=>$str90,
                'total_ht'=>$money,'total_tva'=>$money,'total_ttc'=>$money,'amount_ht'=>$money,'amount_ttc'=>$money,
                'author'=>$str190,'author_id'=>$int,'author_username'=>$str190,'created_at'=>$dt,'updated_at'=>$dt
            ],
            'line_collection'=>'order_lines','line_fk'=>'order_id','extra_exclude'=>['lines']
        ],
        'order_lines'=>[
            'columns'=>[
                'order_id'=>$int,'line_no'=>$int,'product_id'=>$int,'product_ref'=>$str90,'product_label'=>$str190,
                'description'=>$text,'tva'=>$qty,'pu_ht'=>$money,'qty'=>$qty,'unit'=>$str90,'reduction'=>$str90,
                'cost_price'=>$money,'total_ht'=>$money,'total_ttc'=>$money
            ]
        ],
        'invoices'=>[
            'columns'=>[
                'ref'=>$str90,'client_id'=>$int,'client_ref'=>$str90,'client'=>$str190,'type'=>$str120,'invoice_date'=>$dt,'due_date'=>$dt,
                'payment_terms'=>$str120,'payment_mode'=>$str120,'bank_account'=>$str120,'template'=>$str120,
                'public_note'=>$text,'private_note'=>$text,'status'=>$str90,'order_id'=>$int,'order_ref'=>$str90,'expedition_id'=>$int,'expedition_ref'=>$str90,
                'paid_amount'=>$money,'remaining_amount'=>$money,'total_ht'=>$money,'total_tva'=>$money,'total_ttc'=>$money,'amount_ht'=>$money,'amount_ttc'=>$money,
                'created_by'=>$str190,'created_by_id'=>$int,'created_by_username'=>$str190,'created_at'=>$dt,'updated_at'=>$dt
            ],
            'line_collection'=>'invoice_lines','line_fk'=>'invoice_id','extra_exclude'=>['lines']
        ],
        'invoice_lines'=>[
            'columns'=>[
                'invoice_id'=>$int,'line_no'=>$int,'product_id'=>$int,'product_ref'=>$str90,'product_label'=>$str190,
                'description'=>$text,'tva'=>$qty,'pu_ht'=>$money,'qty'=>$qty,'unit'=>$str90,'reduction'=>$str90,
                'cost_price'=>$money,'total_ht'=>$money,'total_ttc'=>$money
            ]
        ],
        'invoice_payments'=>[
            'columns'=>[
                'invoice_id'=>$int,'date'=>$dt,'amount'=>$money,'mode'=>$str120,'reference'=>$str190,'note'=>$text,'created_at'=>$dt
            ]
        ],
        'expeditions'=>[
            'columns'=>[
                'ref'=>$str90,'order_id'=>$int,'order_ref'=>$str90,'tier_id'=>$int,'tier_name'=>$str190,'tier_ref'=>$str90,'city'=>$str120,'zip'=>$str90,
                'warehouse_id'=>$int,'warehouse_name'=>$str190,'date'=>$dt,'delivery_date'=>$dt,'shipping_method'=>$str120,'tracking'=>$str190,
                'status'=>$str90,'note_public'=>$text,'invoice_id'=>$int,'invoice_ref'=>$str90,
                'created_by'=>$str190,'created_by_id'=>$int,'created_by_username'=>$str190,'created_at'=>$dt,'updated_at'=>$dt
            ],
            'line_collection'=>'expedition_lines','line_fk'=>'expedition_id','extra_exclude'=>['lines']
        ],
        'expedition_lines'=>[
            'columns'=>[
                'expedition_id'=>$int,'line_no'=>$int,'product_id'=>$int,'product_ref'=>$str90,'product_label'=>$str190,'qty'=>$qty,'unit'=>$str90
            ]
        ],
        'receptions'=>[
            'columns'=>[
                'ref'=>$str90,'tier_id'=>$int,'tier_name'=>$str190,'warehouse_id'=>$int,'warehouse_name'=>$str190,'date'=>$dt,'order_date'=>$dt,
                'method'=>$str120,'supplier_doc'=>$str190,'status'=>$str90,'note_public'=>$text,
                'created_by'=>$str190,'created_by_id'=>$int,'created_by_username'=>$str190,'created_at'=>$dt,'updated_at'=>$dt
            ],
            'line_collection'=>'reception_lines','line_fk'=>'reception_id','extra_exclude'=>['lines']
        ],
        'reception_lines'=>[
            'columns'=>[
                'reception_id'=>$int,'line_no'=>$int,'product_id'=>$int,'product_ref'=>$str90,'product_label'=>$str190,'qty'=>$qty,'unit'=>$str90
            ]
        ],
        'settings'=>['columns'=>['company_name'=>$str190,'language'=>$str90,'currency'=>$str90,'timezone'=>$str120,'api_enabled'=>$bool,'api_key_hash'=>$str190,'created_at'=>$dt,'updated_at'=>$dt], 'extra_keep'=>['company','security','numbering','templates','api']],
        'stock_alerts'=>['columns'=>['product_id'=>$int,'product_ref'=>$str90,'product_label'=>$str190,'warehouse_id'=>$int,'qty'=>$qty,'threshold'=>$qty,'status'=>$str90,'last_notified_at'=>$dt,'created_at'=>$dt]],
        'signatures'=>['columns'=>[
            'ref'=>$str90,'token_hash'=>$str255,'public_url'=>$str255,'pdf_file'=>$str255,'pdf_name'=>$str190,'doc_type'=>$str120,
            'client_name'=>$str190,'client_email'=>$str190,'cc_email'=>$str190,'subject'=>$str255,'message'=>$text,'status'=>$str90,
            'created_by'=>$int,'created_by_name'=>$str190,'created_at'=>$dt,'expires_at'=>$dt,'sent_at'=>$dt,'opened_at'=>$dt,
            'signed_at'=>$dt,'signer_name'=>$str190,'signer_email'=>$str190,'signature_data'=>'LONGTEXT NULL','signed_ip_hash'=>$str255,
            'signed_user_agent'=>$str255,'signed_pdf_file'=>$str255,'email_sent'=>$bool,'email_error'=>$text
        ]],
        'sent_emails'=>['columns'=>['type'=>$str90,'object_id'=>$int,'to_email'=>$str190,'cc'=>$str190,'subject'=>$str255,'message'=>$text,'sent'=>$bool,'error'=>$text,'created_at'=>$dt], 'aliases'=>['to'=>'to_email']],
        'user_emails'=>['columns'=>['user_id'=>$int,'to_email'=>$str190,'cc'=>$str190,'subject'=>$str255,'message'=>$text,'sent'=>$bool,'error'=>$text,'created_at'=>$dt], 'aliases'=>['to'=>'to_email']],
        'quote_emails'=>['columns'=>['quote_id'=>$int,'to_email'=>$str190,'cc'=>$str190,'subject'=>$str255,'message'=>$text,'sent'=>$bool,'error'=>$text,'created_at'=>$dt], 'aliases'=>['to'=>'to_email']],
        'order_emails'=>['columns'=>['order_id'=>$int,'to_email'=>$str190,'cc'=>$str190,'subject'=>$str255,'message'=>$text,'sent'=>$bool,'error'=>$text,'created_at'=>$dt], 'aliases'=>['to'=>'to_email']],
        'invoice_emails'=>['columns'=>['invoice_id'=>$int,'to_email'=>$str190,'cc'=>$str190,'subject'=>$str255,'message'=>$text,'sent'=>$bool,'error'=>$text,'created_at'=>$dt], 'aliases'=>['to'=>'to_email']],
        'quote_documents'=>['columns'=>['quote_id'=>$int,'filename'=>$str255,'original_name'=>$str255,'mime_type'=>$str120,'size_bytes'=>'BIGINT NULL','url'=>$str255,'path'=>$str255,'created_at'=>$dt]],
        'order_documents'=>['columns'=>['order_id'=>$int,'filename'=>$str255,'original_name'=>$str255,'mime_type'=>$str120,'size_bytes'=>'BIGINT NULL','url'=>$str255,'path'=>$str255,'created_at'=>$dt]],
        'invoice_documents'=>['columns'=>['invoice_id'=>$int,'filename'=>$str255,'original_name'=>$str255,'mime_type'=>$str120,'size_bytes'=>'BIGINT NULL','url'=>$str255,'path'=>$str255,'created_at'=>$dt]],
        'expedition_documents'=>['columns'=>['expedition_id'=>$int,'filename'=>$str255,'original_name'=>$str255,'mime_type'=>$str120,'size_bytes'=>'BIGINT NULL','url'=>$str255,'path'=>$str255,'created_at'=>$dt]],
        'reception_documents'=>['columns'=>['reception_id'=>$int,'filename'=>$str255,'original_name'=>$str255,'mime_type'=>$str120,'size_bytes'=>'BIGINT NULL','url'=>$str255,'path'=>$str255,'created_at'=>$dt]],

        'bank_accounts'=>['columns'=>['ref'=>$str90,'name'=>$str190,'bank_name'=>$str190,'iban'=>$str190,'rib'=>$str190,'currency'=>$str90,'opening_balance'=>$money,'current_balance'=>$money,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'payment_modes'=>['columns'=>['ref'=>$str90,'name'=>$str190,'type'=>$str120,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'payments'=>['columns'=>['ref'=>$str90,'invoice_id'=>$int,'invoice_ref'=>$str90,'client_id'=>$int,'client_name'=>$str190,'bank_account_id'=>$int,'date'=>$dt,'amount'=>$money,'mode'=>$str120,'reference'=>$str190,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'supplier_payments'=>['columns'=>['ref'=>$str90,'supplier_invoice_id'=>$int,'supplier_invoice_ref'=>$str90,'supplier_id'=>$int,'supplier_name'=>$str190,'bank_account_id'=>$int,'date'=>$dt,'amount'=>$money,'mode'=>$str120,'reference'=>$str190,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'accounting_accounts'=>['columns'=>['ref'=>$str90,'code'=>$str90,'label'=>$str190,'type'=>$str120,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'accounting_journals'=>['columns'=>['ref'=>$str90,'code'=>$str90,'label'=>$str190,'type'=>$str120,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'accounting_entries'=>['columns'=>['ref'=>$str90,'date'=>$dt,'journal'=>$str90,'account'=>$str90,'label'=>$str190,'debit'=>$money,'credit'=>$money,'source_type'=>$str120,'source_id'=>$int,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'accounting_periods'=>['columns'=>['ref'=>$str90,'name'=>$str190,'start_date'=>$dt,'end_date'=>$dt,'status'=>$str90,'closed_by'=>$int,'closed_at'=>$dt,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'currencies'=>['columns'=>['ref'=>$str90,'code'=>$str90,'name'=>$str190,'rate'=>$qty,'symbol'=>$str90,'status'=>$str90,'created_at'=>$dt,'updated_at'=>$dt]],
        'custom_fields'=>['columns'=>['ref'=>$str90,'object_type'=>$str120,'field_key'=>$str120,'label'=>$str190,'field_type'=>$str120,'required'=>$bool,'status'=>$str90,'options'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'documents'=>['columns'=>['ref'=>$str90,'object_type'=>$str120,'object_id'=>$int,'object_ref'=>$str90,'title'=>$str190,'filename'=>$str255,'mime_type'=>$str120,'size_bytes'=>'BIGINT NULL','url'=>$str255,'path'=>$str255,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'projects'=>['columns'=>['ref'=>$str90,'title'=>$str190,'client_id'=>$int,'client_name'=>$str190,'start_date'=>$dt,'end_date'=>$dt,'budget'=>$money,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'project_tasks'=>['columns'=>['ref'=>$str90,'project_id'=>$int,'title'=>$str190,'assigned_to'=>$int,'start_date'=>$dt,'due_date'=>$dt,'estimated_hours'=>$qty,'spent_hours'=>$qty,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'agenda_events'=>['columns'=>['ref'=>$str90,'title'=>$str190,'object_type'=>$str120,'object_id'=>$int,'event_date'=>$dt,'event_time'=>$str90,'assigned_to'=>$int,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'pos_sales'=>['columns'=>['ref'=>$str90,'client_name'=>$str190,'date'=>$dt,'amount_ht'=>$money,'amount_ttc'=>$money,'payment_mode'=>$str120,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt], 'line_collection'=>'pos_sale_lines','line_fk'=>'sale_id','extra_exclude'=>['lines']],
        'pos_sale_lines'=>['columns'=>['sale_id'=>$int,'line_no'=>$int,'product_id'=>$int,'product_ref'=>$str90,'product_label'=>$str190,'qty'=>$qty,'pu_ht'=>$money,'total_ht'=>$money,'total_ttc'=>$money]],
        'manufacturing_orders'=>['columns'=>['ref'=>$str90,'product_id'=>$int,'product_ref'=>$str90,'product_label'=>$str190,'qty'=>$qty,'start_date'=>$dt,'end_date'=>$dt,'cost'=>$money,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt], 'line_collection'=>'manufacturing_lines','line_fk'=>'manufacturing_id','extra_exclude'=>['lines']],
        'manufacturing_lines'=>['columns'=>['manufacturing_id'=>$int,'line_no'=>$int,'component_id'=>$int,'component_ref'=>$str90,'component_label'=>$str190,'qty'=>$qty,'unit'=>$str90,'cost'=>$money]],
        'stock_lots'=>['columns'=>['ref'=>$str90,'product_id'=>$int,'product_ref'=>$str90,'product_label'=>$str190,'warehouse_id'=>$int,'lot_number'=>$str120,'expiry_date'=>$dt,'qty'=>$qty,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'stock_serials'=>['columns'=>['ref'=>$str90,'product_id'=>$int,'product_ref'=>$str90,'product_label'=>$str190,'warehouse_id'=>$int,'serial_number'=>$str190,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt]],
        'inventories'=>['columns'=>['ref'=>$str90,'warehouse_id'=>$int,'warehouse_name'=>$str190,'date'=>$dt,'status'=>$str90,'note'=>$text,'created_at'=>$dt,'updated_at'=>$dt], 'line_collection'=>'inventory_lines','line_fk'=>'inventory_id','extra_exclude'=>['lines']],
        'inventory_lines'=>['columns'=>['inventory_id'=>$int,'line_no'=>$int,'product_id'=>$int,'product_ref'=>$str90,'product_label'=>$str190,'system_qty'=>$qty,'counted_qty'=>$qty,'difference_qty'=>$qty,'unit'=>$str90]],
        'imports'=>['columns'=>['ref'=>$str90,'collection'=>$str120,'filename'=>$str255,'rows_imported'=>$int,'status'=>$str90,'note'=>$text,'created_at'=>$dt]],
        'exports'=>['columns'=>['ref'=>$str90,'collection'=>$str120,'filename'=>$str255,'rows_exported'=>$int,'status'=>$str90,'created_at'=>$dt]],
        'api_tokens'=>['columns'=>['ref'=>$str90,'name'=>$str190,'token_hash'=>$str255,'permissions'=>$text,'status'=>$str90,'last_used_at'=>$dt,'created_at'=>$dt,'updated_at'=>$dt]],

        'password_resets'=>['columns'=>['user_id'=>$int,'email'=>$str190,'token_hash'=>$str255,'expires_at'=>$dt,'used'=>$bool,'created_at'=>$dt]],
        'login_attempts'=>[
            'columns'=>[
                'bucket'=>'VARCHAR(90) NULL',
                'login_hash'=>'VARCHAR(90) NULL',
                'ip_hash'=>'VARCHAR(90) NULL',
                'count'=>'INT NOT NULL DEFAULT 0',
                'first'=>'INT NOT NULL DEFAULT 0',
                'locked_until'=>'INT NOT NULL DEFAULT 0',
                'updated_at_ts'=>'INT NOT NULL DEFAULT 0',
                'updated_at'=>$dt,
                'created_at'=>$dt
            ]
        ],
    ];
    return $schema;
}

function ge_schema_for($name){ $schema=ge_real_schema(); return $schema[$name] ?? null; }
function ge_sql_is_decimal($type){ return stripos($type,'DECIMAL')!==false; }
function ge_sql_is_int($type){ return preg_match('/\bINT\b/i',$type) && stripos($type,'TINYINT')===false; }
function ge_sql_is_bool($type){ return stripos($type,'TINYINT(1)')!==false; }
function ge_schema_is_complex($v){ return is_array($v) || is_object($v); }
function ge_schema_json($v){ return json_encode($v, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); }


function ge_schema_mysql_datetime($value){
    $v=trim((string)$value);
    if($v==='') return null;
    if(preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?)?/', $v)){
        return strlen($v)<=10 ? $v.' 00:00:00' : str_replace('T',' ',substr($v,0,19));
    }
    if(preg_match('/^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(\d{2}):(\d{2})(?::(\d{2}))?)?/', $v, $m)){
        return $m[3].'-'.$m[2].'-'.$m[1].' '.($m[4] ?? '00').':'.($m[5] ?? '00').':'.($m[6] ?? '00');
    }
    $ts=strtotime($v);
    return $ts ? date('Y-m-d H:i:s',$ts) : null;
}

function ge_schema_db_value($type, $value){
    // MySQL does not use the column DEFAULT when we explicitly insert NULL.
    // For NOT NULL business columns (booleans and money/qty decimals), always
    // send a safe typed value so first install/seed cannot crash on empty fields.
    if(ge_sql_is_bool($type)) return !empty($value) ? 1 : 0;
    if(ge_sql_is_decimal($type)){
        if($value === null || $value === '') return 0;
        return is_numeric($value) ? (float)$value : (function_exists('ge_parse_number') ? ge_parse_number($value) : (float)str_replace(',','.',(string)$value));
    }
    if($value === null) return null;
    if(ge_sql_is_int($type)) return ($value === '' ? null : (int)$value);
    if(ge_schema_is_complex($value)) return ge_schema_json($value);
    return (string)$value;
}

function ge_schema_db_row_to_app($name, array $dbRow){
    $schema=ge_schema_for($name);
    $payload=[];
    foreach(['extra_json','payload'] as $jsonCol){
        if(!empty($dbRow[$jsonCol])){
            $decoded=json_decode((string)$dbRow[$jsonCol], true);
            if(is_array($decoded)) $payload=array_merge($payload,$decoded);
        }
    }
    $row=$payload;
    $row['id']=(int)($dbRow['record_id'] ?? $dbRow['id'] ?? 0);
    if(isset($dbRow['tenant_id'])) $row['tenant_id']=(int)$dbRow['tenant_id'];
    if($schema){
        foreach(($schema['columns'] ?? []) as $field=>$type){
            $col=$field;
            $aliases=array_flip($schema['aliases'] ?? []);
            $appField=$aliases[$field] ?? $field;
            if(array_key_exists($col,$dbRow) && $dbRow[$col] !== null){
                if(ge_sql_is_bool($type)) $row[$appField]=(bool)$dbRow[$col];
                elseif(ge_sql_is_int($type)) $row[$appField]=(int)$dbRow[$col];
                elseif(ge_sql_is_decimal($type)) $row[$appField]=(float)$dbRow[$col];
                else $row[$appField]=$dbRow[$col];
            }
        }
        // Preserve common legacy aliases expected by screens.
        if(isset($row['to_email']) && !isset($row['to'])) $row['to']=$row['to_email'];
        if($name==='products'){
            if(!isset($row['stock']) && isset($row['physical_stock'])) $row['stock']=$row['physical_stock'];
            if(!isset($row['vat']) && isset($row['tax_rate'])) $row['vat']=$row['tax_rate'];
        }
    }else{
        foreach(['ref','label','status','amount','created_at','updated_at'] as $k){ if(isset($dbRow[$k]) && !isset($row[$k])) $row[$k]=$dbRow[$k]; }
    }
    return $row;
}

function ge_schema_extract_extra($name, array $row){
    $schema=ge_schema_for($name); if(!$schema) return $row;
    $mapped=['id'=>true,'record_id'=>true,'payload'=>true,'extra_json'=>true];
    foreach(($schema['columns'] ?? []) as $field=>$type){ $mapped[$field]=true; }
    foreach(($schema['aliases'] ?? []) as $app=>$db){ $mapped[$app]=true; $mapped[$db]=true; }
    foreach((array)($schema['extra_exclude'] ?? []) as $k){ $mapped[$k]=true; }
    $extra=[];
    foreach($row as $k=>$v){
        if(!isset($mapped[$k])) $extra[$k]=$v;
    }
    foreach((array)($schema['extra_keep'] ?? []) as $k){ if(array_key_exists($k,$row)) $extra[$k]=$row[$k]; }
    return $extra;
}

function ge_schema_missing_columns(PDO $pdo, $table){
    $stmt=$pdo->prepare('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $stmt->execute([$table]);
    return array_flip($stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
}

function ge_real_install_table(PDO $pdo, $collection, $table){
    $schema=ge_schema_for($collection); if(!$schema) return;
    $existing=ge_schema_missing_columns($pdo,$table);
    $columns=$schema['columns'] ?? [];
    if(!isset($existing['tenant_id'])){
        try{ $pdo->exec('ALTER TABLE '.ge_identifier($table).' ADD COLUMN `tenant_id` INT NOT NULL DEFAULT 1 FIRST'); }catch(Throwable $e){}
        $existing['tenant_id']=true;
    }
    if(!isset($existing['extra_json'])){
        try{ $pdo->exec('ALTER TABLE '.ge_identifier($table).' ADD COLUMN `extra_json` LONGTEXT NULL AFTER `payload`'); }catch(Throwable $e){}
        $existing['extra_json']=true;
    }
    foreach($columns as $col=>$type){
        if(isset($existing[$col])) continue;
        try{ $pdo->exec('ALTER TABLE '.ge_identifier($table).' ADD COLUMN '.ge_identifier($col).' '.$type); }catch(Throwable $e){}
    }
    ge_real_install_indexes($pdo,$collection,$table,$columns);
    if(function_exists('ge_ensure_tenant_column')) ge_ensure_tenant_column($pdo,$table,true);
    ge_real_migrate_payload_columns($pdo,$collection,$table);
}


function ge_real_migrate_payload_columns(PDO $pdo, $collection, $table){
    static $done=[];
    if(isset($done[$table])) return;
    $done[$table]=true;
    $schema=ge_schema_for($collection); if(!$schema) return;
    try{
        $stmt=$pdo->query("SELECT * FROM ".ge_identifier($table)." WHERE tenant_id=".(int)ge_current_tenant_id()." AND (extra_json IS NULL OR extra_json='') AND payload IS NOT NULL AND payload<>'' ORDER BY record_id ASC LIMIT 2000");
        $rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
        if(!$rows) return;
        $cols=array_keys($schema['columns'] ?? []);
        foreach($rows as $dbRow){
            $payload=json_decode((string)($dbRow['payload'] ?? ''), true);
            if(!is_array($payload)) continue;
            $payload['id']=(int)($payload['id'] ?? $dbRow['record_id'] ?? 0);
            $extra=ge_schema_extract_extra($collection,$payload);
            [$ref,$label,$status,$amount]=function_exists('data_index_fields') ? data_index_fields($payload) : [($payload['ref'] ?? null),($payload['label'] ?? $payload['name'] ?? null),($payload['status'] ?? null),null];
            $set=['extra_json=?','ref=?','label=?','status=?','amount=?'];
            $vals=[ge_schema_json($extra),$ref,$label,$status,$amount];
            foreach($cols as $c){
                if(!array_key_exists($c,$payload)) continue;
                $set[]=ge_identifier($c).'=?';
                $vals[]=($c==='created_at'||$c==='updated_at') ? ge_schema_mysql_datetime($payload[$c]) : ge_schema_db_value($schema['columns'][$c], $payload[$c]);
            }
            $vals[]=(int)$dbRow['record_id'];
            $vals[]=ge_current_tenant_id();
            $up=$pdo->prepare('UPDATE '.ge_identifier($table).' SET '.implode(',',$set).' WHERE record_id=? AND tenant_id=?');
            $up->execute($vals);
        }
    }catch(Throwable $e){ try{ if(function_exists('audit_log')) audit_log('schema_migration_error',$collection.': '.$e->getMessage()); }catch(Throwable $ignored){} }
}

function ge_real_index_name($table,$col){ return substr('idx_'.$table.'_'.$col,0,60); }
function ge_real_index_exists(PDO $pdo,$table,$index){
    $stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?');
    $stmt->execute([$table,$index]); return (int)$stmt->fetchColumn()>0;
}
function ge_real_install_indexes(PDO $pdo,$collection,$table,$columns){
    $wanted=[];
    foreach(['ref','label','name','username','email','bucket','login_hash','ip_hash','locked_until','client','client_id','tier_id','supplier_id','warehouse_id','product_id','status','type','date','order_date','invoice_date','due_date','created_at'] as $c){
        if(isset($columns[$c])) $wanted[]=$c;
    }
    foreach(array_unique($wanted) as $col){
        $type=$columns[$col] ?? '';
        if(stripos($type,'TEXT')!==false || stripos($type,'LONGTEXT')!==false) continue;
        $idx=ge_real_index_name($table,$col);
        try{ if(!ge_real_index_exists($pdo,$table,$idx)) $pdo->exec('CREATE INDEX '.ge_identifier($idx).' ON '.ge_identifier($table).' ('.ge_identifier($col).')'); }catch(Throwable $e){}
    }
    ge_install_fast_collection_indexes($pdo, (string)$collection, (string)$table);
}

function ge_install_fast_collection_indexes(PDO $pdo, string $collection, string $table): void {
    try{
        if($collection === 'users'){
            if(!ge_real_index_exists($pdo,$table,'idx_ge_users_tenant_email_status')){
                $pdo->exec('CREATE INDEX `idx_ge_users_tenant_email_status` ON '.ge_identifier($table).' (`tenant_id`,`email`,`status`)');
            }
            if(!ge_real_index_exists($pdo,$table,'idx_ge_users_tenant_username_status')){
                $pdo->exec('CREATE INDEX `idx_ge_users_tenant_username_status` ON '.ge_identifier($table).' (`tenant_id`,`username`,`status`)');
            }
        }
        if($collection === 'login_attempts'){
            if(!ge_real_index_exists($pdo,$table,'uniq_ge_login_attempts_bucket')){
                $pdo->exec('CREATE UNIQUE INDEX `uniq_ge_login_attempts_bucket` ON '.ge_identifier($table).' (`tenant_id`,`bucket`)');
            }
            if(!ge_real_index_exists($pdo,$table,'idx_ge_login_attempts_locked')){
                $pdo->exec('CREATE INDEX `idx_ge_login_attempts_locked` ON '.ge_identifier($table).' (`tenant_id`,`locked_until`,`updated_at_ts`)');
            }
        }
    }catch(Throwable $e){
        try{ if(function_exists('audit_log')) audit_log('fast_index_error', $collection.': '.$e->getMessage()); }catch(Throwable $ignored){}
    }
}

function ge_schema_insert_rows(PDO $pdo, $name, array $data){
    $schema=ge_schema_for($name);
    if(!$schema) return false;
    $table=ge_collection_table($name);
    if(!$pdo->inTransaction()) db_install_collection_table($pdo,$table);
    $columns=array_keys($schema['columns'] ?? []);
    $allCols=array_merge(['tenant_id','record_id','payload','extra_json','ref','label','status','amount'], $columns);
    $allCols=array_values(array_unique($allCols));
    $ph='('.implode(',',array_fill(0,count($allCols),'?')).')';
    $updates=[];
    foreach($allCols as $c){ if($c==='record_id' || $c==='tenant_id') continue; $updates[]=ge_identifier($c).'=VALUES('.ge_identifier($c).')'; }
    if(!in_array('updated_at',$allCols,true)) $updates[]='updated_at=CURRENT_TIMESTAMP';
    $sql='INSERT INTO '.ge_identifier($table).' ('.implode(',',array_map('ge_identifier',$allCols)).') VALUES '.$ph.' ON DUPLICATE KEY UPDATE '.implode(',',$updates);
    $stmt=$pdo->prepare($sql);
    $incoming=[]; $i=1;
    foreach($data as $row){
        if(!is_array($row)) continue;
        $row=ge_sanitize_collection_row($name,$row);
        $id=(int)($row['id'] ?? $row['record_id'] ?? $i); if($id<=0) $id=$i;
        $incoming[]=$id;
        [$ref,$label,$status,$amount]=data_index_fields($row);
        $extra=ge_schema_extract_extra($name,$row);
        // Keep payload only for compatibility/extras, not as the primary database model.
        $payload=ge_schema_json($extra ?: ['_relational'=>true]);
        $vals=[];
        foreach($allCols as $c){
            if($c==='tenant_id') $vals[]=ge_current_tenant_id();
            elseif($c==='record_id') $vals[]=$id;
            elseif($c==='payload') $vals[]=$payload;
            elseif($c==='extra_json') $vals[]=ge_schema_json($extra);
            elseif($c==='ref') $vals[]=$ref;
            elseif($c==='label') $vals[]=$label;
            elseif($c==='status') $vals[]=$status;
            elseif($c==='amount') $vals[]=$amount;
            elseif($c==='created_at' || $c==='updated_at'){
                $vals[]=ge_schema_mysql_datetime($row[$c] ?? null);
            }
            else{
                $appField=$c;
                foreach(($schema['aliases'] ?? []) as $from=>$to){ if($to===$c && array_key_exists($from,$row)){ $appField=$from; break; } }
                $vals[]=ge_schema_db_value($schema['columns'][$c], $row[$appField] ?? $row[$c] ?? null);
            }
        }
        $stmt->execute($vals);
        $i++;
    }
    if($incoming){
        $incoming=array_values(array_unique(array_map('intval',$incoming)));
        $ph=implode(',', array_fill(0,count($incoming),'?'));
        $del=$pdo->prepare('DELETE FROM '.ge_identifier($table).' WHERE tenant_id=? AND record_id NOT IN ('.$ph.')');
        $del->execute(array_merge([ge_current_tenant_id()], $incoming));
    }else{
        $stmtDel=$pdo->prepare('DELETE FROM '.ge_identifier($table).' WHERE tenant_id=?'); $stmtDel->execute([ge_current_tenant_id()]);
    }
    return true;
}


function ge_sync_document_lines_from_rows($collection, array $rows){
    $schema=ge_schema_for($collection);
    if(!$schema || empty($schema['line_collection']) || empty($schema['line_fk'])) return;
    $lineCollection=$schema['line_collection']; $fk=$schema['line_fk'];
    $docIds=[]; $lineSets=[];
    foreach($rows as $row){
        if(!is_array($row) || !array_key_exists('lines',$row) || !is_array($row['lines'])) continue;
        $docId=(int)($row['id'] ?? 0); if($docId<=0) continue;
        $docIds[]=$docId; $lineSets[$docId]=$row['lines'];
    }
    if(!$docIds) return;
    $docIds=array_values(array_unique($docIds));
    $existing=data_read($lineCollection, []);
    $new=[];
    foreach($existing as $line){ if(!in_array((int)($line[$fk] ?? 0), $docIds, true)) $new[]=$line; }
    $next=next_id($new); // next_id is available from helpers.php at runtime.
    foreach($lineSets as $docId=>$lines){
        $lineNo=1;
        foreach($lines as $line){
            if(!is_array($line)) continue;
            $line['id']=$next++;
            $line[$fk]=$docId;
            $line['line_no']=$lineNo++;
            $new[]=$line;
        }
    }
    data_write($lineCollection, $new, false);
}

function ge_hydrate_document_lines($name, array $rows){
    $schema=ge_schema_for($name);
    if(!$schema || empty($schema['line_collection']) || empty($schema['line_fk']) || !$rows) return $rows;
    $lineCollection=$schema['line_collection']; $fk=$schema['line_fk'];
    $lines=data_read($lineCollection, []);
    $by=[]; foreach($lines as $line){ $by[(int)($line[$fk] ?? 0)][]=$line; }
    foreach($rows as &$row){ $id=(int)($row['id'] ?? 0); $row['lines']=$by[$id] ?? ($row['lines'] ?? []); }
    unset($row);
    return $rows;
}
