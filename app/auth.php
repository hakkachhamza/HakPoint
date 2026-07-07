<?php
if(function_exists('ge_send_security_headers')) ge_send_security_headers();
if(session_status() !== PHP_SESSION_ACTIVE){
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443) || (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    $cfg = function_exists('app_config') ? (app_config()['security'] ?? []) : [];
    session_set_cookie_params([
        'lifetime'=>0,
        'path'=>'/',
        'domain'=>'',
        'secure'=>$secure,
        'httponly'=>true,
        'samesite'=>$cfg['session_samesite'] ?? 'Lax'
    ]);
    session_start();
}


function csrf_token(){
    if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function csrf_field(){ return '<input type="hidden" name="csrf_token" value="'.e(csrf_token()).'">'; }
function csrf_query(){ return 'csrf='.rawurlencode(csrf_token()); }
// Security: CSRF tokens must not be placed in URLs. This helper is kept for old templates.
function csrf_url($url){ return $url; }
function csrf_safe_current_uri(){
    $uri = (string)($_SERVER['REQUEST_URI'] ?? 'index.php');
    $parts = parse_url($uri);
    $path = $parts['path'] ?? 'index.php';
    $query = [];
    if(!empty($parts['query'])) parse_str($parts['query'], $query);
    unset($query['csrf'], $query['csrf_token']);
    $qs = http_build_query($query);
    return $path.($qs !== '' ? '?'.$qs : '');
}
function csrf_confirmation_page(){
    http_response_code(200);
    $action = csrf_safe_current_uri();
    $back = (string)($_SERVER['HTTP_REFERER'] ?? 'index.php?page=dashboard');
    if(function_exists('current_user') && current_user()){
        $title = 'Confirmer l’action';
        include __DIR__.'/../views/layouts/header.php';
        echo '<div class="panel" style="max-width:720px;margin:24px auto"><h2><i class="fa-solid fa-shield-halved"></i> Confirmer cette action</h2><p>Pour votre sécurité, cette action ne peut pas être exécutée par un simple lien. Cliquez sur confirmer pour continuer.</p><form method="post" action="'.e($action).'">'.csrf_field().'<button class="btn-orange" type="submit">Confirmer</button> <a class="btn" href="'.e($back).'">Annuler</a></form></div>';
        include __DIR__.'/../views/layouts/footer.php';
    }else{
        echo '<h2>Confirmer cette action</h2><form method="post" action="'.e($action).'">'.csrf_field().'<button type="submit">Confirmer</button></form>';
    }
    exit;
}
function require_csrf(){
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if($method !== 'POST'){
        csrf_confirmation_page();
    }
    $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if(is_string($token) && $token !== '' && hash_equals(csrf_token(), $token)) return;
    http_response_code(403);
    echo '<h2>Action refusée</h2><p>Jeton de sécurité CSRF manquant ou invalide. Retournez à la page précédente et réessayez.</p>';
    exit;
}

function current_user(){ return $_SESSION['user'] ?? null; }
function password_needs_change($user=null){ $user=$user ?: current_user(); return !empty($user['force_password_change']) || !empty($user['password_must_change']); }
function is_admin($user=null){ $user = $user ?: current_user(); return ($user['role'] ?? '') === 'Administrateur' || ($user['username'] ?? '') === 'admin'; }
function require_login(){ if(empty($_SESSION['user'])) redirect_to('index.php?page=login'); }
function refresh_session_user(){
    if(empty($_SESSION['user']['id'])) return;
    $u = function_exists('ge_fast_user_by_id') ? ge_fast_user_by_id((int)$_SESSION['user']['id']) : null;
    if(is_array($u) && $u){
        $_SESSION['user'] = $u;
        if((int)($u['tenant_id'] ?? 0) > 0) $_SESSION['tenant_id']=(int)$u['tenant_id'];
        if(!empty($u['tenant_slug'])) $_SESSION['tenant_slug']=(string)$u['tenant_slug'];
        return;
    }
    $users = data_read('users', []);
    foreach($users as $row){
        if((int)($row['id'] ?? 0) === (int)$_SESSION['user']['id']){ $_SESSION['user'] = $row; return; }
    }
}
function has_permission($permission, $user=null){
    $user = $user ?: current_user();
    if(!$user) return false;
    if(is_admin($user)) return true;
    if($permission === '' || $permission === null) return true;
    $permissions = $user['permissions'] ?? [];
    return in_array($permission, $permissions, true);
}
function require_permission($permission){
    if(has_permission($permission)) return;
    http_response_code(403);
    $title = 'Accès refusé';
    include __DIR__.'/../views/layouts/header.php';
    echo '<div class="panel access-denied"><h2><i class="fa-solid fa-lock"></i> Accès refusé</h2><p>Vous n’avez pas la permission nécessaire pour accéder à cette page.</p><p><b>Permission requise :</b> '.e($permission).'</p><a class="btn-orange" href="index.php?page=dashboard">Retour tableau de bord</a></div>';
    include __DIR__.'/../views/layouts/footer.php';
    exit;
}

function ge_fast_user_by_id(int $id){
    if($id <= 0) return null;
    try{
        $pdo=db(); $table=ge_collection_table('users'); db_install_collection_table($pdo,$table);
        $stmt=$pdo->prepare('SELECT * FROM '.ge_identifier($table).' WHERE tenant_id=? AND record_id=? LIMIT 1');
        $stmt->execute([ge_current_tenant_id(), $id]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? ge_schema_db_row_to_app('users', $row) : null;
    }catch(Throwable $e){ return null; }
}
function ge_fast_user_by_login(string $login){
    $login=strtolower(trim($login));
    if($login==='') return null;
    try{
        $pdo=db(); $table=ge_collection_table('users'); db_install_collection_table($pdo,$table);
        $stmt=$pdo->prepare("SELECT * FROM ".ge_identifier($table)." WHERE tenant_id=? AND COALESCE(status,'Actif')<>'Désactivé' AND (email=? OR username=?) ORDER BY record_id ASC LIMIT 1");
        $stmt->execute([ge_current_tenant_id(), $login, $login]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? ge_schema_db_row_to_app('users', $row) : null;
    }catch(Throwable $e){ return null; }
}
function ge_fast_update_user_login_state(array $u): bool {
    $id=(int)($u['id'] ?? 0); if($id<=0) return false;
    try{
        $pdo=db(); $table=ge_collection_table('users'); db_install_collection_table($pdo,$table);
        $u=ge_sanitize_collection_row('users',$u);
        [$ref,$label,$status,$amount]=data_index_fields($u);
        $extra=function_exists('ge_schema_extract_extra') ? ge_schema_extract_extra('users',$u) : $u;
        $payload=function_exists('ge_schema_json') ? ge_schema_json($extra ?: ['_relational'=>true]) : data_encode_payload($extra ?: ['_relational'=>true]);
        $stmt=$pdo->prepare('UPDATE '.ge_identifier($table).' SET payload=?, extra_json=?, ref=?, label=?, status=?, amount=?, last_login=?, updated_at=CURRENT_TIMESTAMP WHERE tenant_id=? AND record_id=? LIMIT 1');
        $stmt->execute([$payload,$payload,$ref,$label,$status,$amount,(string)($u['last_login'] ?? ''),ge_current_tenant_id(),$id]);
        return true;
    }catch(Throwable $e){
        try{ audit_log('fast_user_update_error',$e->getMessage()); }catch(Throwable $ignored){}
        return false;
    }
}
function ge_login_attempt_row(string $bucket){
    try{
        $pdo=db(); $table=ge_collection_table('login_attempts'); db_install_collection_table($pdo,$table);
        $stmt=$pdo->prepare('SELECT * FROM '.ge_identifier($table).' WHERE tenant_id=? AND bucket=? LIMIT 1');
        $stmt->execute([ge_current_tenant_id(), $bucket]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? ge_schema_db_row_to_app('login_attempts',$row) : null;
    }catch(Throwable $e){ return null; }
}
function ge_login_attempt_next_id(PDO $pdo, string $table): int {
    try{ $stmt=$pdo->prepare('SELECT COALESCE(MAX(record_id),0)+1 FROM '.ge_identifier($table).' WHERE tenant_id=?'); $stmt->execute([ge_current_tenant_id()]); return max(1,(int)$stmt->fetchColumn()); }catch(Throwable $e){ return 1; }
}
function login_attempts_read(){
    try{
        $pdo=db(); $table=ge_collection_table('login_attempts'); db_install_collection_table($pdo,$table);
        $now=time();
        $stmt=$pdo->prepare('DELETE FROM '.ge_identifier($table).' WHERE tenant_id=? AND updated_at_ts<? AND locked_until<?');
        $stmt->execute([ge_current_tenant_id(), $now-86400, $now]);
        $stmt=$pdo->prepare('SELECT * FROM '.ge_identifier($table).' WHERE tenant_id=? ORDER BY updated_at_ts DESC LIMIT 200');
        $stmt->execute([ge_current_tenant_id()]);
        $rows=[]; foreach($stmt as $r){ $rows[]=ge_schema_db_row_to_app('login_attempts',$r); }
        return $rows;
    }catch(Throwable $e){ return []; }
}
function login_attempt_find(&$rows, $bucket){
    foreach($rows as $i=>$r){ if(($r['bucket'] ?? '') === $bucket) return $i; }
    return -1;
}
function login_attempt_save($bucket, $login, $ip, $attempt){
    try{
        $pdo=db(); $table=ge_collection_table('login_attempts'); db_install_collection_table($pdo,$table);
        $existing=ge_login_attempt_row($bucket);
        $id=(int)($existing['id'] ?? 0); if($id<=0) $id=ge_login_attempt_next_id($pdo,$table);
        $row=[
            'id'=>$id,
            'bucket'=>$bucket,
            'login_hash'=>hash('sha256', strtolower(trim((string)$login))),
            'ip_hash'=>hash('sha256', (string)$ip),
            'count'=>(int)($attempt['count'] ?? 0),
            'first'=>(int)($attempt['first'] ?? time()),
            'locked_until'=>(int)($attempt['locked_until'] ?? 0),
            'updated_at_ts'=>time(),
            'updated_at'=>date('Y-m-d H:i:s'),
            'created_at'=>$existing['created_at'] ?? date('Y-m-d H:i:s'),
        ];
        $payload=function_exists('ge_schema_json') ? ge_schema_json($row) : data_encode_payload($row);
        $stmt=$pdo->prepare('INSERT INTO '.ge_identifier($table).'(tenant_id,record_id,payload,extra_json,ref,label,status,amount,bucket,login_hash,ip_hash,`count`,`first`,locked_until,updated_at_ts,updated_at,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE payload=VALUES(payload), extra_json=VALUES(extra_json), ref=VALUES(ref), label=VALUES(label), bucket=VALUES(bucket), login_hash=VALUES(login_hash), ip_hash=VALUES(ip_hash), `count`=VALUES(`count`), `first`=VALUES(`first`), locked_until=VALUES(locked_until), updated_at_ts=VALUES(updated_at_ts), updated_at=VALUES(updated_at)');
        $stmt->execute([ge_current_tenant_id(),$id,$payload,$payload,$bucket,substr($login,0,190),null,null,$bucket,$row['login_hash'],$row['ip_hash'],$row['count'],$row['first'],$row['locked_until'],$row['updated_at_ts'],$row['updated_at'],$row['created_at']]);
    }catch(Throwable $e){
        $rows = login_attempts_read();
        $idx = login_attempt_find($rows, $bucket);
        $row = ['id' => $idx >= 0 ? (int)($rows[$idx]['id'] ?? ($idx+1)) : next_id($rows), 'bucket' => $bucket, 'login_hash' => hash('sha256', strtolower(trim((string)$login))), 'ip_hash' => hash('sha256', (string)$ip), 'count' => (int)($attempt['count'] ?? 0), 'first' => (int)($attempt['first'] ?? time()), 'locked_until' => (int)($attempt['locked_until'] ?? 0), 'updated_at_ts' => time(), 'updated_at' => date('Y-m-d H:i:s')];
        if($idx >= 0) $rows[$idx] = $row; else $rows[] = $row;
        data_write('login_attempts', $rows, false);
    }
}
function login_attempt_clear($bucket){
    try{
        $pdo=db(); $table=ge_collection_table('login_attempts'); db_install_collection_table($pdo,$table);
        $stmt=$pdo->prepare('DELETE FROM '.ge_identifier($table).' WHERE tenant_id=? AND bucket=?');
        $stmt->execute([ge_current_tenant_id(), $bucket]);
        return;
    }catch(Throwable $e){}
    $rows = login_attempts_read();
    $rows = array_values(array_filter($rows, fn($r)=>($r['bucket'] ?? '') !== $bucket));
    data_write('login_attempts', $rows, false);
}
function login_attempt($email,$password){
    if(function_exists('ge_prepare_login_tenant')) ge_prepare_login_tenant($_POST['tenant_code'] ?? null);
    $login = strtolower(trim((string)$email));
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'local';
    $bucket = hash('sha256', $ip.'|'.$login);
    $attempt = ge_login_attempt_row($bucket) ?: ['count'=>0,'first'=>time(),'locked_until'=>0];
    if((int)($attempt['locked_until'] ?? 0) > time()){
        audit_log('login_rate_limited','Temporarily blocked login: '.$login);
        return false;
    }
    if(time() - (int)($attempt['first'] ?? 0) > 900){ $attempt=['count'=>0,'first'=>time(),'locked_until'=>0]; }

    $candidate = ge_fast_user_by_login($login);
    $users = $candidate ? [$candidate] : data_read('users', []);
    foreach($users as $u){
        $uEmail = strtolower((string)($u['email'] ?? ''));
        $uLogin = strtolower((string)($u['username'] ?? ''));
        $active = ($u['status'] ?? 'Actif') !== 'Désactivé';
        if($active && ($uEmail === $login || $uLogin === $login) && password_verify((string)$password, (string)($u['password'] ?? ''))){
            login_attempt_clear($bucket);
            unset($u['plain_password'], $u['password_plain']);
            if((int)($u['tenant_id'] ?? 0) <= 0) $u['tenant_id'] = function_exists('ge_current_tenant_id') ? ge_current_tenant_id() : 1;
            if(empty($u['tenant_slug'])) $u['tenant_slug'] = $_SESSION['login_tenant_slug'] ?? (function_exists('ge_current_tenant_slug') ? ge_current_tenant_slug() : 'global-energie');
            $_SESSION['tenant_id']=(int)$u['tenant_id'];
            $_SESSION['tenant_slug']=(string)$u['tenant_slug'];
            if(!empty($u['twofa_enabled']) && !empty($u['twofa_secret'])){
                $_SESSION['pending_2fa_user']=$u;
                $_SESSION['pending_2fa_expires']=time()+600;
                audit_log('2fa_required','Authenticator 2FA required for '.$uLogin.' tenant='.$_SESSION['tenant_slug']);
                return '2fa';
            }
            $u['previous_login'] = $u['last_login'] ?? '';
            $u['last_login'] = date('d/m/Y H:i');
            ge_fast_update_user_login_state($u);
            if(session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
            $_SESSION['user']=$u;
            $_SESSION['tenant_id']=(int)($u['tenant_id'] ?? ge_current_tenant_id());
            $_SESSION['tenant_slug']=(string)($u['tenant_slug'] ?? ge_current_tenant_slug());
            $_SESSION['ge_show_stock_alert_once'] = 1;
            audit_log('login_success','User logged in: '.$uLogin.' tenant='.$_SESSION['tenant_slug']);
            return true;
        }
    }
    $attempt['count']=(int)($attempt['count'] ?? 0)+1;
    if($attempt['count'] >= 7){ $attempt['locked_until']=time()+900; }
    login_attempt_save($bucket, $login, $ip, $attempt);
    audit_log('login_failed','Failed login: '.$login);
    return false;
}
function verify_2fa_code($code){
    if(empty($_SESSION['pending_2fa_user']) || time()>(int)($_SESSION['pending_2fa_expires']??0)) return false;
    $pending=$_SESSION['pending_2fa_user'];
    // The pending user's own secret is the only valid 2FA secret.
    $secret=$pending['twofa_secret'] ?? '';
    if(!totp_verify($secret, $code)) return false;
    $_SESSION['tenant_id']=(int)($pending['tenant_id'] ?? (function_exists('ge_current_tenant_id') ? ge_current_tenant_id() : 1));
    $_SESSION['tenant_slug']=(string)($pending['tenant_slug'] ?? (function_exists('ge_current_tenant_slug') ? ge_current_tenant_slug() : 'global-energie'));
    $u=ge_fast_user_by_id((int)($pending['id'] ?? 0)) ?: $pending;
    $u['tenant_id']=$_SESSION['tenant_id'];
    $u['tenant_slug']=$_SESSION['tenant_slug'];
    $u['previous_login']=$u['last_login']??'';
    $u['last_login']=date('d/m/Y H:i');
    ge_fast_update_user_login_state($u);
    if(session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
    $_SESSION['user']=$u;
    unset($_SESSION['pending_2fa_user'],$_SESSION['pending_2fa_expires']);
    $_SESSION['ge_show_stock_alert_once'] = 1;
    audit_log('2fa_login_success','2FA login validated');
    return true;
}
function logout(){ session_destroy(); redirect_to('index.php?page=login'); }
