<?php
// Fast database seeding. Runs once per schema version instead of reading full tables
// on every request, so login and dashboard stay quick when data grows.
function ge_initial_admin_password(){
    $cfg = app_config()['security'] ?? [];
    $envPassword = trim((string)($cfg['admin_password'] ?? ''));
    if($envPassword !== '') return [$envPassword, false];

    // Requested default login for easy first access.
    // Change GE_ADMIN_PASSWORD in .env / Railway Variables after first login for production.
    return ['admin1234', false];
}

function ge_default_admin_row($password){
    return [
        'id'=>1,
        'tenant_id'=>ge_default_tenant_id(),
        'tenant_slug'=>ge_default_tenant_slug(),
        'ref'=>'USR'.date('ym').'-00001',
        'name'=>'Admin',
        'firstname'=>'Super',
        'username'=>'admin',
        'email'=>getenv('GE_ADMIN_EMAIL') ?: 'admin@hakpoint.ma',
        'role'=>'Administrateur',
        'status'=>'Actif',
        'employee'=>true,
        'external_user'=>'Interne',
        'force_password_change'=>false,
        'twofa_enabled'=>false,
        'twofa_secret'=>'',
        'permissions'=>[],
        'password'=>password_hash($password, PASSWORD_DEFAULT),
        'created_at'=>date('Y-m-d H:i:s')
    ];
}

function ge_seed_collection_next_id(PDO $pdo, string $table): int {
    try{ $stmt=$pdo->prepare('SELECT COALESCE(MAX(record_id),0)+1 FROM '.ge_identifier($table).' WHERE tenant_id=?'); $stmt->execute([ge_current_tenant_id()]); return max(1,(int)$stmt->fetchColumn()); }catch(Throwable $e){ return 1; }
}

function ge_seed_user_by_username(PDO $pdo, string $username){
    try{
        $table=ge_collection_table('users'); db_install_collection_table($pdo,$table);
        $stmt=$pdo->prepare('SELECT * FROM '.ge_identifier($table).' WHERE tenant_id=? AND username=? LIMIT 1');
        $stmt->execute([ge_current_tenant_id(), strtolower(trim($username))]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? ge_schema_db_row_to_app('users',$row) : null;
    }catch(Throwable $e){ return null; }
}

function ge_seed_upsert_user(PDO $pdo, array $row): void {
    $table=ge_collection_table('users'); db_install_collection_table($pdo,$table);
    if((int)($row['id'] ?? 0) <= 0) $row['id']=ge_seed_collection_next_id($pdo,$table);
    $row=ge_sanitize_collection_row('users',$row);
    [$ref,$label,$status,$amount]=data_index_fields($row);
    $extra=function_exists('ge_schema_extract_extra') ? ge_schema_extract_extra('users',$row) : $row;
    $payload=function_exists('ge_schema_json') ? ge_schema_json($extra ?: ['_relational'=>true]) : data_encode_payload($extra ?: ['_relational'=>true]);
    $stmt=$pdo->prepare('INSERT INTO '.ge_identifier($table).'(`tenant_id`,`record_id`,`payload`,`extra_json`,`ref`,`label`,`status`,`amount`,`name`,`firstname`,`username`,`email`,`role`,`password`,`employee`,`external_user`,`force_password_change`,`twofa_enabled`,`twofa_secret`,`created_at`) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE `payload`=VALUES(`payload`), `extra_json`=VALUES(`extra_json`), `ref`=VALUES(`ref`), `label`=VALUES(`label`), `status`=VALUES(`status`), `name`=VALUES(`name`), `firstname`=VALUES(`firstname`), `username`=VALUES(`username`), `email`=VALUES(`email`), `role`=VALUES(`role`), `password`=VALUES(`password`), `employee`=VALUES(`employee`), `external_user`=VALUES(`external_user`), `force_password_change`=VALUES(`force_password_change`), `twofa_enabled`=VALUES(`twofa_enabled`), `twofa_secret`=VALUES(`twofa_secret`)');
    $stmt->execute([ge_current_tenant_id(),(int)$row['id'],$payload,$payload,$ref,$label,$status,$amount,$row['name'] ?? null,$row['firstname'] ?? null,$row['username'] ?? null,$row['email'] ?? null,$row['role'] ?? null,$row['password'] ?? null,!empty($row['employee'])?1:0,$row['external_user'] ?? null,!empty($row['force_password_change'])?1:0,!empty($row['twofa_enabled'])?1:0,$row['twofa_secret'] ?? '',date('Y-m-d H:i:s')]);
}

function ge_seed_warehouse_if_missing(PDO $pdo): void {
    try{
        $table=ge_collection_table('warehouses'); db_install_collection_table($pdo,$table);
        $stmt=$pdo->prepare('SELECT COUNT(*) FROM '.ge_identifier($table).' WHERE tenant_id=?');
        $stmt->execute([ge_current_tenant_id()]);
        if((int)$stmt->fetchColumn() > 0) return;
        $row=['id'=>1,'name'=>'Entrepôt principal','city'=>'Casablanca','country'=>'Maroc','status'=>'Ouvert','created_at'=>date('Y-m-d H:i:s')];
        [$ref,$label,$status,$amount]=data_index_fields($row);
        $extra=function_exists('ge_schema_extract_extra') ? ge_schema_extract_extra('warehouses',$row) : $row;
        $payload=function_exists('ge_schema_json') ? ge_schema_json($extra ?: ['_relational'=>true]) : data_encode_payload($extra ?: ['_relational'=>true]);
        $stmt=$pdo->prepare('INSERT INTO '.ge_identifier($table).'(`tenant_id`,`record_id`,`payload`,`extra_json`,`ref`,`label`,`status`,`amount`,`name`,`city`,`country`,`created_at`) VALUES(?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE `payload`=VALUES(`payload`), `extra_json`=VALUES(`extra_json`), `label`=VALUES(`label`), `status`=VALUES(`status`), `name`=VALUES(`name`), `city`=VALUES(`city`), `country`=VALUES(`country`)');
        $stmt->execute([ge_current_tenant_id(),1,$payload,$payload,$ref,$label,$status,$amount,$row['name'],$row['city'],$row['country'],date('Y-m-d H:i:s')]);
    }catch(Throwable $e){
        try{ audit_log('seed_warehouse_error',$e->getMessage()); }catch(Throwable $ignored){}
    }
}

$pdo = db();
$seedVersion='2026-06-26-fast-seed-v2';
$forceSeed = filter_var(getenv('GE_SYNC_SEED_ON_BOOT') ?: 'false', FILTER_VALIDATE_BOOLEAN);
if(!$forceSeed && ge_schema_meta_get($pdo, 'seed_version', '') === $seedVersion){
    return;
}

[$adminPassword, $generated] = ge_initial_admin_password();
$shouldSeedDefaultAdmin = !function_exists('ge_tenancy_enabled') || !ge_tenancy_enabled() || ge_current_tenant_id() === ge_default_tenant_id();

if($shouldSeedDefaultAdmin){
    $admin=ge_seed_user_by_username($pdo,'admin');
    if(!$admin){
        ge_seed_upsert_user($pdo, ge_default_admin_row($adminPassword));
        try{ audit_log('initial_admin_created', 'Default admin created. Login: admin / password from GE_ADMIN_PASSWORD or admin1234'); }catch(Throwable $e){}
    }elseif($forceSeed){
        $row=ge_default_admin_row($adminPassword);
        $row['id']=(int)($admin['id'] ?? 1);
        ge_seed_upsert_user($pdo,$row);
        try{ audit_log('admin_login_synced', 'Admin login synced. Username: admin'); }catch(Throwable $e){}
    }
    // Do not create default warehouse/demo operational data.
    // The dashboard must stay at 0 until the user creates real records.
}

ge_schema_meta_set($pdo, 'seed_version', $seedVersion);
