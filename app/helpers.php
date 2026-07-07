<?php
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($n){ return number_format((float)$n, 3, ',', ' '); }
function redirect_to($url){ header('Location: '.$url); exit; }
function ge_send_file_download($path, $filename='document.pdf', $mime='application/pdf'){
    $path=(string)$path;
    $real=@realpath($path);
    $uploadsRoot=@realpath(__DIR__.'/../uploads');
    if(!$real || !$uploadsRoot || !is_file($real) || !str_starts_with($real, $uploadsRoot.DIRECTORY_SEPARATOR)){
        http_response_code(404);
        echo 'Fichier introuvable';
        exit;
    }
    if(strtolower(pathinfo($real, PATHINFO_EXTENSION)) !== 'pdf'){
        http_response_code(404);
        echo 'Type de fichier non autorisé';
        exit;
    }
    $filename=basename((string)($filename ?: basename($real)));
    if(headers_sent()) exit;
    while(ob_get_level()>0){ @ob_end_clean(); }
    header('Content-Type: application/pdf');
    header('Content-Length: '.filesize($real));
    header('Content-Disposition: attachment; filename="'.str_replace('"','', $filename).'"');
    header('X-Content-Type-Options: nosniff');
    readfile($real);
    exit;
}
function storage_path($file){ return __DIR__.'/../storage/'.$file; } // legacy path, no longer used for data

function ge_load_env_file(){
    static $loaded=false; if($loaded) return; $loaded=true;
    $file=__DIR__.'/../.env'; if(!is_file($file)) return;
    foreach(file($file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line){
        $line=trim((string)$line);
        if($line==='' || str_starts_with($line,'#') || !str_contains($line,'=')) continue;
        [$k,$v]=explode('=', $line, 2);
        $k=trim((string)$k); $v=trim((string)$v);
        if($k==='') continue;
        $v=trim($v, "\"'");
        if(getenv($k)===false){ putenv($k.'='.$v); $_ENV[$k]=$v; $_SERVER[$k]=$v; }
    }
}
function app_config(){ static $cfg=null; if($cfg===null){ ge_load_env_file(); $cfg = require __DIR__.'/../config.php'; } return $cfg; }
require_once __DIR__.'/real_schema.php';
require_once __DIR__.'/enterprise.php';
function app_debug(){
    $v = getenv('GE_DEBUG');
    return in_array(strtolower((string)$v), ['1','true','yes','on'], true);
}

function ge_schema_version(){ return '2026-06-26-fast-tenancy-onboarding-v5-square-pdf'; }
function ge_schema_meta_ensure(PDO $pdo): void {
    try{
        $pdo->exec("CREATE TABLE IF NOT EXISTS ge_schema_meta (
            meta_key VARCHAR(90) NOT NULL PRIMARY KEY,
            meta_value VARCHAR(255) NULL,
            updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }catch(Throwable $e){}
}
function ge_schema_meta_get(PDO $pdo, string $key, $default=null){
    try{
        ge_schema_meta_ensure($pdo);
        $stmt=$pdo->prepare('SELECT meta_value FROM ge_schema_meta WHERE meta_key=? LIMIT 1');
        $stmt->execute([$key]);
        $v=$stmt->fetchColumn();
        return $v === false ? $default : $v;
    }catch(Throwable $e){ return $default; }
}
function ge_schema_meta_set(PDO $pdo, string $key, string $value): void {
    try{
        ge_schema_meta_ensure($pdo);
        $stmt=$pdo->prepare('INSERT INTO ge_schema_meta(meta_key,meta_value) VALUES(?,?) ON DUPLICATE KEY UPDATE meta_value=VALUES(meta_value), updated_at=CURRENT_TIMESTAMP');
        $stmt->execute([$key,$value]);
    }catch(Throwable $e){}
}
function ge_schema_is_current(PDO $pdo): bool {
    if(filter_var(getenv('GE_FORCE_SCHEMA_CHECK') ?: 'false', FILTER_VALIDATE_BOOLEAN)) return false;
    return ge_schema_meta_get($pdo, 'schema_version', '') === ge_schema_version();
}

/* -------------------------------------------------------------------------
   Multi-tenancy + application Row-Level Security (RLS)
   ------------------------------------------------------------------------- */
function ge_tenancy_config(){ return app_config()['tenancy'] ?? []; }
function ge_tenancy_enabled(){ return filter_var(ge_tenancy_config()['enabled'] ?? true, FILTER_VALIDATE_BOOLEAN); }
function ge_default_tenant_id(){ $id=(int)(ge_tenancy_config()['default_tenant_id'] ?? 1); return $id>0 ? $id : 1; }
function ge_default_tenant_slug(){ $s=strtolower(trim((string)(ge_tenancy_config()['default_tenant_slug'] ?? 'global-energie'))); return $s!=='' ? preg_replace('/[^a-z0-9_-]+/','-', $s) : 'global-energie'; }
function ge_default_tenant_name(){ $n=trim((string)(ge_tenancy_config()['default_tenant_name'] ?? 'Global Energie')); return $n!=='' ? $n : 'Global Energie'; }
function ge_normalize_tenant_slug($slug){ $slug=strtolower(trim((string)$slug)); $slug=preg_replace('/[^a-z0-9_-]+/','-', $slug); $slug=trim($slug,'-_'); return $slug!=='' ? $slug : ge_default_tenant_slug(); }
function ge_current_tenant_id(){
    if(!ge_tenancy_enabled()) return ge_default_tenant_id();
    $u = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? null);
    if(is_array($u) && (int)($u['tenant_id'] ?? 0) > 0) return (int)$u['tenant_id'];
    if((int)($_SESSION['tenant_id'] ?? 0) > 0) return (int)$_SESSION['tenant_id'];
    if((int)($_SESSION['login_tenant_id'] ?? 0) > 0) return (int)$_SESSION['login_tenant_id'];
    return ge_default_tenant_id();
}
function ge_current_tenant_slug(){
    $u = function_exists('current_user') ? current_user() : ($_SESSION['user'] ?? null);
    if(is_array($u) && !empty($u['tenant_slug'])) return ge_normalize_tenant_slug($u['tenant_slug']);
    if(!empty($_SESSION['tenant_slug'])) return ge_normalize_tenant_slug($_SESSION['tenant_slug']);
    if(!empty($_SESSION['login_tenant_slug'])) return ge_normalize_tenant_slug($_SESSION['login_tenant_slug']);
    return ge_default_tenant_slug();
}
function ge_tenant_sql($alias=''){
    $col = ($alias!=='' ? ge_identifier($alias).'.' : '').'`tenant_id`';
    return $col.' = ?';
}
function ge_tenant_params(array $extra=[]){ return array_merge([ge_current_tenant_id()], $extra); }
function ge_tenant_where($alias='', $prefix='WHERE'){ return ' '.$prefix.' '.ge_tenant_sql($alias).' '; }
function ge_tenant_and($alias=''){ return ' AND '.ge_tenant_sql($alias).' '; }
function ge_tenant_apply_row(array $row){ if(ge_tenancy_enabled() && (int)($row['tenant_id'] ?? 0)<=0) $row['tenant_id']=ge_current_tenant_id(); return $row; }
function ge_set_db_tenant_context(PDO $pdo){
    try{ $stmt=$pdo->prepare('SET @ge_current_tenant_id := ?'); $stmt->execute([ge_current_tenant_id()]); }catch(Throwable $e){}
}
function ge_tenant_table_exists(PDO $pdo, string $table): bool {
    try{ $stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?'); $stmt->execute([$table]); return (int)$stmt->fetchColumn()>0; }catch(Throwable $e){ return false; }
}
function ge_tenant_index_exists(PDO $pdo, string $table, string $index): bool {
    try{ $stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?'); $stmt->execute([$table,$index]); return (int)$stmt->fetchColumn()>0; }catch(Throwable $e){ return false; }
}
function ge_tenant_unique_indexes_on_columns(PDO $pdo, string $table, array $columns): array {
    try{
        $stmt=$pdo->prepare("SELECT INDEX_NAME, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ',') AS cols, MIN(NON_UNIQUE) AS non_unique FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? GROUP BY INDEX_NAME HAVING non_unique=0");
        $stmt->execute([$table]);
        $wanted=implode(',', $columns); $out=[];
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $r){
            $idx=(string)($r['INDEX_NAME'] ?? '');
            if($idx!=='' && $idx!=='PRIMARY' && (string)($r['cols'] ?? '')===$wanted) $out[]=$idx;
        }
        return $out;
    }catch(Throwable $e){ return []; }
}
function ge_tenant_drop_index(PDO $pdo, string $table, string $index): void {
    try{ if(ge_tenant_index_exists($pdo,$table,$index)) $pdo->exec('ALTER TABLE '.ge_identifier($table).' DROP INDEX '.ge_identifier($index)); }catch(Throwable $e){}
}
function ge_tenancy_fix_unique_indexes(PDO $pdo): void {
    if(!ge_tenancy_enabled()) return;
    $rules=[
        ['ge_purchase_orders','uniq_po_tenant_ref',['tenant_id','ref'],[['ref']]],
        ['ge_supplier_invoices','uniq_si_tenant_ref',['tenant_id','ref'],[['ref']]],
        ['ge_credit_notes','uniq_cn_tenant_ref',['tenant_id','ref'],[['ref']]],
        ['ge_approval_rules','uniq_rule_tenant_type',['tenant_id','object_type'],[['object_type']]],
        ['ge_bank_movements','uniq_bank_tenant_source',['tenant_id','source_type','source_id'],[['source_type','source_id']]],
    ];
    foreach($rules as [$table,$wantedIndex,$wantedColumns,$oldColumnSets]){
        if(!ge_tenant_table_exists($pdo,$table)) continue;
        foreach($oldColumnSets as $cols){ foreach(ge_tenant_unique_indexes_on_columns($pdo,$table,$cols) as $oldIdx){ ge_tenant_drop_index($pdo,$table,$oldIdx); } }
        try{ if(!ge_tenant_index_exists($pdo,$table,$wantedIndex)) $pdo->exec('CREATE UNIQUE INDEX '.ge_identifier($wantedIndex).' ON '.ge_identifier($table).' ('.implode(',', array_map('ge_identifier',$wantedColumns)).')'); }catch(Throwable $e){}
    }
}
function ge_tenant_trigger_exists(PDO $pdo, string $trigger): bool {
    try{ $stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=?'); $stmt->execute([$trigger]); return (int)$stmt->fetchColumn()>0; }catch(Throwable $e){ return false; }
}
function ge_tenant_primary_columns(PDO $pdo, string $table): array {
    try{ $stmt=$pdo->prepare("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME='PRIMARY' ORDER BY ORDINAL_POSITION"); $stmt->execute([$table]); return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []; }catch(Throwable $e){ return []; }
}
function ge_ensure_tenant_column(PDO $pdo, string $table, bool $compositeRecordPk=false): void {
    if(!ge_tenancy_enabled() || !ge_tenant_table_exists($pdo,$table)) return;
    try{ if(!ge_table_column_exists($pdo,$table,'tenant_id')) $pdo->exec('ALTER TABLE '.ge_identifier($table).' ADD COLUMN `tenant_id` INT NULL DEFAULT NULL AFTER `id`'); }catch(Throwable $e){
        try{ if(!ge_table_column_exists($pdo,$table,'tenant_id')) $pdo->exec('ALTER TABLE '.ge_identifier($table).' ADD COLUMN `tenant_id` INT NULL DEFAULT NULL FIRST'); }catch(Throwable $ignored){}
    }
    try{ $pdo->exec('UPDATE '.ge_identifier($table).' SET tenant_id='.ge_default_tenant_id().' WHERE tenant_id IS NULL OR tenant_id<=0'); }catch(Throwable $e){}
    try{ $pdo->exec('ALTER TABLE '.ge_identifier($table).' MODIFY COLUMN `tenant_id` INT NOT NULL DEFAULT 1'); }catch(Throwable $e){}
    try{ if(!ge_tenant_index_exists($pdo,$table,'idx_'.$table.'_tenant')) $pdo->exec('CREATE INDEX '.ge_identifier('idx_'.$table.'_tenant').' ON '.ge_identifier($table).' (`tenant_id`)'); }catch(Throwable $e){}
    if($compositeRecordPk && ge_table_column_exists($pdo,$table,'record_id')){
        $pk=ge_tenant_primary_columns($pdo,$table);
        if($pk===['record_id']){
            try{ $pdo->exec('ALTER TABLE '.ge_identifier($table).' DROP PRIMARY KEY, ADD PRIMARY KEY(`tenant_id`,`record_id`)'); }catch(Throwable $e){}
        }
    }
    $trigger='trg_'.$table.'_tenant_bi';
    if(!ge_tenant_trigger_exists($pdo,$trigger)){
        $defaultTenant = ge_default_tenant_id();
        try{
            $pdo->exec('CREATE TRIGGER '.ge_identifier($trigger).' BEFORE INSERT ON '.ge_identifier($table).' FOR EACH ROW BEGIN IF NEW.tenant_id IS NULL OR NEW.tenant_id <= 0 OR (NEW.tenant_id = '.$defaultTenant.' AND COALESCE(@ge_current_tenant_id, '.$defaultTenant.') <> '.$defaultTenant.') THEN SET NEW.tenant_id = COALESCE(@ge_current_tenant_id, '.$defaultTenant.'); END IF; END');
        }catch(Throwable $e){}
    }
}
function ge_install_tenancy_tables(PDO $pdo): void {
    if(!ge_tenancy_enabled()) return;
    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_tenants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        slug VARCHAR(90) NOT NULL UNIQUE,
        name VARCHAR(190) NOT NULL,
        company_size VARCHAR(20) NULL,
        business_email VARCHAR(190) NULL,
        phone VARCHAR(60) NULL,
        country VARCHAR(120) NULL,
        city VARCHAR(120) NULL,
        zip VARCHAR(40) NULL,
        status VARCHAR(40) NOT NULL DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_tenant_status(status),
        KEY idx_tenant_email(business_email),
        KEY idx_tenant_city(city)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    ge_ensure_tenant_profile_columns($pdo);
    $stmt=$pdo->prepare('INSERT INTO ge_tenants(id,slug,name,status) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE slug=VALUES(slug), name=VALUES(name), status=VALUES(status)');
    $stmt->execute([ge_default_tenant_id(), ge_default_tenant_slug(), ge_default_tenant_name(), 'active']);
}
function ge_ensure_tenant_profile_columns(PDO $pdo): void {
    if(!ge_tenancy_enabled() || !ge_tenant_table_exists($pdo,'ge_tenants')) return;
    $columns=[
        'company_size'=>"VARCHAR(20) NULL AFTER `name`",
        'business_email'=>"VARCHAR(190) NULL AFTER `company_size`",
        'phone'=>"VARCHAR(60) NULL AFTER `business_email`",
        'country'=>"VARCHAR(120) NULL AFTER `phone`",
        'city'=>"VARCHAR(120) NULL AFTER `country`",
        'zip'=>"VARCHAR(40) NULL AFTER `city`",
    ];
    foreach($columns as $column=>$definition){
        try{ if(function_exists('ge_table_column_exists') && !ge_table_column_exists($pdo,'ge_tenants',$column)) $pdo->exec('ALTER TABLE `ge_tenants` ADD COLUMN `'.$column.'` '.$definition); }catch(Throwable $e){}
    }
    try{ if(!ge_tenant_index_exists($pdo,'ge_tenants','idx_tenant_email')) $pdo->exec('CREATE INDEX idx_tenant_email ON ge_tenants(business_email)'); }catch(Throwable $e){}
    try{ if(!ge_tenant_index_exists($pdo,'ge_tenants','idx_tenant_city')) $pdo->exec('CREATE INDEX idx_tenant_city ON ge_tenants(city)'); }catch(Throwable $e){}
}
function ge_tenant_slug_exists(PDO $pdo, string $slug): bool {
    ge_install_tenancy_tables($pdo);
    $stmt=$pdo->prepare('SELECT COUNT(*) FROM ge_tenants WHERE slug=?');
    $stmt->execute([ge_normalize_tenant_slug($slug)]);
    return (int)$stmt->fetchColumn()>0;
}
function ge_unique_tenant_slug(PDO $pdo, string $companyName): string {
    $base = ge_normalize_tenant_slug($companyName);
    if($base === ge_default_tenant_slug() && strtolower(trim($companyName)) !== strtolower(ge_default_tenant_name())){
        $base = 'company';
    }
    $base = substr(trim($base, '-_'), 0, 70) ?: 'company';
    $slug = $base; $i=2;
    while(ge_tenant_slug_exists($pdo, $slug)){
        $suffix='-'.$i++;
        $slug = substr($base, 0, 90-strlen($suffix)).$suffix;
    }
    return $slug;
}
function ge_username_from_email(string $email): string {
    $local = strtolower(trim((string)strtok($email, '@')));
    $local = preg_replace('/[^a-z0-9_.-]+/', '', $local);
    $local = trim($local, '._-');
    return $local !== '' ? $local : 'admin';
}
function ge_register_company_account(array $input): array {
    $companyName=trim((string)($input['company_name'] ?? ''));
    $companySize=trim((string)($input['company_size'] ?? ''));
    $businessEmail=strtolower(trim((string)($input['business_email'] ?? '')));
    $password=(string)($input['password'] ?? '');
    $phone=trim((string)($input['phone'] ?? ''));
    $country=trim((string)($input['country'] ?? ''));
    $city=trim((string)($input['city'] ?? ''));
    $zip=trim((string)($input['zip'] ?? ''));

    if($companyName==='') return ['ok'=>false,'error'=>'Company name is required.'];
    if(!in_array($companySize, ['small','medium','big'], true)) return ['ok'=>false,'error'=>'Choose company size: small, medium or big.'];
    if(!filter_var($businessEmail, FILTER_VALIDATE_EMAIL)) return ['ok'=>false,'error'=>'Business email is invalid.'];
    if(strlen($password) < 8) return ['ok'=>false,'error'=>'Password must contain at least 8 characters.'];
    if($phone==='') return ['ok'=>false,'error'=>'Phone number is required.'];
    if($country==='') return ['ok'=>false,'error'=>'Country is required.'];
    if($city==='') return ['ok'=>false,'error'=>'City is required.'];
    if($zip==='') return ['ok'=>false,'error'=>'ZIP code is required.'];

    $pdo=db();
    ge_install_tenancy_tables($pdo);
    $slug=ge_unique_tenant_slug($pdo, $companyName);
    $stmt=$pdo->prepare('INSERT INTO ge_tenants(slug,name,company_size,business_email,phone,country,city,zip,status) VALUES(?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$slug,$companyName,$companySize,$businessEmail,$phone,$country,$city,$zip,'active']);
    $tenantId=(int)$pdo->lastInsertId();
    if($tenantId<=0){
        $tenant=ge_tenant_by_slug($pdo,$slug);
        $tenantId=(int)($tenant['id'] ?? 0);
    }
    if($tenantId<=0) return ['ok'=>false,'error'=>'Unable to create company tenant.'];

    if(session_status() === PHP_SESSION_ACTIVE){
        $_SESSION['tenant_id']=$tenantId;
        $_SESSION['tenant_slug']=$slug;
        $_SESSION['login_tenant_id']=$tenantId;
        $_SESSION['login_tenant_slug']=$slug;
    }
    ge_set_db_tenant_context($pdo);

    $users=data_read('users', []);
    $id=next_id($users);
    $username=ge_username_from_email($businessEmail);
    $existingNames=array_map(fn($u)=>strtolower((string)($u['username'] ?? '')), $users);
    $baseUsername=$username; $i=2;
    while(in_array(strtolower($username), $existingNames, true)){ $username=$baseUsername.$i++; }

    $user=[
        'id'=>$id,
        'tenant_id'=>$tenantId,
        'tenant_slug'=>$slug,
        'ref'=>'USR'.date('ym').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT),
        'name'=>'Admin',
        'firstname'=>$companyName,
        'username'=>$username,
        'email'=>$businessEmail,
        'phone'=>$phone,
        'role'=>'Administrateur',
        'status'=>'Actif',
        'employee'=>true,
        'external_user'=>'Interne',
        'company_name'=>$companyName,
        'company_size'=>$companySize,
        'country'=>$country,
        'city'=>$city,
        'zip'=>$zip,
        'force_password_change'=>false,
        'twofa_enabled'=>false,
        'twofa_secret'=>'',
        'permissions'=>[],
        'password'=>password_hash($password, PASSWORD_DEFAULT),
        'created_at'=>date('Y-m-d H:i:s')
    ];
    $users[]=$user;
    data_write('users', $users, false);

    if(!data_read('warehouses', [])){
        data_write('warehouses', [[
            'id'=>1,
            'name'=>'Entrepôt principal',
            'city'=>$city,
            'country'=>$country,
            'status'=>'Ouvert',
            'created_at'=>date('Y-m-d H:i:s')
        ]], false);
    }

    save_app_settings([
        'company_name'=>$companyName,
        'company_email'=>$businessEmail,
        'company_phone'=>$phone,
        'company_country'=>$country,
        'company_city'=>$city,
        'onboarding_required'=>true,
        'onboarding_complete'=>false,
        'onboarding_step'=>'company',
        'first_product_prompt_done'=>false,
        'created_at'=>date('Y-m-d H:i:s'),
        'updated_at'=>date('Y-m-d H:i:s')
    ]);

    if(session_status() === PHP_SESSION_ACTIVE){
        if(session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
        $_SESSION['user']=$user;
        $_SESSION['tenant_id']=$tenantId;
        $_SESSION['tenant_slug']=$slug;
        $_SESSION['ge_show_stock_alert_once']=1;
        $_SESSION['ge_onboarding_force']=1;
    }
    try{ audit_log('tenant_registered','New tenant registered: '.$companyName.' / '.$slug); }catch(Throwable $e){}
    return ['ok'=>true,'tenant_id'=>$tenantId,'tenant_slug'=>$slug,'user'=>$user];
}
function ge_tenant_by_slug(PDO $pdo, string $slug){
    ge_install_tenancy_tables($pdo);
    $slug=ge_normalize_tenant_slug($slug);
    $stmt=$pdo->prepare("SELECT * FROM ge_tenants WHERE slug=? AND status<>'disabled' LIMIT 1");
    $stmt->execute([$slug]);
    $tenant=$stmt->fetch(PDO::FETCH_ASSOC);
    if($tenant) return $tenant;
    $stmt=$pdo->prepare('SELECT * FROM ge_tenants WHERE id=? LIMIT 1');
    $stmt->execute([ge_default_tenant_id()]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['id'=>ge_default_tenant_id(),'slug'=>ge_default_tenant_slug(),'name'=>ge_default_tenant_name()];
}
function ge_prepare_login_tenant($slug=null){
    if(session_status() !== PHP_SESSION_ACTIVE) return ge_default_tenant_id();
    $slug = $slug ?? ($_POST['tenant_code'] ?? $_GET['tenant'] ?? $_COOKIE['ge_tenant'] ?? ge_default_tenant_slug());
    $tenant = ge_tenant_by_slug(db(), (string)$slug);
    $_SESSION['login_tenant_id']=(int)($tenant['id'] ?? ge_default_tenant_id());
    $_SESSION['login_tenant_slug']=(string)($tenant['slug'] ?? ge_default_tenant_slug());
    return (int)$_SESSION['login_tenant_id'];
}
function ge_fetch_tenant_rows(PDO $pdo, string $table, string $order='id DESC', int $limit=5000): array {
    $table=preg_replace('/[^a-zA-Z0-9_]+/','',$table); $order=preg_replace('/[^a-zA-Z0-9_, .`-]+/','',$order);
    $limit=max(1,min(20000,$limit));
    try{ $stmt=$pdo->prepare('SELECT * FROM '.ge_identifier($table).' WHERE tenant_id=? ORDER BY '.$order.' LIMIT '.$limit); $stmt->execute([ge_current_tenant_id()]); return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []; }catch(Throwable $e){ return []; }
}
function ge_count_tenant_rows(PDO $pdo, string $table, string $where='', array $params=[]): int {
    $table=preg_replace('/[^a-zA-Z0-9_]+/','',$table);
    $sql='SELECT COUNT(*) FROM '.ge_identifier($table).' WHERE tenant_id=?';
    $all=[ge_current_tenant_id()];
    if(trim($where)!==''){ $sql.=' AND '.$where; $all=array_merge($all,$params); }
    try{ $stmt=$pdo->prepare($sql); $stmt->execute($all); return (int)$stmt->fetchColumn(); }catch(Throwable $e){ return 0; }
}
function ge_fetch_tenant_by_id(PDO $pdo, string $table, int $id): array {
    $table=preg_replace('/[^a-zA-Z0-9_]+/','',$table); if($id<=0) return [];
    try{ $stmt=$pdo->prepare('SELECT * FROM '.ge_identifier($table).' WHERE id=? AND tenant_id=? LIMIT 1'); $stmt->execute([$id,ge_current_tenant_id()]); return $stmt->fetch(PDO::FETCH_ASSOC) ?: []; }catch(Throwable $e){ return []; }
}
function ge_tenant_execute(PDO $pdo, string $sql, array $params=[]): bool {
    try{ $stmt=$pdo->prepare($sql); return $stmt->execute($params); }catch(Throwable $e){ return false; }
}

function ge_tenancy_install_all_known_tables(PDO $pdo): void {
    if(!ge_tenancy_enabled()) return;
    $tables=['procrm_audit_logs','ge_structured_products','ge_structured_tiers','ge_structured_sales','ge_structured_stock_movements','ge_purchase_orders','ge_supplier_invoices','ge_credit_notes','ge_approval_requests','ge_api_logs','ge_backups','ge_purchase_order_lines','ge_supplier_invoice_lines','ge_approval_rules','ge_bank_movements'];
    foreach(ge_known_collections() as $collection){ $tables[]=ge_collection_table($collection); }
    foreach(array_unique($tables) as $table){ ge_ensure_tenant_column($pdo,$table, str_starts_with($table,'ge_') && !in_array($table,['ge_tenants','ge_purchase_orders','ge_supplier_invoices','ge_credit_notes','ge_approval_requests','ge_api_logs','ge_backups','ge_purchase_order_lines','ge_supplier_invoice_lines','ge_approval_rules','ge_bank_movements','ge_structured_products','ge_structured_tiers','ge_structured_sales','ge_structured_stock_movements'], true)); }
    ge_tenancy_fix_unique_indexes($pdo);
}

function ge_storage_dir($sub=''){
    $root = __DIR__.'/../storage';
    if(!is_dir($root)) @mkdir($root, 0775, true);
    $path = $sub ? $root.'/'.trim((string)$sub, '/\\') : $root;
    if(!is_dir($path)) @mkdir($path, 0775, true);
    return $path;
}

function ge_is_https(){
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443)
        || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
}
function ge_send_security_headers(){
    if(headers_sent()) return;
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:; img-src 'self' data: blob: https://res.cloudinary.com; frame-src 'self'; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
    if((app_config()['security']['force_https'] ?? false) && !ge_is_https() && !empty($_SERVER['HTTP_HOST'])){
        $target = 'https://'.$_SERVER['HTTP_HOST'].($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: '.$target, true, 301);
        exit;
    }
    if(ge_is_https()){
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function db(){
    static $pdo=null;
    if($pdo instanceof PDO){ ge_set_db_tenant_context($pdo); return $pdo; }
    $cfg = app_config()['db'] ?? [];
    $host = $cfg['host'] ?? '127.0.0.1';
    $port = (int)($cfg['port'] ?? 3306);
    $name = $cfg['name'] ?? 'spix';
    $charset = $cfg['charset'] ?? 'utf8mb4';
    $user = $cfg['user'] ?? 'root';
    $pass = $cfg['pass'] ?? '';
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    try{
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        db_install_core($pdo);
        ge_set_db_tenant_context($pdo);
        return $pdo;
    }catch(PDOException $e){
        http_response_code(500);
        if(defined('GE_API_MODE') && GE_API_MODE){
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success'=>false,
                'connected'=>false,
                'error'=>'database_error',
                'message'=>'Database connection failed. Import install.sql then verify config.php database settings.',
                'details'=>app_debug() ? $e->getMessage() : null,
                'timestamp'=>date('c')
            ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        }else{
            echo '<h2>Erreur base de données</h2>';
            echo '<p>Importe le fichier <b>install.sql</b> dans phpMyAdmin puis vérifie <b>config.php</b>.</p>';
            if(app_debug()){ echo '<pre style="white-space:pre-wrap">'.e($e->getMessage()).'</pre>'; }
        }
        exit;
    }
}

function ge_known_collections(){
    return [
        'users','warehouses','warehouse_movements','settings','products','tiers','clients','suppliers',
        'quotes','quote_lines','quote_documents','quote_emails','orders','order_lines','order_documents','order_emails',
        'invoices','invoice_lines','invoice_documents','invoice_payments','invoice_emails',
        'expeditions','expedition_lines','expedition_documents','receptions','reception_lines','reception_documents',
        'sent_emails','signatures','stock_alerts','user_emails','password_resets','login_attempts',
        'bank_accounts','payment_modes','payments','supplier_payments','accounting_accounts','accounting_journals','accounting_entries','accounting_periods','currencies',
        'custom_fields','documents','projects','project_tasks','agenda_events','pos_sales','pos_sale_lines','manufacturing_orders','manufacturing_lines',
        'stock_lots','stock_serials','inventories','inventory_lines','imports','exports','api_tokens'
    ];
}
function ge_collection_table($name){
    $safe = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', (string)$name));
    $safe = trim($safe, '_');
    if($safe==='') $safe='records';
    return 'ge_'.$safe;
}
function ge_identifier($identifier){
    $identifier = preg_replace('/[^a-zA-Z0-9_]+/', '', (string)$identifier);
    if($identifier==='') throw new InvalidArgumentException('Invalid SQL identifier');
    return '`'.$identifier.'`';
}
function db_install_collection_table(PDO $pdo, $table){
    static $installedThisRequest = [];
    $table = preg_replace('/[^a-zA-Z0-9_]+/', '', (string)$table);
    if($table==='') return;
    if(isset($installedThisRequest[$table])) return;
    $q = ge_identifier($table);
    $pdo->exec("CREATE TABLE IF NOT EXISTS {$q} (
        tenant_id INT NOT NULL DEFAULT 1,
        record_id INT NOT NULL,
        payload LONGTEXT NOT NULL,
        extra_json LONGTEXT NULL,
        ref VARCHAR(90) NULL,
        label VARCHAR(190) NULL,
        status VARCHAR(90) NULL,
        amount DECIMAL(15,3) NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(tenant_id, record_id),
        KEY idx_tenant(tenant_id),
        KEY idx_ref(ref),
        KEY idx_label(label),
        KEY idx_status(status),
        KEY idx_updated(updated_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    ge_ensure_tenant_column($pdo, $table, true);
    $collection = preg_replace('/^ge_/', '', $table);
    if(function_exists('ge_real_install_table')) ge_real_install_table($pdo, $collection, $table);
    $installedThisRequest[$table] = true;
}
function db_install_enterprise_tables(PDO $pdo){
    // Structured shadow tables: fast reports/search without breaking the existing PHP screens.
    // The app still keeps full payloads in ge_* tables, and these tables hold the most important indexed fields.
    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_structured_products (
        tenant_id INT NOT NULL DEFAULT 1,
        id INT NOT NULL,
        PRIMARY KEY(tenant_id, id),
        ref VARCHAR(90) NULL,
        label VARCHAR(190) NOT NULL,
        type VARCHAR(80) NULL,
        brand VARCHAR(120) NULL,
        sale_price DECIMAL(15,3) NOT NULL DEFAULT 0,
        buy_price DECIMAL(15,3) NOT NULL DEFAULT 0,
        physical_stock DECIMAL(15,3) NOT NULL DEFAULT 0,
        virtual_stock DECIMAL(15,3) NOT NULL DEFAULT 0,
        alert_stock DECIMAL(15,3) NOT NULL DEFAULT 0,
        warehouse_id INT NULL,
        status_sale VARCHAR(80) NULL,
        status_buy VARCHAR(80) NULL,
        site_visible VARCHAR(20) NULL,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_product_ref(ref), KEY idx_product_label(label), KEY idx_product_stock(physical_stock), KEY idx_product_visible(site_visible)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_structured_tiers (
        tenant_id INT NOT NULL DEFAULT 1,
        id INT NOT NULL,
        PRIMARY KEY(tenant_id, id),
        ref VARCHAR(90) NULL,
        name VARCHAR(190) NOT NULL,
        type VARCHAR(40) NOT NULL DEFAULT 'prospect',
        email VARCHAR(190) NULL,
        phone VARCHAR(80) NULL,
        city VARCHAR(120) NULL,
        country VARCHAR(120) NULL,
        status VARCHAR(80) NULL,
        ice VARCHAR(90) NULL,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_tier_ref(ref), KEY idx_tier_name(name), KEY idx_tier_type(type), KEY idx_tier_city(city), KEY idx_tier_email(email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_structured_sales (
        tenant_id INT NOT NULL DEFAULT 1,
        object_type VARCHAR(40) NOT NULL,
        object_id INT NOT NULL,
        ref VARCHAR(90) NULL,
        tier_id INT NULL,
        tier_name VARCHAR(190) NULL,
        status VARCHAR(90) NULL,
        amount_ht DECIMAL(15,3) NOT NULL DEFAULT 0,
        amount_ttc DECIMAL(15,3) NOT NULL DEFAULT 0,
        object_date DATE NULL,
        due_date DATE NULL,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(tenant_id, object_type, object_id),
        KEY idx_sales_ref(ref), KEY idx_sales_tier(tier_id), KEY idx_sales_status(status), KEY idx_sales_date(object_date), KEY idx_sales_amount(amount_ht)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_structured_stock_movements (
        tenant_id INT NOT NULL DEFAULT 1,
        id INT NOT NULL,
        PRIMARY KEY(tenant_id, id),
        product_id INT NULL,
        product_ref VARCHAR(90) NULL,
        product_label VARCHAR(190) NULL,
        warehouse_id INT NULL,
        warehouse_name VARCHAR(190) NULL,
        qty DECIMAL(15,3) NOT NULL DEFAULT 0,
        movement_type VARCHAR(80) NULL,
        source_type VARCHAR(80) NULL,
        source_id INT NULL,
        note TEXT NULL,
        movement_date DATETIME NULL,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_sm_product(product_id), KEY idx_sm_warehouse(warehouse_id), KEY idx_sm_type(movement_type), KEY idx_sm_date(movement_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_purchase_orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        ref VARCHAR(90) NOT NULL,
        supplier_id INT NULL,
        supplier_name VARCHAR(190) NOT NULL,
        order_date DATE NULL,
        status VARCHAR(80) NOT NULL DEFAULT 'Brouillon',
        amount_ht DECIMAL(15,3) NOT NULL DEFAULT 0,
        note TEXT NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_po_tenant_ref(tenant_id, ref), KEY idx_po_tenant(tenant_id), KEY idx_po_supplier(supplier_id), KEY idx_po_status(status), KEY idx_po_date(order_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_supplier_invoices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        ref VARCHAR(90) NOT NULL,
        supplier_id INT NULL,
        supplier_name VARCHAR(190) NOT NULL,
        invoice_date DATE NULL,
        due_date DATE NULL,
        status VARCHAR(80) NOT NULL DEFAULT 'À payer',
        amount_ht DECIMAL(15,3) NOT NULL DEFAULT 0,
        amount_ttc DECIMAL(15,3) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_si_tenant_ref(tenant_id, ref), KEY idx_si_tenant(tenant_id), KEY idx_si_supplier(supplier_id), KEY idx_si_status(status), KEY idx_si_due(due_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_credit_notes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        ref VARCHAR(90) NOT NULL,
        invoice_id INT NULL,
        client_name VARCHAR(190) NULL,
        credit_date DATE NULL,
        status VARCHAR(80) NOT NULL DEFAULT 'Brouillon',
        amount_ht DECIMAL(15,3) NOT NULL DEFAULT 0,
        amount_ttc DECIMAL(15,3) NOT NULL DEFAULT 0,
        reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_cn_tenant_ref(tenant_id, ref), KEY idx_cn_tenant(tenant_id), KEY idx_cn_invoice(invoice_id), KEY idx_cn_status(status), KEY idx_cn_date(credit_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_approval_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        title VARCHAR(190) NULL,
        object_type VARCHAR(80) NOT NULL,
        object_id INT NOT NULL,
        object_ref VARCHAR(90) NULL,
        requested_by INT NULL,
        approver_id INT NULL,
        status VARCHAR(80) NOT NULL DEFAULT 'En attente',
        amount DECIMAL(15,3) NOT NULL DEFAULT 0,
        priority VARCHAR(40) NOT NULL DEFAULT 'Normale',
        reason TEXT NULL,
        decision_reason TEXT NULL,
        decided_by INT NULL,
        decided_at DATETIME NULL,
        applied_at DATETIME NULL,
        template VARCHAR(90) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY idx_ap_tenant(tenant_id), KEY idx_ap_object(object_type, object_id), KEY idx_ap_status(status), KEY idx_ap_approver(approver_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_api_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        action VARCHAR(120) NULL,
        api_key_hash VARCHAR(80) NULL,
        ip VARCHAR(80) NULL,
        method VARCHAR(20) NULL,
        status_code INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_api_tenant(tenant_id), KEY idx_api_action(action), KEY idx_api_ip(ip), KEY idx_api_created(created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS ge_backups (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        filename VARCHAR(255) NOT NULL,
        note VARCHAR(255) NULL,
        size_bytes BIGINT NOT NULL DEFAULT 0,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY idx_backup_tenant(tenant_id), KEY idx_backup_created(created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function db_install_core(PDO $pdo){
    // Heavy schema checks are required on first install/update, but doing them on
    // every page load makes login and dashboard slow. A small metadata marker lets
    // normal requests skip the expensive information_schema / ALTER loop.
    if(ge_schema_is_current($pdo)){
        return;
    }
    ge_install_tenancy_tables($pdo);
    db_install_audit($pdo);
    foreach(ge_known_collections() as $collection){
        db_install_collection_table($pdo, ge_collection_table($collection));
    }
    db_install_enterprise_tables($pdo);
    ge_tenancy_install_all_known_tables($pdo);
    ge_schema_meta_set($pdo, 'schema_version', ge_schema_version());
}
function db_table_exists(PDO $pdo, $table){
    $stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}
function data_decode_payload($payload){
    $payload=(string)$payload;
    $json=json_decode($payload, true);
    if(json_last_error()===JSON_ERROR_NONE && is_array($json)) return $json;
    $legacy=@unserialize($payload, ['allowed_classes'=>false]);
    return is_array($legacy) ? $legacy : null;
}
function data_encode_payload($row){
    return json_encode((array)$row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
function data_index_fields($row){
    $row=is_array($row)?$row:[];
    $ref=(string)($row['ref'] ?? $row['code'] ?? $row['username'] ?? '');
    $label=(string)($row['label'] ?? $row['name'] ?? $row['client'] ?? $row['tier_name'] ?? $row['subject'] ?? '');
    $status=(string)($row['status'] ?? $row['state'] ?? '');
    $amount=null;
    foreach(['total_ttc','amount_ttc','total_ht','amount_ht','amount','total'] as $k){
        if(isset($row[$k]) && is_numeric($row[$k])){ $amount=(float)$row[$k]; break; }
    }
    return [$ref ?: null, $label ?: null, $status ?: null, $amount];
}
function legacy_data_read($name){
    $pdo=db();
    if(!db_table_exists($pdo,'procrm_records')) return [];
    $stmt=$pdo->prepare('SELECT payload FROM procrm_records WHERE collection=? ORDER BY record_id ASC');
    $stmt->execute([$name]);
    $rows=[];
    foreach($stmt as $r){
        $payload=data_decode_payload($r['payload']);
        if(is_array($payload)) $rows[]=$payload;
    }
    return $rows;
}
function data_read($name, $default=[]){
    global $GE_DATA_READ_CACHE;
    if(!is_array($GE_DATA_READ_CACHE ?? null)) $GE_DATA_READ_CACHE = [];
    $cacheKey = (function_exists('ge_current_tenant_id') ? (int)ge_current_tenant_id() : 1).'|'.(string)$name;
    if(array_key_exists($cacheKey, $GE_DATA_READ_CACHE)) return $GE_DATA_READ_CACHE[$cacheKey] ?: $default;
    $pdo=db();
    $table=ge_collection_table($name);
    db_install_collection_table($pdo,$table);
    $schema=function_exists('ge_schema_for') ? ge_schema_for($name) : null;
    $rows=[];
    if($schema){
        $stmt=$pdo->prepare('SELECT * FROM '.ge_identifier($table).' WHERE tenant_id=? ORDER BY record_id ASC');
        $stmt->execute([ge_current_tenant_id()]);
        foreach($stmt as $r){ $rows[]=ge_schema_db_row_to_app($name,$r); }
    }else{
        $stmt=$pdo->prepare('SELECT * FROM '.ge_identifier($table).' WHERE tenant_id=? ORDER BY record_id ASC');
        $stmt->execute([ge_current_tenant_id()]);
        foreach($stmt as $r){
            $payload=data_decode_payload($r['payload'] ?? '');
            if(is_array($payload)){
                $payload['id']=(int)($payload['id'] ?? $r['record_id'] ?? 0);
                if(isset($r['tenant_id']) && !isset($payload['tenant_id'])) $payload['tenant_id']=(int)$r['tenant_id'];
                foreach(['ref','label','status','amount','created_at','updated_at'] as $k){ if(isset($r[$k]) && !isset($payload[$k])) $payload[$k]=$r[$k]; }
                $rows[]=$payload;
            }
        }
    }
    if(!$rows && (!function_exists('ge_tenancy_enabled') || !ge_tenancy_enabled() || ge_current_tenant_id() === ge_default_tenant_id())){
        $legacy=legacy_data_read($name);
        if($legacy){ data_write($name,$legacy,false); return $legacy; }
    }
    if($rows && function_exists('ge_hydrate_document_lines')) $rows=ge_hydrate_document_lines($name,$rows);
    $GE_DATA_READ_CACHE[$cacheKey] = $rows;
    return $rows ?: $default;
}
function data_read_cache_clear($name=null){
    global $GE_DATA_READ_CACHE;
    if(!is_array($GE_DATA_READ_CACHE ?? null)){ $GE_DATA_READ_CACHE = []; return; }
    if($name === null){ $GE_DATA_READ_CACHE = []; return; }
    $suffix='|'.(string)$name;
    foreach(array_keys($GE_DATA_READ_CACHE) as $k){ if(str_ends_with((string)$k, $suffix)) unset($GE_DATA_READ_CACHE[$k]); }
}


function ge_sanitize_collection_row($name, $row){
    $row=is_array($row)?$row:[];
    if($name === 'users'){
        unset($row['plain_password'], $row['password_plain']);
    }
    return $row;
}

function ge_insert_collection_rows(PDO $pdo, $name, $data){
    if(function_exists('ge_schema_insert_rows') && ge_schema_for($name)){
        return ge_schema_insert_rows($pdo, $name, is_array($data)?$data:[]);
    }
    $table=ge_collection_table($name);
    // IMPORTANT: do not run CREATE TABLE while a transaction is active.
    if(!$pdo->inTransaction()) db_install_collection_table($pdo,$table);

    $ins=$pdo->prepare('INSERT INTO '.ge_identifier($table).'(tenant_id,record_id,payload,extra_json,ref,label,status,amount) VALUES(?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE payload=VALUES(payload), extra_json=VALUES(extra_json), ref=VALUES(ref), label=VALUES(label), status=VALUES(status), amount=VALUES(amount), updated_at=CURRENT_TIMESTAMP');
    $incoming=[]; $i=1;
    foreach((array)$data as $row){
        if(!is_array($row)) continue;
        $row=ge_sanitize_collection_row($name,$row);
        $id=(int)($row['id'] ?? $row['record_id'] ?? $i);
        if($id<=0) $id=$i;
        $incoming[]=$id;
        [$ref,$label,$status,$amount]=data_index_fields($row);
        $payload=data_encode_payload($row);
        $ins->execute([ge_current_tenant_id(), $id, $payload, $payload, $ref, $label, $status, $amount]);
        $i++;
    }

    // Incremental delete: remove records that are no longer present without wiping the whole table first.
    if($incoming){
        $incoming=array_values(array_unique(array_map('intval',$incoming)));
        $ph=implode(',', array_fill(0,count($incoming),'?'));
        $del=$pdo->prepare('DELETE FROM '.ge_identifier($table).' WHERE tenant_id=? AND record_id NOT IN ('.$ph.')');
        $del->execute(array_merge([ge_current_tenant_id()], $incoming));
    }else{
        $stmt=$pdo->prepare('DELETE FROM '.ge_identifier($table).' WHERE tenant_id=?'); $stmt->execute([ge_current_tenant_id()]);
    }
}


function ge_date_or_null($v){
    $v=trim((string)$v); if($v==='') return null;
    if(preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v, $m)) return $m[1].'-'.$m[2].'-'.$m[3];
    if(preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $v, $m)) return $m[3].'-'.$m[2].'-'.$m[1];
    return null;
}
function ge_decimal($v){ return is_numeric($v) ? (float)$v : (function_exists('ge_parse_number') ? ge_parse_number($v) : (float)str_replace(',','.',(string)$v)); }
function ge_sync_delete_missing(PDO $pdo, $table, $idColumn, $ids, $extraWhere='', $extraParams=[]){
    $ids=array_values(array_unique(array_filter(array_map('intval',(array)$ids), fn($id)=>$id>0)));
    $sql='DELETE FROM '.ge_identifier($table);
    $params=[]; $hasWhere=false;
    if(function_exists('ge_table_column_exists') && ge_table_column_exists($pdo,(string)$table,'tenant_id')){
        $sql.=' WHERE tenant_id=?'; $params[] = ge_current_tenant_id(); $hasWhere=true;
    }
    if($extraWhere){ $sql.=($hasWhere?' AND ':' WHERE ').$extraWhere; $params=array_merge($params,$extraParams); $hasWhere=true; }
    if($ids){
        $ph=implode(',', array_fill(0,count($ids),'?'));
        $sql.=($hasWhere?' AND ':' WHERE ').ge_identifier($idColumn).' NOT IN ('.$ph.')';
        $params=array_merge($params,$ids);
    }
    $stmt=$pdo->prepare($sql); $stmt->execute($params);
}
function ge_sync_structured_shadow($name, $rows){
    try{
        $pdo=db(); db_install_enterprise_tables($pdo);
        $rows=is_array($rows)?$rows:[];
        if($name==='products'){
            $stmt=$pdo->prepare('INSERT INTO ge_structured_products(tenant_id,id,ref,label,type,brand,sale_price,buy_price,physical_stock,virtual_stock,alert_stock,warehouse_id,status_sale,status_buy,site_visible) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE ref=VALUES(ref),label=VALUES(label),type=VALUES(type),brand=VALUES(brand),sale_price=VALUES(sale_price),buy_price=VALUES(buy_price),physical_stock=VALUES(physical_stock),virtual_stock=VALUES(virtual_stock),alert_stock=VALUES(alert_stock),warehouse_id=VALUES(warehouse_id),status_sale=VALUES(status_sale),status_buy=VALUES(status_buy),site_visible=VALUES(site_visible),updated_at=CURRENT_TIMESTAMP');
            $ids=[];
            foreach($rows as $r){ if(!is_array($r)) continue; $id=(int)($r['id']??0); if($id<=0) continue; $ids[]=$id; $stmt->execute([ge_current_tenant_id(),$id,$r['ref']??null,$r['label']??($r['name']??'Produit'),$r['type']??null,$r['product_type']??($r['brand']??null),ge_decimal($r['sale_price']??0),ge_decimal($r['buy_price']??0),ge_decimal($r['physical_stock']??$r['stock']??0),ge_decimal($r['virtual_stock']??$r['physical_stock']??0),ge_decimal($r['alert_stock']??0),(int)($r['warehouse_id']??0) ?: null,$r['sale_status']??null,$r['buy_status']??null,$r['site_visible']??null]); }
            ge_sync_delete_missing($pdo,'ge_structured_products','id',$ids); return;
        }
        if(in_array($name,['tiers','clients','suppliers'],true)){
            if($name==='clients') $defaultType='client'; elseif($name==='suppliers') $defaultType='supplier'; else $defaultType='prospect';
            $stmt=$pdo->prepare('INSERT INTO ge_structured_tiers(tenant_id,id,ref,name,type,email,phone,city,country,status,ice) VALUES(?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE ref=VALUES(ref),name=VALUES(name),type=VALUES(type),email=VALUES(email),phone=VALUES(phone),city=VALUES(city),country=VALUES(country),status=VALUES(status),ice=VALUES(ice),updated_at=CURRENT_TIMESTAMP');
            $ids=[];
            foreach($rows as $r){ if(!is_array($r)) continue; $id=(int)($r['id']??0); if($id<=0) continue; $ids[]=$id; $type=$r['type']??$defaultType; $stmt->execute([ge_current_tenant_id(),$id,$r['ref']??($r['code']??null),$r['name']??($r['label']??'Tiers'),$type,$r['email']??null,$r['phone']??($r['mobile']??null),$r['city']??null,$r['country']??null,$r['status']??null,$r['ice']??($r['tax_id']??null)]); }
            ge_sync_delete_missing($pdo,'ge_structured_tiers','id',$ids, $name==='tiers' ? '' : 'type=?', $name==='tiers' ? [] : [$defaultType]); return;
        }
        $salesMap=['quotes'=>'quote','orders'=>'order','invoices'=>'invoice','expeditions'=>'expedition','receptions'=>'reception'];
        if(isset($salesMap[$name])){
            $type=$salesMap[$name];
            $stmt=$pdo->prepare('INSERT INTO ge_structured_sales(tenant_id,object_type,object_id,ref,tier_id,tier_name,status,amount_ht,amount_ttc,object_date,due_date) VALUES(?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE ref=VALUES(ref),tier_id=VALUES(tier_id),tier_name=VALUES(tier_name),status=VALUES(status),amount_ht=VALUES(amount_ht),amount_ttc=VALUES(amount_ttc),object_date=VALUES(object_date),due_date=VALUES(due_date),updated_at=CURRENT_TIMESTAMP');
            $ids=[];
            foreach($rows as $r){ if(!is_array($r)) continue; $id=(int)($r['id']??0); if($id<=0) continue; $ids[]=$id; $stmt->execute([ge_current_tenant_id(),$type,$id,$r['ref']??null,(int)($r['tier_id']??$r['client_id']??0) ?: null,$r['tier_name']??($r['client']??$r['client_name']??null),$r['status']??null,ge_decimal($r['amount_ht']??$r['total_ht']??$r['amount']??0),ge_decimal($r['amount_ttc']??$r['total_ttc']??0),ge_date_or_null($r['date']??$r['order_date']??$r['proposal_date']??$r['delivery_date']??''),ge_date_or_null($r['due_date']??$r['delivery_date']??'')]); }
            ge_sync_delete_missing($pdo,'ge_structured_sales','object_id',$ids,'object_type=?',[$type]); return;
        }
        if($name==='warehouse_movements'){
            $stmt=$pdo->prepare('INSERT INTO ge_structured_stock_movements(tenant_id,id,product_id,product_ref,product_label,warehouse_id,warehouse_name,qty,movement_type,source_type,source_id,note,movement_date) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE product_id=VALUES(product_id),product_ref=VALUES(product_ref),product_label=VALUES(product_label),warehouse_id=VALUES(warehouse_id),warehouse_name=VALUES(warehouse_name),qty=VALUES(qty),movement_type=VALUES(movement_type),source_type=VALUES(source_type),source_id=VALUES(source_id),note=VALUES(note),movement_date=VALUES(movement_date),updated_at=CURRENT_TIMESTAMP');
            $ids=[];
            foreach($rows as $r){ if(!is_array($r)) continue; $id=(int)($r['id']??0); if($id<=0) continue; $ids[]=$id; $stmt->execute([ge_current_tenant_id(),$id,(int)($r['product_id']??0) ?: null,$r['product_ref']??null,$r['product_label']??($r['label']??null),(int)($r['warehouse_id']??0) ?: null,$r['warehouse_name']??null,ge_decimal($r['qty']??0),$r['type']??($r['movement_type']??null),$r['source_type']??null,(int)($r['source_id']??0) ?: null,$r['note']??null,ge_date_or_null($r['date']??$r['created_at']??'')]); }
            ge_sync_delete_missing($pdo,'ge_structured_stock_movements','id',$ids); return;
        }
    }catch(Throwable $e){ try{ audit_log('structured_sync_error', $name.': '.$e->getMessage()); }catch(Throwable $ignored){} }
}


function data_write_batch($collections, $audit=true){
    if(!is_array($collections)) return;
    if(function_exists('data_read_cache_clear')) data_read_cache_clear();
    $pdo=db();
    $oldCounts=[];
    foreach($collections as $name=>$data){
        $table=ge_collection_table($name);
        db_install_collection_table($pdo,$table);
        try{ $stmt=$pdo->prepare('SELECT COUNT(*) FROM '.ge_identifier($table).' WHERE tenant_id=?'); $stmt->execute([ge_current_tenant_id()]); $oldCounts[$name]=(int)$stmt->fetchColumn(); }catch(Throwable $e){ $oldCounts[$name]=0; }
    }
    $startedTransaction = !$pdo->inTransaction();
    if($startedTransaction) $pdo->beginTransaction();
    try{
        foreach($collections as $name=>$data){
            ge_insert_collection_rows($pdo, (string)$name, is_array($data)?$data:[]);
        }
        if($startedTransaction && $pdo->inTransaction()) $pdo->commit();
        foreach($collections as $syncName=>$syncData){
            ge_sync_structured_shadow((string)$syncName, is_array($syncData)?$syncData:[]);
            if(function_exists('ge_sync_document_lines_from_rows')) ge_sync_document_lines_from_rows((string)$syncName, is_array($syncData)?$syncData:[]);
            if(function_exists('ge_erp_auto_approvals_from_rows')) ge_erp_auto_approvals_from_rows((string)$syncName, is_array($syncData)?$syncData:[]);
        }
        if($audit){
            foreach($collections as $name=>$data){
                if($name !== 'settings') audit_log('data_write_batch', 'Table: '.ge_collection_table($name).' | old rows: '.($oldCounts[$name]??0).' | new rows: '.(is_array($data)?count($data):0));
            }
        }
        if(isset($collections['products']) && function_exists('ge_stock_alert_after_products_write')){
            try{ ge_stock_alert_after_products_write($collections['products']); }catch(Throwable $stockAlertError){ try{ audit_log('stock_alert_error',$stockAlertError->getMessage()); }catch(Throwable $ignored){} }
        }
    }catch(Throwable $e){
        if($startedTransaction && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

function data_write($name, $data, $audit=true){
    if(function_exists('data_read_cache_clear')) data_read_cache_clear($name);
    if(!is_array($data)) $data=[];
    $pdo=db();
    $table=ge_collection_table($name);
    db_install_collection_table($pdo,$table);
    $oldCount=0;
    try{ $stmt=$pdo->prepare('SELECT COUNT(*) FROM '.ge_identifier($table).' WHERE tenant_id=?'); $stmt->execute([ge_current_tenant_id()]); $oldCount=(int)$stmt->fetchColumn(); }catch(Throwable $e){}
    $startedTransaction = !$pdo->inTransaction();
    if($startedTransaction) $pdo->beginTransaction();
    try{
        ge_insert_collection_rows($pdo, $name, $data);
        if($startedTransaction && $pdo->inTransaction()) $pdo->commit();
        ge_sync_structured_shadow($name, $data);
        if(function_exists('ge_sync_document_lines_from_rows')) ge_sync_document_lines_from_rows($name, $data);
        if(function_exists('ge_erp_auto_approvals_from_rows')) ge_erp_auto_approvals_from_rows((string)$name, is_array($data)?$data:[]);
        if($audit && $name !== 'settings') audit_log('data_write', 'Table: '.$table.' | old rows: '.$oldCount.' | new rows: '.count($data));
        if($name === 'products' && function_exists('ge_stock_alert_after_products_write')){
            try{
                ge_stock_alert_after_products_write($data);
            }catch(Throwable $stockAlertError){
                try{ audit_log('stock_alert_error', $stockAlertError->getMessage()); }catch(Throwable $ignored){}
            }
        }
    }catch(Throwable $e){
        if($startedTransaction && $pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}
function collection_exists($name){
    $pdo=db(); $table=ge_collection_table($name); db_install_collection_table($pdo,$table);
    $stmt=$pdo->prepare('SELECT COUNT(*) FROM '.ge_identifier($table).' WHERE tenant_id=?');
    $stmt->execute([ge_current_tenant_id()]);
    if((int)$stmt->fetchColumn()>0) return true;
    return (bool)legacy_data_read($name);
}

function ge_unlink_document_file($doc){
    $paths=[];
    if(!empty($doc['path'])) $paths[]=$doc['path'];
    if(!empty($doc['url'])) $paths[]=__DIR__.'/../'.ltrim((string)$doc['url'],'/');
    $uploads=@realpath(__DIR__.'/../uploads');
    foreach($paths as $path){
        $real=@realpath($path);
        if($real && $uploads && str_starts_with($real, $uploads.DIRECTORY_SEPARATOR) && is_file($real)) @unlink($real);
    }
}
function ge_parse_number($value){
    $v=(string)$value;
    $v=str_replace(["\xc2\xa0", ' '], '', $v);
    $v=str_replace(',', '.', $v);
    $v=preg_replace('/[^0-9.\-]+/', '', $v);
    if(substr_count($v,'.')>1){
        $parts=explode('.',$v); $last=array_pop($parts); $v=implode('', $parts).'.'.$last;
    }
    return is_numeric($v) ? (float)$v : 0.0;
}

function ge_sync_document_lines($collection, $foreignKey, $docId, $lines){
    $docId=(int)$docId; if($docId<=0) return;
    $rows=array_values(array_filter(data_read($collection, []), fn($r)=>(int)($r[$foreignKey] ?? 0)!==$docId));
    $next=next_id($rows); $lineNo=1;
    foreach((array)$lines as $line){
        if(!is_array($line)) continue;
        $row=$line;
        $row['id']=$next++;
        $row[$foreignKey]=$docId;
        $row['line_no']=$lineNo++;
        $rows[]=$row;
    }
    data_write($collection,$rows,false);
}
function ge_delete_document_lines($collection, $foreignKey, $docId){
    $docId=(int)$docId; if($docId<=0) return;
    data_write($collection, array_values(array_filter(data_read($collection, []), fn($r)=>(int)($r[$foreignKey] ?? 0)!==$docId)), false);
}

function next_id($rows){ $max=0; foreach($rows as $r){ $max=max($max,(int)($r['id']??0)); } return $max+1; }
function current_page(){ return $_GET['page'] ?? 'dashboard'; }
function active($page){ return current_page()===$page ? 'active' : ''; }
function product_url($id,$tab='product'){ return 'index.php?page=product_show&id='.(int)$id.'&tab='.urlencode($tab); }

function asset_url($path){ return $path; }
function product_image_src($product){
    $img = trim((string)($product['image'] ?? ''));
    if($img === '') return 'assets/images/product-placeholder.svg';
    if(preg_match('/^https?:\/\//i', $img) || substr($img, 0, 1) === '/') return $img;
    return 'uploads/products/'.rawurlencode(basename(str_replace('\\', '/', $img)));
}
function find_row_by_id($rows,$id){ foreach($rows as $r){ if((int)($r['id']??0)===(int)$id) return $r; } return null; }


function ge_user_full_name($user=null){
    $user = is_array($user) ? $user : [];
    $first = trim((string)($user['firstname'] ?? $user['first_name'] ?? ''));
    $last = trim((string)($user['name'] ?? $user['last_name'] ?? ''));
    $full = trim($first.' '.$last);
    if($full !== '') return $full;
    foreach(['display_name','username','email'] as $key){
        $v = trim((string)($user[$key] ?? ''));
        if($v !== '') return $v;
    }
    return 'Utilisateur';
}
function ge_current_author_name(){ return ge_user_full_name(function_exists('current_user') ? current_user() : null); }
function ge_current_author_id(){ $u = function_exists('current_user') ? current_user() : null; return (int)($u['id'] ?? 0); }
function ge_current_author_username(){ $u = function_exists('current_user') ? current_user() : null; return trim((string)($u['username'] ?? $u['email'] ?? '')); }
function ge_users_cache(){ static $users=null; if($users===null) $users=data_read('users', []); return $users; }
function ge_find_user_by_id($id){
    $id=(int)$id; if($id<=0) return null;
    foreach(ge_users_cache() as $u){ if((int)($u['id']??0)===$id) return $u; }
    return null;
}
function ge_find_user_by_login($login){
    $login=strtolower(trim((string)$login)); if($login==='') return null;
    foreach(ge_users_cache() as $u){
        $username=strtolower(trim((string)($u['username']??'')));
        $email=strtolower(trim((string)($u['email']??'')));
        if($username===$login || $email===$login) return $u;
    }
    return null;
}
function ge_record_author($row,$field='author'){
    $row = is_array($row) ? $row : [];
    $idKeys = [$field.'_id','created_by_id','author_id','user_id'];
    foreach($idKeys as $key){
        if(!empty($row[$key])){ $u=ge_find_user_by_id((int)$row[$key]); if($u) return ge_user_full_name($u); }
    }
    $stored = trim((string)($row[$field] ?? ''));
    if($stored==='') $stored = trim((string)($row['created_by'] ?? $row['author'] ?? ''));
    $login = trim((string)($row[$field.'_username'] ?? $row['created_by_username'] ?? $row['author_username'] ?? ''));
    if($login!==''){ $u=ge_find_user_by_login($login); if($u) return ge_user_full_name($u); }
    if($stored!=='' && !in_array(strtolower($stored), ['superadmin','admin'], true)) return $stored;
    if($stored!==''){
        $u=ge_find_user_by_login($stored==='SuperAdmin' ? 'admin' : $stored);
        if($u) return ge_user_full_name($u);
    }
    return 'Utilisateur';
}
function ge_author_fields($field='author'){
    $id=ge_current_author_id(); $username=ge_current_author_username(); $name=ge_current_author_name();
    $out=[$field=>$name];
    if($id>0) $out[$field.'_id']=$id;
    if($username!=='') $out[$field.'_username']=$username;
    return $out;
}

function tiers_all(){ return data_read('tiers', []); }
function tiers_by_type($type){ return array_values(array_filter(tiers_all(), fn($t)=>($t['type']??'')===$type)); }

function ge_tier_is_client($t){
    return (($t['type'] ?? '') === 'client') || !empty($t['is_client']);
}
function ge_client_code($t){
    return $t['code_client'] ?? $t['ref'] ?? $t['code'] ?? '';
}
function ge_available_clients(){
    $clients=[]; $seen=[];
    foreach(tiers_all() as $t){
        if(!ge_tier_is_client($t)) continue;
        $name=trim((string)($t['name'] ?? $t['label'] ?? ''));
        if($name==='') continue;
        $id=(int)($t['id'] ?? 0);
        $key=$id>0 ? 'id_'.$id : 'name_'.mb_strtolower($name);
        if(isset($seen[$key])) continue;
        $seen[$key]=true;
        $clients[]=[
            'id'=>$id,
            'name'=>$name,
            'ref'=>ge_client_code($t),
            'email'=>$t['email'] ?? '',
            'phone'=>$t['phone'] ?? ($t['mobile'] ?? ''),
            'address'=>$t['address'] ?? '',
            'city'=>$t['city'] ?? '',
            'zip'=>$t['zip'] ?? ''
        ];
    }
    foreach(data_read('clients',[]) as $c){
        $name=trim((string)($c['name'] ?? $c['label'] ?? ''));
        if($name==='') continue;
        $id=(int)($c['id'] ?? 0);
        $key=$id>0 ? 'legacy_'.$id : 'legacy_'.mb_strtolower($name);
        if(isset($seen[$key]) || isset($seen['name_'.mb_strtolower($name)])) continue;
        $seen[$key]=true;
        $clients[]=['id'=>$id,'name'=>$name,'ref'=>$c['ref'] ?? $c['code'] ?? '','email'=>$c['email'] ?? '','phone'=>$c['phone'] ?? '','address'=>$c['address'] ?? '','city'=>$c['city'] ?? '','zip'=>$c['zip'] ?? ''];
    }
    usort($clients, fn($a,$b)=>strcasecmp($a['name'],$b['name']));
    return $clients;
}
function ge_client_from_post(){
    $clientId=(int)($_POST['client_id'] ?? 0);
    $postedName=trim((string)($_POST['client'] ?? ''));
    foreach(ge_available_clients() as $c){
        if($clientId>0 && (int)($c['id'] ?? 0)===$clientId) return ['client_id'=>$clientId,'client'=>$c['name'],'client_ref'=>$c['ref'] ?? ''];
    }
    foreach(ge_available_clients() as $c){
        if($postedName!=='' && $postedName===$c['name']) return ['client_id'=>(int)($c['id'] ?? 0),'client'=>$c['name'],'client_ref'=>$c['ref'] ?? ''];
    }
    return ['client_id'=>0,'client'=>'','client_ref'=>''];
}
function tier_type_label($type){ return ['prospect'=>'Prospect','client'=>'Client','supplier'=>'Fournisseur'][$type] ?? 'Tiers'; }
function tier_type_badge($type){ return ['prospect'=>'badge-blue','client'=>'badge-green','supplier'=>'badge-orange'][$type] ?? 'badge-gray'; }
function tier_ref($type,$id){ $prefix=['prospect'=>'PR','client'=>'CU','supplier'=>'FO'][$type] ?? 'TI'; return $prefix.date('ym').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT); }
function sync_tiers_legacy($tiers){
    $clients=[]; $suppliers=[];
    foreach($tiers as $t){
        $row=['id'=>$t['id']??0,'name'=>$t['name']??'','email'=>$t['email']??'','phone'=>$t['phone']??'','city'=>$t['city']??'','country'=>$t['country']??''];
        if(($t['type']??'')==='client') $clients[]=$row;
        if(($t['type']??'')==='supplier') $suppliers[]=$row;
    }
    data_write('clients',$clients); data_write('suppliers',$suppliers);
}

function chart_percent($value,$total){ return $total>0 ? max(0,min(100,round(($value/$total)*100,1))) : 0; }
function month_key_from_date($date){
    if(!$date) return date('n');
    $date=(string)$date;
    if(preg_match('/^(\d{4})-(\d{2})-/', $date, $m)) return (int)$m[2];
    if(preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $date, $m)) return (int)$m[2];
    return date('n');
}
function amount_from_row($row){ return (float)($row['amount_ht'] ?? $row['total_ht'] ?? $row['total'] ?? $row['amount'] ?? 0); }

function mail_clean_addr($v){
    $v=trim((string)$v);
    if(preg_match('/<([^>]+)>/', $v, $m)) $v=trim($m[1]);
    return $v;
}
function mail_split_addresses($v){
    $parts=preg_split('/[,;]+/', (string)$v);
    $out=[];
    foreach($parts as $p){ $a=mail_clean_addr($p); if($a!=='' && filter_var($a, FILTER_VALIDATE_EMAIL)) $out[]=$a; }
    return array_values(array_unique($out));
}
function smtp_read_line($fp){
    $data='';
    while(!feof($fp)){
        $line=fgets($fp, 515);
        if($line===false) break;
        $data.=$line;
        if(strlen($line)>=4 && $line[3]===' ') break;
    }
    return $data;
}
function smtp_expect($fp, $codes){
    $resp=smtp_read_line($fp);
    $code=(int)substr($resp,0,3);
    if(!in_array($code,(array)$codes,true)) throw new Exception(trim($resp) ?: 'SMTP error');
    return $resp;
}
function smtp_cmd($fp, $cmd, $codes){
    fwrite($fp, $cmd."\r\n");
    return smtp_expect($fp, $codes);
}
function mail_uploaded_attachments($field='attachments'){
    static $cache=[];
    if(array_key_exists($field,$cache)) return $cache[$field];
    $files=$_FILES[$field] ?? null;
    if(!$files || empty($files['name'])) return $cache[$field]=[];
    $out=[]; $max=10*1024*1024;
    $names=is_array($files['name']) ? $files['name'] : [$files['name']];
    $tmps=is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $errs=is_array($files['error']) ? $files['error'] : [$files['error']];
    $sizes=is_array($files['size']) ? $files['size'] : [$files['size']];
    $allowed=['pdf'=>'application/pdf','png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','webp'=>'image/webp','gif'=>'image/gif'];
    foreach($names as $i=>$name){
        if(($errs[$i] ?? UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) continue;
        if(($errs[$i] ?? UPLOAD_ERR_OK)!==UPLOAD_ERR_OK) throw new Exception('Erreur upload pièce jointe: '.$name);
        if(($sizes[$i] ?? 0)>$max) throw new Exception('Pièce jointe trop grande (max 10 MB): '.$name);
        $ext=strtolower(pathinfo((string)$name, PATHINFO_EXTENSION));
        if(!isset($allowed[$ext])) throw new Exception('Type non autorisé: '.$name.' (PDF ou image seulement)');
        $tmp=$tmps[$i] ?? '';
        if(!is_uploaded_file($tmp) && !file_exists($tmp)) throw new Exception('Fichier joint introuvable: '.$name);
        $safe=preg_replace('/[^A-Za-z0-9._-]+/','_',basename((string)$name));
        $out[]=['name'=>$safe ?: ('attachment.'.$ext),'tmp'=>$tmp,'mime'=>$allowed[$ext]];
    }
    return $cache[$field]=$out;
}
function mail_attachment_names($attachments){
    $out=[]; foreach((array)$attachments as $a){ if(!empty($a['name'])) $out[]=$a['name']; }
    return implode(', ', $out);
}

function ge_mail_header_addr($name, $email){
    $name=trim(str_replace(["\r","\n",'"'], ['','',''], (string)$name));
    $email=mail_clean_addr($email);
    return $name!=='' ? '"'.$name.'" <'.$email.'>' : $email;
}
function ge_mail_build_message($fromName,$from,$toList,$ccList,$subject,$message,$attachments=[]){
    $subject=str_replace(["\r","\n"],' ',(string)$subject);
    $boundary='=_GE_'.bin2hex(random_bytes(12));
    $headers=[];
    $headers[]='From: '.ge_mail_header_addr($fromName,$from);
    $headers[]='To: '.implode(', ', $toList);
    if($ccList) $headers[]='Cc: '.implode(', ', $ccList);
    $headers[]='Subject: '.$subject;
    $headers[]='MIME-Version: 1.0';
    $headers[]='Date: '.date('r');
    $headers[]='Message-ID: <'.bin2hex(random_bytes(12)).'@'.preg_replace('/^www\./','', $_SERVER['HTTP_HOST'] ?? 'localhost').'>';
    if($attachments){
        $headers[]='Content-Type: multipart/mixed; boundary="'.$boundary.'"';
        $body="--$boundary\r\n";
        $body.="Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n";
        $body.=quoted_printable_encode(nl2br(htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8')))."\r\n";
        foreach($attachments as $att){
            if(empty($att['tmp']) || empty($att['name']) || !is_file($att['tmp'])) continue;
            $data=@file_get_contents($att['tmp']); if($data===false) continue;
            $filename=str_replace(['"',"\r","\n"], '', basename((string)$att['name']));
            $mime=$att['mime'] ?? 'application/octet-stream';
            $body.="--$boundary\r\n";
            $body.='Content-Type: '.$mime.'; name="'.$filename."\"\r\n";
            $body.="Content-Transfer-Encoding: base64\r\n";
            $body.='Content-Disposition: attachment; filename="'.$filename."\"\r\n\r\n";
            $body.=chunk_split(base64_encode($data))."\r\n";
        }
        $body.="--$boundary--\r\n";
    }else{
        $headers[]='Content-Type: text/html; charset=UTF-8';
        $headers[]='Content-Transfer-Encoding: quoted-printable';
        $body=quoted_printable_encode(nl2br(htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8')))."\r\n";
    }
    return implode("\r\n", $headers)."\r\n\r\n".$body;
}
function ge_send_smtp_email($host,$port,$secure,$username,$password,$from,$fromName,$toList,$ccList,$subject,$message,$attachments=[]){
    $host=trim((string)$host); $port=(int)$port; if($port<=0) $port=587;
    $secure=strtolower(trim((string)$secure));
    $remote=($secure==='ssl' || $port===465 ? 'ssl://' : '').$host;
    $fp=@stream_socket_client($remote.':'.$port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if(!$fp) return ['ok'=>false,'error'=>'SMTP connection failed: '.$errstr];
    stream_set_timeout($fp, 30);
    try{
        smtp_expect($fp, [220]);
        $ehloHost=preg_replace('/[^A-Za-z0-9.-]+/','', $_SERVER['HTTP_HOST'] ?? 'localhost') ?: 'localhost';
        smtp_cmd($fp, 'EHLO '.$ehloHost, [250]);
        if(($secure==='tls' || $secure==='starttls') && $port!==465){
            smtp_cmd($fp, 'STARTTLS', [220]);
            if(!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new Exception('STARTTLS failed');
            smtp_cmd($fp, 'EHLO '.$ehloHost, [250]);
        }
        if(trim((string)$username)!==''){
            smtp_cmd($fp, 'AUTH LOGIN', [334]);
            smtp_cmd($fp, base64_encode((string)$username), [334]);
            smtp_cmd($fp, base64_encode((string)$password), [235]);
        }
        smtp_cmd($fp, 'MAIL FROM:<'.mail_clean_addr($from).'>', [250]);
        foreach(array_values(array_unique(array_merge($toList,$ccList))) as $rcpt){ smtp_cmd($fp, 'RCPT TO:<'.$rcpt.'>', [250,251]); }
        smtp_cmd($fp, 'DATA', [354]);
        $raw=ge_mail_build_message($fromName,$from,$toList,$ccList,$subject,$message,$attachments);
        $raw=preg_replace('/^\./m', '..', $raw);
        fwrite($fp, $raw."\r\n.\r\n");
        smtp_expect($fp, [250]);
        @smtp_cmd($fp, 'QUIT', [221,250]);
        fclose($fp);
        return ['ok'=>true,'error'=>''];
    }catch(Throwable $e){
        @fwrite($fp,"QUIT\r\n"); @fclose($fp);
        return ['ok'=>false,'error'=>$e->getMessage()];
    }
}
function send_real_email($to, $subject, $message, $cc='', $attachments=[]){
    $cfg = app_config()['mail'] ?? [];
    $settings = function_exists('app_settings') ? app_settings() : [];
    $apiKey = trim((string)(getenv('RESEND_API_KEY') ?: getenv('GE_RESEND_API_KEY') ?: getenv('RESEND_KEY') ?: getenv('RESEND_APIKEY') ?: ($settings['smtp_resend_api_key'] ?? '') ?: ($cfg['resend_api_key'] ?? '')));
    $from = trim((string)(getenv('GE_SMTP_FROM_EMAIL') ?: ($settings['smtp_from_email'] ?? '') ?: ($cfg['from_email'] ?? '') ?: 'onboarding@resend.dev'));
    $fromName = trim((string)(getenv('GE_SMTP_FROM_NAME') ?: ($settings['smtp_from_name'] ?? '') ?: ($cfg['from_name'] ?? '') ?: ($settings['company_name'] ?? '') ?: 'Global Energie'));

    $toList = mail_split_addresses($to);
    $ccList = mail_split_addresses($cc);

    if (!$toList) {
        return ['ok' => false, 'error' => 'Adresse destinataire invalide.'];
    }

    if ($apiKey) {
        $payload = [
            'from' => $fromName . ' <' . $from . '>',
            'to' => $toList,
            'subject' => $subject,
            'html' => nl2br(htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8'))
        ];
        if ($ccList) $payload['cc'] = $ccList;
        if (!empty($attachments)) {
            $payload['attachments'] = [];
            foreach ($attachments as $att) {
                if (empty($att['tmp']) || empty($att['name'])) continue;
                $content = @file_get_contents($att['tmp']);
                if ($content === false) return ['ok' => false, 'error' => 'Impossible de lire la pièce jointe: ' . $att['name']];
                $payload['attachments'][] = ['filename' => $att['name'], 'content' => base64_encode($content)];
            }
        }
        if (!function_exists('curl_init')) return ['ok' => false, 'error' => 'Extension PHP cURL non activée. Active curl dans php.ini puis redémarre Apache.'];
        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30
        ]);
        $response = curl_exec($ch); $error = curl_error($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        if ($error) return ['ok' => false, 'error' => $error];
        if ($status < 200 || $status >= 300) return ['ok' => false, 'error' => 'Resend error HTTP ' . $status . ': ' . $response];
        return ['ok' => true, 'error' => ''];
    }

    $smtpHost = trim((string)(getenv('GE_SMTP_HOST') ?: ($settings['smtp_host'] ?? '') ?: ($cfg['host'] ?? '')));
    if($smtpHost===''){
        return ['ok' => false, 'error' => 'Email config missing. Add Resend API key or SMTP host in the first setup / Settings.'];
    }
    return ge_send_smtp_email(
        $smtpHost,
        (int)(getenv('GE_SMTP_PORT') ?: ($settings['smtp_port'] ?? ($cfg['port'] ?? 587))),
        (string)(getenv('GE_SMTP_SECURE') ?: ($settings['smtp_secure'] ?? ($cfg['secure'] ?? 'tls'))),
        (string)(getenv('GE_SMTP_USERNAME') ?: ($settings['smtp_username'] ?? ($cfg['username'] ?? ''))),
        (string)(getenv('GE_SMTP_PASSWORD') ?: ($settings['smtp_password'] ?? ($cfg['password'] ?? ''))),
        $from,$fromName,$toList,$ccList,$subject,$message,$attachments
    );
}



function ge_stock_alert_cfg(){
    $cfg=app_config()['stock_alert'] ?? [];
    $email=trim((string)(getenv('GE_STOCK_ALERT_EMAIL') ?: getenv('GE_ALERT_EMAIL') ?: getenv('GE_ADMIN_EMAIL') ?: ($cfg['email'] ?? '')));
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        try{
            foreach(data_read('users', []) as $u){
                $role=strtolower((string)($u['role'] ?? ''));
                $ue=trim((string)($u['email'] ?? ''));
                if(filter_var($ue, FILTER_VALIDATE_EMAIL) && ($role==='admin' || $email==='')){ $email=$ue; break; }
            }
        }catch(Throwable $ignored){}
    }
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)) $email=trim((string)(app_config()['mail']['from_email'] ?? ''));
    return [
        'enabled'=>(bool)($cfg['enabled'] ?? true),
        'threshold'=>(float)(getenv('GE_STOCK_ALERT_THRESHOLD') ?: ($cfg['threshold'] ?? 4)),
        'email'=>$email,
    ];
}
function ge_product_current_stock($product){
    if(isset($product['physical_stock']) && is_numeric($product['physical_stock'])) return (float)$product['physical_stock'];
    if(isset($product['stock']) && is_numeric($product['stock'])) return (float)$product['stock'];
    if(isset($product['warehouse_stock']) && is_array($product['warehouse_stock'])) return (float)array_sum(array_map('floatval',$product['warehouse_stock']));
    return 0.0;
}
function ge_stock_alert_find_index($rows, $productId){
    foreach($rows as $i=>$r){ if((int)($r['product_id'] ?? 0)===(int)$productId) return $i; }
    return -1;
}
function ge_stock_alert_after_products_write($products){
    $cfg=ge_stock_alert_cfg();
    if(empty($cfg['enabled'])) return;
    $recipient=$cfg['email'];
    $threshold=(float)$cfg['threshold'];
    if(!filter_var($recipient, FILTER_VALIDATE_EMAIL)) return;

    $states=data_read('stock_alerts', []);
    $next=next_id($states);
    $toNotify=[];
    $now=date('Y-m-d H:i:s');

    foreach((array)$products as $p){
        if(!is_array($p)) continue;
        $pid=(int)($p['id'] ?? 0);
        if($pid<=0) continue;
        $qty=ge_product_current_stock($p);
        $idx=ge_stock_alert_find_index($states, $pid);
        if($idx<0){
            $states[]=[
                'id'=>$next++,
                'product_id'=>$pid,
                'product_ref'=>(string)($p['ref'] ?? ''),
                'product_label'=>(string)($p['label'] ?? $p['name'] ?? ''),
                'current_qty'=>$qty,
                'threshold'=>$threshold,
                'is_low'=>false,
                'notified_low'=>false,
                'last_alert_qty'=>null,
                'last_alert_at'=>'',
                'last_error'=>'',
                'updated_at'=>$now,
            ];
            $idx=count($states)-1;
        }
        $states[$idx]['product_ref']=(string)($p['ref'] ?? '');
        $states[$idx]['product_label']=(string)($p['label'] ?? $p['name'] ?? '');
        $states[$idx]['current_qty']=$qty;
        $states[$idx]['threshold']=$threshold;
        $states[$idx]['updated_at']=$now;

        if($qty <= $threshold){
            $sameQtyNotified = !empty($states[$idx]['notified_low']) && (string)($states[$idx]['last_alert_qty'] ?? '') === (string)$qty;
            if(!$sameQtyNotified){
                $toNotify[]=[
                    'state_index'=>$idx,
                    'id'=>$pid,
                    'ref'=>(string)($p['ref'] ?? ''),
                    'label'=>(string)($p['label'] ?? $p['name'] ?? 'Produit'),
                    'qty'=>$qty,
                    'threshold'=>$threshold,
                ];
            }
            $states[$idx]['is_low']=true;
        }else{
            $states[$idx]['is_low']=false;
            $states[$idx]['notified_low']=false;
            $states[$idx]['last_alert_qty']=null;
            $states[$idx]['last_error']='';
        }
    }

    if($toNotify){
        $lines=[];
        $lines[]="Bonjour,";
        $lines[]="";
        $lines[]="Alerte stock Global Energie: les produits suivants sont au seuil critique (4, 3, 2, 1 ou moins).";
        $lines[]="Seuil configuré: ".$threshold;
        $lines[]="Date: ".date('d/m/Y H:i');
        $lines[]="";
        foreach($toNotify as $item){
            $lines[]='- '.($item['ref'] ? $item['ref'].' | ' : '').$item['label'].' : stock actuel = '.$item['qty'].' / seuil = '.$item['threshold'];
        }
        $lines[]="";
        $lines[]="Merci de réapprovisionner ou vérifier le stock.";
        $res=send_real_email($recipient, 'Alerte stock Global Energie - produits <= '.$threshold, implode("\n", $lines));
        try{
            $mailRows=data_read('sent_emails', []);
            $mailRows[]=['id'=>next_id($mailRows),'type'=>'stock_alert','object_id'=>0,'to'=>$recipient,'subject'=>'Alerte stock Global Energie - produits <= '.$threshold,'message'=>implode("\n", $lines),'sent'=>!empty($res['ok'])?1:0,'error'=>$res['error'] ?? '','created_at'=>date('d/m/Y H:i')];
            data_write('sent_emails', $mailRows, false);
        }catch(Throwable $ignored){}
        foreach($toNotify as $item){
            $idx=(int)$item['state_index'];
            if(!isset($states[$idx])) continue;
            if(!empty($res['ok'])){
                $states[$idx]['notified_low']=true;
                $states[$idx]['last_alert_qty']=$item['qty'];
                $states[$idx]['last_alert_at']=$now;
                $states[$idx]['last_error']='';
            }else{
                $states[$idx]['notified_low']=false;
                $states[$idx]['last_error']=$res['error'] ?? 'Erreur envoi email';
            }
        }
    }

    data_write('stock_alerts', $states, false);
}


function base32_encode_no_padding($data){
    $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits=''; $out='';
    for($i=0;$i<strlen($data);$i++) $bits.=str_pad(decbin(ord($data[$i])),8,'0',STR_PAD_LEFT);
    for($i=0;$i<strlen($bits);$i+=5){
        $chunk=substr($bits,$i,5);
        if(strlen($chunk)<5) $chunk=str_pad($chunk,5,'0',STR_PAD_RIGHT);
        $out.=$alphabet[bindec($chunk)];
    }
    return $out;
}
function base32_decode_no_padding($b32){
    $alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32=strtoupper(preg_replace('/[^A-Z2-7]/','',(string)$b32));
    $bits='';
    for($i=0;$i<strlen($b32);$i++){
        $v=strpos($alphabet,$b32[$i]);
        if($v===false) continue;
        $bits.=str_pad(decbin($v),5,'0',STR_PAD_LEFT);
    }
    $out='';
    for($i=0;$i+8<=strlen($bits);$i+=8) $out.=chr(bindec(substr($bits,$i,8)));
    return $out;
}
function totp_generate_secret($bytes=20){ return base32_encode_no_padding(random_bytes($bytes)); }
function totp_code($secret, $time=null, $period=30, $digits=6){
    $time=$time ?? time();
    $counter=(int)floor($time/$period);
    $key=base32_decode_no_padding($secret);
    $bin=pack('N*', 0).pack('N*', $counter);
    $hash=hash_hmac('sha1', $bin, $key, true);
    $offset=ord(substr($hash,-1)) & 0x0F;
    $truncated=((ord($hash[$offset]) & 0x7F) << 24) | ((ord($hash[$offset+1]) & 0xFF) << 16) | ((ord($hash[$offset+2]) & 0xFF) << 8) | (ord($hash[$offset+3]) & 0xFF);
    return str_pad((string)($truncated % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
}
function totp_verify($secret, $code, $window=1){
    $code=preg_replace('/\D+/','',(string)$code);
    if(strlen($code)!==6 || !$secret) return false;
    for($i=-$window;$i<=$window;$i++) if(hash_equals(totp_code($secret, time()+($i*30)), $code)) return true;
    return false;
}
function totp_uri($secret, $account='admin@hakpoint.ma'){
    $issuer='hakpoint';
    $label=$issuer.':'.$account;
    return 'otpauth://totp/'.rawurlencode($label).'?secret='.rawurlencode($secret).'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
}


function ge_secure_save_upload($field, $subdir, $allowedExts=null, $maxMb=null, $prefix='file'){
    if(empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $cfg=app_config()['uploads'] ?? [];
    $file=$_FILES[$field];
    if(($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) throw new Exception('Erreur upload fichier');
    $ext=strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExts=$allowedExts ?: ($cfg['allowed_documents'] ?? ['pdf','jpg','jpeg','png','webp']);
    if(!in_array($ext, $allowedExts, true)) throw new Exception('Type de fichier non autorisé');
    $maxMb=$maxMb ?: (int)($cfg['max_document_mb'] ?? 15);
    if(($file['size'] ?? 0) > ($maxMb*1024*1024)) throw new Exception('Fichier trop grand (max '.$maxMb.' MB)');
    $tmp=(string)($file['tmp_name'] ?? '');
    if(!is_uploaded_file($tmp) && !file_exists($tmp)) throw new Exception('Fichier upload introuvable');
    if(in_array($ext, ['jpg','jpeg','png','webp','gif'], true) && !@getimagesize($tmp)) throw new Exception('Image invalide');
    $dir=__DIR__.'/../uploads/'.trim($subdir,'/');
    if(!is_dir($dir)) @mkdir($dir,0775,true);
    foreach(['index.html','.htaccess'] as $guard){
        $guardPath=$dir.'/'.$guard;
        if($guard==='index.html' && !is_file($guardPath)) @file_put_contents($guardPath, '');
        if($guard==='.htaccess' && !is_file($guardPath)) @file_put_contents($guardPath, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|cgi|pl|asp|aspx|jsp)$\">\nRequire all denied\n</FilesMatch>\n");
    }
    $name=preg_replace('/[^A-Za-z0-9_-]+/','_', $prefix).'_'.date('Ymd_His').'_'.bin2hex(random_bytes(6)).'.'.$ext;
    $dest=$dir.'/'.$name;
    if(!@move_uploaded_file($tmp,$dest)){
        if(!@rename($tmp,$dest)) throw new Exception('Impossible de sauvegarder le fichier');
    }
    @chmod($dest, 0644);
    return $name;
}

function ge_create_backup($note='manual'){
    $pdo=db(); db_install_enterprise_tables($pdo);
    $backupDir=ge_storage_dir('backups');
    $payload=['created_at'=>date('c'),'app'=>app_config()['app_name'] ?? 'GLOBAL ENERGIE EVENTS','collections'=>[],'audit_last'=>[]];
    foreach(ge_known_collections() as $collection){ $payload['collections'][$collection]=data_read($collection, []); }
    try{ $payload['audit_last']=audit_rows(1000); }catch(Throwable $e){}
    $filename='backup_'.date('Ymd_His').'_'.bin2hex(random_bytes(3)).'.json';
    $path=$backupDir.'/'.$filename;
    file_put_contents($path, json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
    $size=is_file($path) ? filesize($path) : 0;
    $u=function_exists('current_user') ? current_user() : null;
    $stmt=$pdo->prepare('INSERT INTO ge_backups(tenant_id,filename,note,size_bytes,created_by) VALUES(?,?,?,?,?)');
    $stmt->execute([ge_current_tenant_id(),$filename,(string)$note,(int)$size,(int)($u['id']??0) ?: null]);
    ge_cleanup_backups();
    audit_log('backup_created','Backup: '.$filename.' | '.$size.' bytes');
    return ['filename'=>$filename,'path'=>$path,'size_bytes'=>$size];
}
function ge_cleanup_backups(){
    $keep=(int)((app_config()['backup']['keep_last'] ?? 20)); if($keep<1) $keep=20;
    $rows=ge_list_backups(1000);
    $extra=array_slice($rows,$keep);
    $dir=ge_storage_dir('backups'); $pdo=db();
    foreach($extra as $r){
        $file=basename((string)$r['filename']); if($file && is_file($dir.'/'.$file)) @unlink($dir.'/'.$file);
        if(!empty($r['id'])){ $st=$pdo->prepare('DELETE FROM ge_backups WHERE id=? AND tenant_id=?'); $st->execute([(int)$r['id'], ge_current_tenant_id()]); }
    }
}
function ge_list_backups($limit=50){
    try{ $pdo=db(); db_install_enterprise_tables($pdo); return $pdo->query('SELECT * FROM ge_backups WHERE tenant_id='.(int)ge_current_tenant_id().' ORDER BY id DESC LIMIT '.(int)$limit)->fetchAll(); }catch(Throwable $e){ return []; }
}


function ge_restore_backup($filename){
    $file = basename((string)$filename);
    if($file === '' || !preg_match('/^backup_[A-Za-z0-9_\-]+\.json$/', $file)) {
        throw new Exception('Fichier backup invalide.');
    }
    $path = ge_storage_dir('backups').'/'.$file;
    if(!is_file($path)) throw new Exception('Backup introuvable: '.$file);
    $raw = file_get_contents($path);
    $payload = json_decode((string)$raw, true);
    if(!is_array($payload) || empty($payload['collections']) || !is_array($payload['collections'])) {
        throw new Exception('Backup invalide ou vide.');
    }
    // Safety: create a snapshot before restoring.
    try { ge_create_backup('auto-before-restore '.$file); } catch(Throwable $e) {}
    $allowed = array_flip(ge_known_collections());
    $restored = 0;
    foreach($payload['collections'] as $collection=>$rows){
        if(!isset($allowed[$collection]) || !is_array($rows)) continue;
        data_write($collection, array_values($rows), false);
        $restored++;
    }
    audit_log('backup_restored', 'Restored backup: '.$file.' | collections: '.$restored);
    return ['filename'=>$file,'collections'=>$restored];
}

function ge_header_stock_notifications(int $limit=6): array {
    $thresholdCfg = app_config()['stock_alert'] ?? [];
    $threshold = (float)(getenv('GE_STOCK_ALERT_THRESHOLD') ?: ($thresholdCfg['threshold'] ?? 4));
    if($threshold <= 0) $threshold = 4;
    $limit = max(1, min(50, $limit));
    try{
        $pdo = db();
        $table = ge_collection_table('products');
        db_install_collection_table($pdo, $table);
        $where = "tenant_id=? AND COALESCE(`physical_stock`,`stock`,0) <= CASE WHEN COALESCE(`alert_stock`,0) > 0 THEN GREATEST(COALESCE(`alert_stock`,0), ?) ELSE ? END";
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM '.ge_identifier($table).' WHERE '.$where);
        $countStmt->execute([ge_current_tenant_id(), $threshold, $threshold]);
        $count = (int)$countStmt->fetchColumn();
        $sql = 'SELECT `record_id` AS id, `ref`, `label`, COALESCE(`physical_stock`,`stock`,0) AS qty, COALESCE(`alert_stock`,0) AS product_alert FROM '.ge_identifier($table).' WHERE '.$where.' ORDER BY COALESCE(`physical_stock`,`stock`,0) ASC, `label` ASC LIMIT '.$limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute([ge_current_tenant_id(), $threshold, $threshold]);
        $items=[];
        foreach($stmt as $r){
            $pa=(float)($r['product_alert'] ?? 0);
            $items[]=[
                'id'=>(int)($r['id'] ?? 0),
                'ref'=>(string)($r['ref'] ?? ''),
                'label'=>(string)($r['label'] ?? 'Produit'),
                'qty'=>(float)($r['qty'] ?? 0),
                'alert'=>$pa > 0 ? max($pa, $threshold) : $threshold,
            ];
        }
        return ['count'=>$count, 'items'=>$items, 'threshold'=>$threshold];
    }catch(Throwable $e){
        // Safe fallback for old databases: only then use the legacy full product scan.
        $items=[]; $all=[];
        try{ $all=data_read('products', []); }catch(Throwable $ignored){ $all=[]; }
        foreach((array)$all as $hp){
            if(!is_array($hp)) continue;
            $qty = ge_product_current_stock($hp);
            $productAlert = (float)($hp['alert_stock'] ?? $hp['stock_alert'] ?? 0);
            $alert = $productAlert > 0 ? max($productAlert, $threshold) : $threshold;
            if($alert > 0 && $qty <= $alert){
                $items[]=['id'=>(int)($hp['id'] ?? 0),'ref'=>(string)($hp['ref'] ?? ''),'label'=>(string)($hp['label'] ?? $hp['name'] ?? 'Produit'),'qty'=>$qty,'alert'=>$alert];
            }
        }
        usort($items, fn($a,$b)=>($a['qty']==$b['qty']) ? strcmp((string)$a['label'], (string)$b['label']) : ($a['qty'] <=> $b['qty']));
        return ['count'=>count($items), 'items'=>array_slice($items,0,$limit), 'threshold'=>$threshold];
    }
}

function app_settings(){
    static $cachedSettings = null;
    if(is_array($cachedSettings)) return $cachedSettings;
    $s=data_read('settings', []);
    $row=$s[0] ?? [];
    $cachedSettings = array_merge([
        'language'=>'fr',
        // Legacy global 2FA keys kept only for backward compatibility.
        // Active 2FA settings are stored per user in the users collection.
        'twofa_enabled'=>false,
        'twofa_secret'=>'',
        'api_enabled'=>true,
        'api_key'=>'',
        'api_last_regenerated'=>'',
        'api_allowed_origins'=>'',
        'company_name'=>'',
        'company_subtitle'=>'',
        'company_address'=>'',
        'company_city'=>'',
        'company_country'=>'Maroc',
        'company_phone'=>'',
        'company_email'=>'',
        'company_rc'=>'',
        'company_patente'=>'',
        'company_if'=>'',
        'company_cnss'=>'',
        'company_ice'=>'',
        'company_tva'=>'',
        'company_logo'=>'',
        'company_pdf_logo'=>'',
        'invoice_default_terms'=>'',
        'pdf_sender_text'=>'',
        'pdf_footer_text'=>'',
        'pdf_show_logo'=>false,
        'pdf_show_sender_box'=>false,
        'pdf_show_payment_block'=>false,
        'pdf_show_footer'=>false,
        'pdf_show_signature'=>false,
        'smtp_host'=>'',
        'smtp_port'=>587,
        'smtp_secure'=>'tls',
        'smtp_username'=>'',
        'smtp_password'=>'',
        'smtp_from_email'=>'',
        'smtp_from_name'=>'',
        'smtp_default_letter'=>'',
        'smtp_resend_api_key'=>'',
        'onboarding_required'=>false,
        'onboarding_complete'=>false,
        'first_product_prompt_done'=>false
    ], $row);
    return $cachedSettings;
}
function app_setting($key,$default=null){ $s=app_settings(); return $s[$key] ?? $default; }
function save_app_settings($new){
    $s=app_settings(); $s=array_merge($s,(array)$new); $s['id']=1; data_write('settings', [$s]);
}
function ge_bool_setting($key, $default=false){
    $v=app_setting($key, $default);
    if(is_bool($v)) return $v;
    if(is_numeric($v)) return ((int)$v) === 1;
    return in_array(strtolower(trim((string)$v)), ['1','true','yes','on'], true);
}
function ge_should_show_onboarding(): bool {
    if(!function_exists('current_user') || !current_user()) return false;
    if(!is_admin()) return false;
    if(!empty($_GET['onboarding'])) return true;
    if(!empty($_SESSION['ge_onboarding_force'])) return true;
    $required=ge_bool_setting('onboarding_required', false);
    $complete=ge_bool_setting('onboarding_complete', false);
    return $required && !$complete;
}
function ge_should_show_first_product_prompt(): bool {
    if(!function_exists('current_user') || !current_user()) return false;
    if(!is_admin()) return false;
    if(!ge_bool_setting('onboarding_required', false)) return false;
    if(!ge_bool_setting('onboarding_complete', false)) return false;
    if(ge_bool_setting('first_product_prompt_done', false)) return false;
    try{ if(count(data_read('products', [])) > 0) return false; }catch(Throwable $e){}
    return !empty($_SESSION['ge_show_first_product_prompt']) || true;
}
function ge_generate_api_key($bytes=24){ return bin2hex(random_bytes($bytes)); }
function ge_api_key(){
    $key=trim((string)app_setting('api_key',''));
    if($key===''){
        $key=ge_generate_api_key();
        save_app_settings(['api_key'=>$key,'api_enabled'=>true,'api_last_regenerated'=>date('Y-m-d H:i:s')]);
    }
    return $key;
}
function ge_api_base_url(){
    $cfg=app_config();
    $base=trim((string)($cfg['base_url'] ?? ''));
    if($base!=='') return rtrim($base,'/');
    $https=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    $scheme=$https ? 'https' : 'http';
    $host=$_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir=rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    if($dir==='.' || $dir==='\\') $dir='';
    return $scheme.'://'.$host.$dir;
}
function ge_api_count($collection){ return count(data_read($collection, [])); }
function db_install_audit(PDO $pdo){
    $pdo->exec("CREATE TABLE IF NOT EXISTS procrm_audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        tenant_id INT NOT NULL DEFAULT 1,
        user_id INT NULL,
        username VARCHAR(120) NULL,
        action VARCHAR(120) NOT NULL,
        details TEXT NULL,
        ip VARCHAR(80) NULL,
        user_agent VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tenant(tenant_id), INDEX idx_created(created_at), INDEX idx_action(action)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function audit_log($action,$details=''){
    try{
        $pdo=db(); db_install_audit($pdo);
        $u=current_user();
        $stmt=$pdo->prepare('INSERT INTO procrm_audit_logs(tenant_id,user_id,username,action,details,ip,user_agent) VALUES(?,?,?,?,?,?,?)');
        $stmt->execute([ge_current_tenant_id(), (int)($u['id']??0) ?: null, $u['username']??($u['email']??null), (string)$action, (string)$details, $_SERVER['REMOTE_ADDR']??'', substr($_SERVER['HTTP_USER_AGENT']??'',0,250)]);
    }catch(Throwable $e){ /* never break the app because of logging */ }
}
function audit_rows($limit=5000){
    try{ $pdo=db(); db_install_audit($pdo); $stmt=$pdo->prepare('SELECT * FROM procrm_audit_logs WHERE tenant_id=? ORDER BY id DESC LIMIT '.(int)$limit); $stmt->execute([ge_current_tenant_id()]); return $stmt->fetchAll(); }catch(Throwable $e){ return []; }
}
function ge_lang(){ return app_setting('language','fr'); }
function ge_dict(){
    return [
      'en'=>[
        'Tableau de bord'=>'Dashboard','Tiers'=>'Third parties','Produits'=>'Products','Devis'=>'Quotes','Commandes'=>'Orders','Factures clients'=>'Customer invoices','Entrepôts'=>'Warehouses','Expéditions'=>'Shipments','Réceptions'=>'Receptions','Utilisateurs'=>'Users','Paramètres'=>'Settings','Liste'=>'List','Nouveau produit'=>'New product','Nouvel utilisateur'=>'New user','Liste des utilisateurs'=>'User list','Vue hiérarchique'=>'Hierarchy view','Nouvelle commande'=>'New order','Nouvelle facture'=>'New invoice','Nouvelle expédition'=>'New shipment','Nouvelle réception'=>'New reception','Statistiques'=>'Statistics','Déconnexion'=>'Logout','Nouvel entrepôt'=>'New warehouse','Mouvements'=>'Movements','Stock à date'=>'Stock at date','Changement de stock en'=>'Stock transfer','Nouveau devis'=>'New quote','Nouveau client'=>'New customer','Nouveau fournisseur'=>'New supplier','Nouveau prospect'=>'New prospect','Prospects'=>'Prospects','Clients'=>'Customers','Fournisseurs'=>'Suppliers','Brouillon'=>'Draft','Validé'=>'Validated','Validée'=>'Validated','Annulée'=>'Cancelled','Impayée'=>'Unpaid','Payée'=>'Paid','Abandonnée'=>'Abandoned','Règlements'=>'Payments','Rapports'=>'Reports','Liste des modèles'=>'Templates list','Rechercher produit, client, facture...'=>'Search product, customer, invoice...','Ouvrir le menu'=>'Open menu',
        'Paramètres'=>'Settings','Langue du site complet'=>'Full website language','Langue du site, mot de passe et double authentification Authenticator.'=>'Website language, password, profile picture and Authenticator 2FA.','Paramètres enregistrés avec succès.'=>'Settings saved successfully.','Choisir la langue'=>'Choose language','Enregistrer'=>'Save','Sécurité'=>'Security','Mot de passe actuel'=>'Current password','Nouveau mot de passe'=>'New password','Confirmer le nouveau mot de passe'=>'Confirm new password','Mot de passe actuel incorrect'=>'Current password is incorrect','Min. 8 caractères'=>'Min. 8 characters','Répéter le mot de passe'=>'Repeat password','Double authentification 2FA'=>'Two-factor authentication 2FA','Désactivé'=>'Disabled','Activé avec Google Authenticator'=>'Enabled with Google Authenticator','Scanner ce QR code avec Google Authenticator'=>'Scan this QR code with Google Authenticator','Nom affiché dans l\'application :'=>'Name shown in the app:','Compte :'=>'Account:','Code OTP de l\'application'=>'OTP code from the app','Pour activer la 2FA, entrez le code à 6 chiffres après le scan.'=>'To enable 2FA, enter the 6-digit code after scanning.','Enregistrer sécurité'=>'Save security','Photo de profil'=>'Profile picture','Changer votre photo de profil'=>'Change your profile picture','Importer une image'=>'Upload an image','Formats acceptés : JPG, PNG, WEBP. Taille max 2 MB.'=>'Accepted formats: JPG, PNG, WEBP. Max size 2 MB.','Enregistrer profil'=>'Save profile','Interface'=>'Interface','Profil'=>'Profile','Modifier votre photo et vos préférences.'=>'Change your photo and preferences.','Choisir un fichier'=>'Choose a file','Aucun fichier choisi'=>'No file chosen',
        'Modifier'=>'Edit','Supprimer'=>'Delete','Ajouter'=>'Add','Annuler'=>'Cancel','Enregistrer'=>'Save','Retour'=>'Back','Actions'=>'Actions','Nom'=>'Name','Email'=>'Email','Téléphone'=>'Phone','Ville'=>'City','Pays'=>'Country','Adresse'=>'Address','Statut'=>'Status','Date'=>'Date','Montant'=>'Amount','Référence'=>'Reference','Description'=>'Description','Prix'=>'Price','Stock'=>'Stock','Image'=>'Image','Fichiers joints'=>'Attachments','Notes'=>'Notes','Événements'=>'Events','Marges'=>'Margins','Objets référents'=>'Linked objects','Prix de vente'=>'Sale price','Prix d’achat'=>'Purchase price','Nouveau'=>'New','Créer'=>'Create','Mettre à jour'=>'Update','Afficher'=>'View','Rechercher'=>'Search','Filtrer'=>'Filter','Valider'=>'Validate','Envoyer email'=>'Send email','Message'=>'Message','Sujet'=>'Subject','Adressé à'=>'To','Copie à'=>'Cc','Pièces jointes'=>'Attachments','ENVOYER EMAIL'=>'SEND EMAIL','ANNULER'=>'CANCEL','Mot de passe oublié ?'=>'Forgot password?','Se connecter'=>'Log in','Entrez votre email'=>'Enter your email','Entrez votre mot de passe'=>'Enter your password'
      ],
      'ar'=>[
        'Tableau de bord'=>'لوحة التحكم','Tiers'=>'الأطراف','Produits'=>'المنتجات','Devis'=>'عروض الأسعار','Commandes'=>'الطلبات','Factures clients'=>'فواتير الزبناء','Entrepôts'=>'المخازن','Expéditions'=>'الإرساليات','Réceptions'=>'الاستلامات','Utilisateurs'=>'المستخدمون','Paramètres'=>'الإعدادات','Liste'=>'اللائحة','Nouveau produit'=>'منتج جديد','Nouvel utilisateur'=>'مستخدم جديد','Liste des utilisateurs'=>'لائحة المستخدمين','Vue hiérarchique'=>'عرض هرمي','Nouvelle commande'=>'طلب جديد','Nouvelle facture'=>'فاتورة جديدة','Nouvelle expédition'=>'إرسالية جديدة','Nouvelle réception'=>'استلام جديد','Statistiques'=>'الإحصائيات','Déconnexion'=>'تسجيل الخروج','Nouvel entrepôt'=>'مخزن جديد','Mouvements'=>'الحركات','Stock à date'=>'المخزون حسب التاريخ','Changement de stock en'=>'نقل المخزون','Nouveau devis'=>'عرض سعر جديد','Nouveau client'=>'زبون جديد','Nouveau fournisseur'=>'مورد جديد','Nouveau prospect'=>'عميل محتمل جديد','Prospects'=>'عملاء محتملون','Clients'=>'الزبناء','Fournisseurs'=>'الموردون','Brouillon'=>'مسودة','Validé'=>'مصادق عليه','Validée'=>'مصادق عليها','Annulée'=>'ملغاة','Impayée'=>'غير مدفوعة','Payée'=>'مدفوعة','Abandonnée'=>'متروكة','Règlements'=>'الدفعات','Rapports'=>'التقارير','Liste des modèles'=>'لائحة النماذج','Rechercher produit, client, facture...'=>'ابحث عن منتج أو زبون أو فاتورة...','Ouvrir le menu'=>'فتح القائمة',
        'Langue du site complet'=>'لغة الموقع كاملة','Langue du site, mot de passe et double authentification Authenticator.'=>'لغة الموقع، كلمة المرور، صورة الملف الشخصي والمصادقة الثنائية.','Paramètres enregistrés avec succès.'=>'تم حفظ الإعدادات بنجاح.','Choisir la langue'=>'اختر اللغة','Enregistrer'=>'حفظ','Sécurité'=>'الأمان','Mot de passe actuel'=>'كلمة المرور الحالية','Nouveau mot de passe'=>'كلمة المرور الجديدة','Confirmer le nouveau mot de passe'=>'تأكيد كلمة المرور الجديدة','Mot de passe actuel incorrect'=>'كلمة المرور الحالية غير صحيحة','Min. 8 caractères'=>'8 أحرف على الأقل','Répéter le mot de passe'=>'أعد كتابة كلمة المرور','Double authentification 2FA'=>'المصادقة الثنائية 2FA','Désactivé'=>'معطلة','Activé avec Google Authenticator'=>'مفعلة مع Google Authenticator','Scanner ce QR code avec Google Authenticator'=>'امسح رمز QR باستعمال Google Authenticator','Nom affiché dans l\'application :'=>'الاسم الظاهر في التطبيق:','Compte :'=>'الحساب:','Code OTP de l\'application'=>'رمز OTP من التطبيق','Pour activer la 2FA, entrez le code à 6 chiffres après le scan.'=>'لتفعيل 2FA أدخل الرمز المكون من 6 أرقام بعد المسح.','Enregistrer sécurité'=>'حفظ الأمان','Photo de profil'=>'صورة الملف الشخصي','Changer votre photo de profil'=>'تغيير صورة الملف الشخصي','Importer une image'=>'رفع صورة','Formats acceptés : JPG, PNG, WEBP. Taille max 2 MB.'=>'الصيغ المقبولة: JPG و PNG و WEBP. الحجم الأقصى 2MB.','Enregistrer profil'=>'حفظ الملف الشخصي','Interface'=>'الواجهة','Profil'=>'الملف الشخصي','Modifier votre photo et vos préférences.'=>'عدّل صورتك وتفضيلاتك.','Choisir un fichier'=>'اختيار ملف','Aucun fichier choisi'=>'لم يتم اختيار ملف',
        'Modifier'=>'تعديل','Supprimer'=>'حذف','Ajouter'=>'إضافة','Annuler'=>'إلغاء','Retour'=>'رجوع','Actions'=>'الإجراءات','Nom'=>'الاسم','Email'=>'البريد الإلكتروني','Téléphone'=>'الهاتف','Ville'=>'المدينة','Pays'=>'البلد','Adresse'=>'العنوان','Statut'=>'الحالة','Date'=>'التاريخ','Montant'=>'المبلغ','Référence'=>'المرجع','Description'=>'الوصف','Prix'=>'الثمن','Stock'=>'المخزون','Image'=>'الصورة','Fichiers joints'=>'المرفقات','Notes'=>'ملاحظات','Événements'=>'الأحداث','Marges'=>'الهامش','Objets référents'=>'العناصر المرتبطة','Prix de vente'=>'ثمن البيع','Prix d’achat'=>'ثمن الشراء','Nouveau'=>'جديد','Créer'=>'إنشاء','Mettre à jour'=>'تحديث','Afficher'=>'عرض','Rechercher'=>'بحث','Filtrer'=>'تصفية','Valider'=>'مصادقة','Envoyer email'=>'إرسال بريد','Message'=>'الرسالة','Sujet'=>'الموضوع','Adressé à'=>'إلى','Copie à'=>'نسخة إلى','Pièces jointes'=>'المرفقات','ENVOYER EMAIL'=>'إرسال البريد','ANNULER'=>'إلغاء','Mot de passe oublié ?'=>'نسيت كلمة المرور؟','Se connecter'=>'تسجيل الدخول','Entrez votre email'=>'أدخل بريدك الإلكتروني','Entrez votre mot de passe'=>'أدخل كلمة المرور'
      ]
    ];
}
function ge_t($fr){
    $lang=ge_lang(); if($lang==='fr') return $fr;
    $dict=ge_dict();
    return $dict[$lang][$fr] ?? $fr;
}
function ge_translate_html($html){
    $lang=ge_lang(); if($lang==='fr') return $html;
    $dict=ge_dict()[$lang] ?? [];
    if(!$dict) return $html;
    uksort($dict, fn($a,$b)=>strlen($b)<=>strlen($a));
    return strtr($html, $dict);
}
function ge_begin_translate(){ if(!defined('GE_TRANSLATE_BUFFER')){ define('GE_TRANSLATE_BUFFER', true); ob_start(); } }
function ge_end_translate(){ if(defined('GE_TRANSLATE_BUFFER') && ob_get_level()>0){ $html=ob_get_clean(); echo ge_translate_html($html); } }
function user_avatar_src($user=null){
    $user=$user ?: current_user();
    $pic=$user['profile_picture'] ?? '';
    return $pic ? 'uploads/profiles/'.rawurlencode($pic) : '';
}
function remove_current_user_profile_picture(){
    $users=data_read('users', []); $cu=current_user(); $old='';
    foreach($users as &$u){
        if((int)($u['id']??0)===(int)($cu['id']??0)){
            $old=(string)($u['profile_picture'] ?? '');
            $u['profile_picture']='';
            $_SESSION['user']=$u;
            break;
        }
    }
    unset($u);
    data_write('users',$users);
    if($old){
        $path=__DIR__.'/../uploads/profiles/'.basename($old);
        if(is_file($path)) @unlink($path);
    }
    audit_log('profile_picture_removed','Current user removed profile picture');
}

function save_current_user_profile_picture($field='profile_picture'){
    try{
        $name=ge_secure_save_upload($field, 'profiles', ['jpg','jpeg','png','webp'], 2, 'profile_'.(int)(current_user()['id']??0));
        if(!$name) return null;
        $users=data_read('users', []); $cu=current_user();
        foreach($users as &$u){ if((int)($u['id']??0)===(int)($cu['id']??0)){ $u['profile_picture']=$name; $_SESSION['user']=$u; break; } }
        unset($u); data_write('users',$users); audit_log('profile_picture_updated','Current user updated profile picture');
        return $name;
    }catch(Throwable $e){
        throw new Exception($e->getMessage());
    }
}



// v72: simple reusable list pagination helpers (20 rows/page default)
if(!function_exists('ge_list_limit')){
    function ge_list_limit(){ return 20; }
}
if(!function_exists('ge_list_page')){
    function ge_list_page($param='p'){
        $p=(int)($_GET[$param] ?? 1);
        return max(1,$p);
    }
}
if(!function_exists('ge_list_offset')){
    function ge_list_offset($param='p', $per=20){ return (ge_list_page($param)-1)*max(1,(int)$per); }
}
if(!function_exists('ge_list_sort_oldest_first')){
    function ge_list_sort_oldest_first($rows){
        usort($rows, function($a,$b){
            $ai=(int)($a['id'] ?? 0); $bi=(int)($b['id'] ?? 0);
            if($ai && $bi && $ai!==$bi) return $ai <=> $bi;
            return strcmp((string)($a['created_at'] ?? ''), (string)($b['created_at'] ?? ''));
        });
        return $rows;
    }
}
if(!function_exists('ge_list_slice')){
    function ge_list_slice($rows, $param='p', $per=20){
        $rows=ge_list_sort_oldest_first(array_values($rows));
        $total=count($rows); $page=ge_list_page($param); $pages=max(1,(int)ceil($total/max(1,$per)));
        if($page>$pages) $page=$pages;
        return [array_slice($rows, ($page-1)*$per, $per), $total, $page, $pages];
    }
}

if(!function_exists('ge_list_paginate_current')){
    function ge_list_paginate_current($rows, $param='p', $per=20){
        $rows=array_values((array)$rows);
        $per=max(1,(int)$per);
        $total=count($rows);
        $page=ge_list_page($param);
        $pages=max(1,(int)ceil($total/$per));
        if($page>$pages) $page=$pages;
        return [array_slice($rows, ($page-1)*$per, $per), $total, $page, $pages];
    }
}
if(!function_exists('ge_list_pager')){
    function ge_list_pager($total, $page, $pages, $param='p', $extra=[]){
        if($pages<=1) return '';
        $params=array_merge($_GET, $extra); unset($params[$param]);
        $base='index.php?'.http_build_query($params);
        $sep=(str_contains($base,'?') && substr($base,-1)!=='?') ? '&' : '';
        $prev=max(1,$page-1); $next=min($pages,$page+1);
        $html='<div class="ge-pager"><a class="btn small '.($page<=1?'disabled':'').'" href="'.e($base.$sep.$param.'='.$prev).'">‹</a>';
        $html.='<span>'.e($page).' / '.e($pages).' · '.e($total).' lignes</span>';
        $html.='<a class="btn small '.($page>=$pages?'disabled':'').'" href="'.e($base.$sep.$param.'='.$next).'">›</a></div>';
        return $html;
    }
}
