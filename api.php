<?php
define('GE_API_MODE', true);
require __DIR__.'/app/helpers.php';
require __DIR__.'/app/auth.php';
require __DIR__.'/app/seed.php';

header('Content-Type: application/json; charset=utf-8');
$allowedOrigins = trim((string)app_setting('api_allowed_origins',''));
$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
if($allowedOrigins === '*'){
    header('Access-Control-Allow-Origin: *');
}else{
    $allowed = array_map('trim', preg_split('/[,;\s]+/', $allowedOrigins));
    if($requestOrigin && in_array($requestOrigin, $allowed, true)){
        header('Access-Control-Allow-Origin: '.$requestOrigin);
        header('Vary: Origin');
    }
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: X-API-Key, Authorization, Content-Type');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if(($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS'){
    http_response_code(204);
    exit;
}

function api_log_request($statusCode=200){
    try{
        $pdo=db(); db_install_enterprise_tables($pdo);
        $key=api_request_key();
        $hash=$key!=='' ? hash('sha256',$key) : null;
        $stmt=$pdo->prepare('INSERT INTO ge_api_logs(action,api_key_hash,ip,method,status_code) VALUES(?,?,?,?,?)');
        $stmt->execute([$_GET['action'] ?? 'ping',$hash,$_SERVER['REMOTE_ADDR'] ?? '',$_SERVER['REQUEST_METHOD'] ?? 'GET',(int)$statusCode]);
    }catch(Throwable $e){}
}
function api_send($payload, $code=200){
    http_response_code($code);
    api_log_request($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    exit;
}
function api_json_body(){
    $raw=file_get_contents('php://input');
    if(!$raw) return [];
    $json=json_decode($raw,true);
    return is_array($json) ? $json : [];
}
function api_positive_int($key,$default,$max=200){
    $v=(int)($_GET[$key] ?? $default);
    if($v<1) $v=$default;
    return min($v,$max);
}

function api_request_key(){
    // Security: API keys must be sent via headers, never in URL query strings.
    $key = trim((string)($_SERVER['HTTP_X_API_KEY'] ?? ''));
    if($key !== '') return $key;
    $auth = trim((string)($_SERVER['HTTP_AUTHORIZATION'] ?? ''));
    if(preg_match('/Bearer\s+(.+)/i', $auth, $m)) return trim($m[1]);
    return '';
}

function api_require_key(){
    if(!app_setting('api_enabled', true)){
        api_send([
            'success'=>false,
            'connected'=>false,
            'error'=>'api_disabled',
            'message'=>'API is disabled from the GLOBAL ENERGIE back-office.',
            'timestamp'=>date('c')
        ], 403);
    }
    $expected = trim((string)app_setting('api_key',''));
    $provided = api_request_key();
    if($expected === '' || $provided === '' || !hash_equals($expected, $provided)){
        api_send([
            'success'=>false,
            'connected'=>false,
            'error'=>'invalid_api_key',
            'message'=>'Missing or invalid API key.',
            'timestamp'=>date('c')
        ], 401);
    }
}

function api_module_counts(){
    return [
        'products'=>ge_api_count('products'),
        'tiers'=>ge_api_count('tiers'),
        'clients'=>ge_api_count('clients'),
        'suppliers'=>ge_api_count('suppliers'),
        'quotes'=>ge_api_count('quotes'),
        'orders'=>ge_api_count('orders'),
        'invoices'=>ge_api_count('invoices'),
        'warehouses'=>ge_api_count('warehouses'),
        'expeditions'=>ge_api_count('expeditions'),
        'receptions'=>ge_api_count('receptions'),
        'users'=>ge_api_count('users')
    ];
}

function api_amount_sum($collection){
    $total = 0;
    foreach(data_read($collection, []) as $row){ $total += amount_from_row($row); }
    return round($total, 3);
}


function api_public_text($value, $max=420){
    $value = trim((string)$value);
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = trim(strip_tags($value));
    $value = preg_replace('/\s+/u', ' ', $value);
    if(function_exists('mb_substr') && mb_strlen($value, 'UTF-8') > $max){
        return rtrim(mb_substr($value, 0, $max, 'UTF-8')).'...';
    }
    if(strlen($value) > $max){
        return rtrim(substr($value, 0, $max)).'...';
    }
    return $value;
}

function api_public_brand($value){
    $raw = trim((string)$value);
    if($raw === '') return '';
    $key = strtolower(preg_replace('/[^a-z0-9]+/i', '', $raw));
    $map = [
        'carrier'=>'Carrier',
        'fitco'=>'Fitco',
        'ingelec'=>'Ingelec',
        'midea'=>'Midea'
    ];
    return $map[$key] ?? ucwords(strtolower($raw));
}

function api_public_panel_base(){
    // Prefer GE_BASE_URL when it exists. This prevents Railway/cPanel/proxy installs
    // from generating http:// image links that the HTTPS website blocks.
    $configured = trim((string)(app_config()['base_url'] ?? ''));
    if($configured !== ''){
        return rtrim($configured, '/');
    }

    $forwardedProto = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    $forwardedSsl = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')));
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $forwardedProto === 'https'
        || $forwardedSsl === 'on'
        || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;

    $host = (string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '');
    $hostOnly = strtolower(preg_replace('/:\d+$/', '', $host));
    $isLocal = in_array($hostOnly, ['localhost','127.0.0.1','0.0.0.0','::1'], true) || preg_match('/^(127\.|10\.|192\.168\.)/', $hostOnly);
    // Most hosted panels are HTTPS behind a proxy even when PHP sees HTTP.
    $scheme = ($https || !$isLocal) ? 'https' : 'http';

    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = rtrim(dirname($script), '/');
    if($basePath === '.' || $basePath === '/') $basePath = '';
    return $host !== '' ? ($scheme.'://'.$host.$basePath) : $basePath;
}

function api_public_product_image_filename($image){
    $image = trim((string)$image);
    if($image === '') return '';
    $path = parse_url($image, PHP_URL_PATH);
    if(is_string($path) && $path !== '') $image = $path;
    $filename = basename(str_replace('\\', '/', $image));
    return preg_match('/\.(?:jpe?g|png|webp|gif|svg)$/i', $filename) ? $filename : '';
}

function api_public_product_image_file($image){
    $filename = api_public_product_image_filename($image);
    if($filename === '') return '';
    $path = __DIR__.'/uploads/products/'.$filename;
    return is_file($path) ? $path : '';
}

function api_public_product_image($image){
    $image = trim((string)$image);
    $base = api_public_panel_base();
    if($image === '') return $base.'/assets/images/product-placeholder.svg';

    // Keep external Cloudinary/CDN images if the user saved a full URL.
    if(preg_match('/^https?:\/\//i', $image)){
        $file = api_public_product_image_file($image);
        if($file !== ''){
            $url = $base.'/uploads/products/'.rawurlencode(basename($file));
            return $url.'?v='.@filemtime($file);
        }
        return $image;
    }

    $file = api_public_product_image_file($image);
    if($file !== ''){
        return $base.'/uploads/products/'.rawurlencode(basename($file)).'?v='.@filemtime($file);
    }

    if(substr($image, 0, 1) === '/') return $image;
    $filename = api_public_product_image_filename($image);
    return $filename !== '' ? $base.'/uploads/products/'.rawurlencode($filename) : $base.'/assets/images/product-placeholder.svg';
}

function api_public_product_image_data($image){
    // Robust fallback for separated site/panel deployments: the API also returns
    // the real uploaded image as a safe data:image URI when the file exists.
    $file = api_public_product_image_file($image);
    if($file === '') return '';
    $maxBytes = (int)(getenv('GE_API_INLINE_IMAGE_MAX_BYTES') ?: 3145728); // 3 MB
    $size = @filesize($file);
    if(!$size || $size > $maxBytes) return '';
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $mimeMap = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','webp'=>'image/webp','gif'=>'image/gif'];
    if(!isset($mimeMap[$ext])) return '';
    $data = @file_get_contents($file);
    if($data === false || $data === '') return '';
    return 'data:'.$mimeMap[$ext].';base64,'.base64_encode($data);
}

function api_site_products_payload(){
    $rows = data_read('products', []);
    $products = [];
    foreach(array_reverse($rows) as $p){
        if(!is_array($p)) continue;
        $visible = strtolower(trim((string)($p['site_visible'] ?? 'Oui')));
        if(in_array($visible, ['non','no','0','false','hidden'], true)) continue;

        $id = (string)($p['id'] ?? $p['record_id'] ?? count($products) + 1);
        $title = api_public_text($p['label'] ?? $p['name'] ?? $p['title'] ?? 'Produit', 160);
        $description = api_public_text($p['description'] ?? $p['note'] ?? '', 620);
        $brand = api_public_brand($p['product_type'] ?? $p['brand'] ?? $p['marque'] ?? '');
        $category = api_public_text($p['category'] ?? $p['categorie'] ?? $p['type'] ?? 'Produit', 90);
        if($category === '') $category = 'Produit';

        $rawImage = (string)($p['image'] ?? '');
        $imageUrl = api_public_product_image($rawImage);
        $imageData = api_public_product_image_data($rawImage);
        $imageFilename = api_public_product_image_filename($rawImage);

        $products[] = [
            'id' => $id,
            'name' => $title,
            'title' => $title,
            'description' => $description,
            'brand' => $brand,
            'marque' => $brand,
            'category' => $category,
            // image keeps backward compatibility, image_url is explicit, image_data is the reliable fallback.
            'image' => $imageData !== '' ? $imageData : $imageUrl,
            'image_url' => $imageUrl,
            'image_data' => $imageData,
            'image_filename' => $imageFilename,
            'has_uploaded_image' => $imageFilename !== '',
            'updated_at' => (string)($p['updated_at'] ?? $p['created_at'] ?? '')
        ];
    }

    return [
        'success' => true,
        'source' => 'GLOBAL ENERGIE panel',
        'count' => count($products),
        'products' => $products,
        'timestamp' => date('c')
    ];
}

$action = strtolower(trim((string)($_GET['action'] ?? 'ping')));

if($action === 'site_products' || $action === 'public_products'){
    header('Access-Control-Allow-Origin: *');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    try{
        api_send(api_site_products_payload());
    }catch(Throwable $e){
        api_send([
            'success'=>false,
            'source'=>'GLOBAL ENERGIE panel',
            'products'=>[],
            'error'=>'site_products_exception',
            'message'=>app_debug() ? $e->getMessage() : 'Erreur interne API produits.',
            'timestamp'=>date('c')
        ], 500);
    }
}

api_require_key();

try{
    if($action === 'ping'){
        api_send([
            'success'=>true,
            'connected'=>true,
            'app'=>app_config()['app_name'] ?? 'GLOBAL ENERGIE EVENTS',
            'message'=>'API connected successfully.',
            'timestamp'=>date('c')
        ]);
    }

    if($action === 'health'){
        db()->query('SELECT 1');
        api_send([
            'success'=>true,
            'connected'=>true,
            'database'=>'connected',
            'storage'=>'dedicated_ge_tables',
            'php_version'=>PHP_VERSION,
            'timestamp'=>date('c')
        ]);
    }

    if($action === 'status'){
        api_send([
            'success'=>true,
            'connected'=>true,
            'app'=>app_config()['app_name'] ?? 'GLOBAL ENERGIE EVENTS',
            'api_enabled'=>(bool)app_setting('api_enabled', true),
            'database'=>'connected',
            'counts'=>api_module_counts(),
            'timestamp'=>date('c')
        ]);
    }

    if($action === 'summary'){
        api_send([
            'success'=>true,
            'connected'=>true,
            'counts'=>api_module_counts(),
            'totals'=>[
                'quotes_ht'=>api_amount_sum('quotes'),
                'orders_ht'=>api_amount_sum('orders'),
                'invoices_ht'=>api_amount_sum('invoices')
            ],
            'last_updated'=>date('c')
        ]);
    }

    if($action === 'products'){
        $page=api_positive_int('page',1,100000);
        $per=api_positive_int('per_page',50,200);
        $q=trim((string)($_GET['q'] ?? ''));
        $rows=data_read('products', []);
        if($q!==''){
            $rows=array_values(array_filter($rows, fn($p)=>stripos(($p['ref']??'').' '.($p['label']??'').' '.($p['product_type']??''),$q)!==false));
        }
        $total=count($rows); $slice=array_slice(array_reverse($rows), ($page-1)*$per, $per);
        api_send(['success'=>true,'page'=>$page,'per_page'=>$per,'total'=>$total,'products'=>$slice,'timestamp'=>date('c')]);
    }

    if($action === 'orders'){
        $page=api_positive_int('page',1,100000); $per=api_positive_int('per_page',50,200);
        $rows=data_read('orders', []); $total=count($rows);
        api_send(['success'=>true,'page'=>$page,'per_page'=>$per,'total'=>$total,'orders'=>array_slice(array_reverse($rows),($page-1)*$per,$per),'timestamp'=>date('c')]);
    }

    if($action === 'create_order'){
        if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') api_send(['success'=>false,'error'=>'method_not_allowed','message'=>'Use POST with JSON body.'],405);
        $body=api_json_body();
        $orders=data_read('orders', []);
        $id=next_id($orders);
        $amount=ge_decimal($body['amount_ht'] ?? $body['total_ht'] ?? 0);
        $row=[
            'id'=>$id,
            'ref'=>trim((string)($body['ref'] ?? '')) ?: 'SO'.date('ymd').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT),
            'client_id'=>(int)($body['client_id'] ?? 0),
            'tier_id'=>(int)($body['client_id'] ?? $body['tier_id'] ?? 0),
            'client'=>trim((string)($body['client'] ?? $body['client_name'] ?? $body['tier_name'] ?? 'Client API')),
            'tier_name'=>trim((string)($body['tier_name'] ?? $body['client_name'] ?? $body['client'] ?? 'Client API')),
            'date'=>date('Y-m-d'),
            'delivery_date'=>ge_date_or_null($body['delivery_date'] ?? '') ?: '',
            'status'=>'Brouillon',
            'amount_ht'=>$amount,
            'total_ht'=>$amount,
            'note_public'=>trim((string)($body['note'] ?? 'Commande créée via API')),
            'source'=>'api',
            'created_at'=>date('Y-m-d H:i:s')
        ];
        $orders[]=$row; data_write('orders',$orders);
        audit_log('api_order_created','Order '.$row['ref']);
        api_send(['success'=>true,'order'=>$row,'timestamp'=>date('c')],201);
    }


    $endpointCollections = [
        'tiers'=>'tiers', 'clients'=>'clients', 'customers'=>'clients', 'suppliers'=>'suppliers',
        'quotes'=>'quotes', 'invoices'=>'invoices', 'payments'=>'payments', 'supplier_payments'=>'supplier_payments',
        'bank_accounts'=>'bank_accounts', 'stock'=>'warehouse_movements', 'stock_lots'=>'stock_lots',
        'projects'=>'projects', 'agenda'=>'agenda_events', 'documents'=>'documents'
    ];
    if(isset($endpointCollections[$action])){
        $collection=$endpointCollections[$action];
        $page=api_positive_int('page',1,100000); $per=api_positive_int('per_page',50,200);
        $q=trim((string)($_GET['q'] ?? ''));
        $rows=data_read($collection, []);
        if($q!==''){
            $rows=array_values(array_filter($rows, function($row) use ($q){
                return stripos(json_encode($row,JSON_UNESCAPED_UNICODE),$q)!==false;
            }));
        }
        $total=count($rows);
        api_send(['success'=>true,'collection'=>$collection,'page'=>$page,'per_page'=>$per,'total'=>$total,'rows'=>array_slice(array_reverse($rows),($page-1)*$per,$per),'timestamp'=>date('c')]);
    }

    if(substr($action,0,7)==='create_'){
        if(($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') api_send(['success'=>false,'error'=>'method_not_allowed','message'=>'Use POST with JSON body.'],405);
        $target=substr($action,7);
        $allowedCreate=['tier'=>'tiers','client'=>'clients','supplier'=>'suppliers','product'=>'products','quote'=>'quotes','invoice'=>'invoices','payment'=>'payments','project'=>'projects','agenda'=>'agenda_events'];
        if(!isset($allowedCreate[$target])) api_send(['success'=>false,'error'=>'not_allowed','message'=>'Create action not allowed for this object.'],400);
        $collection=$allowedCreate[$target];
        $body=api_json_body();
        $rows=data_read($collection,[]); $id=next_id($rows);
        $body['id']=$id;
        $body['ref']=trim((string)($body['ref'] ?? '')) ?: strtoupper(substr($collection,0,3)).date('ymd').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT);
        $body['created_at']=$body['created_at'] ?? date('Y-m-d H:i:s');
        $body['updated_at']=date('Y-m-d H:i:s');
        $rows[]=$body;
        data_write($collection,$rows);
        audit_log('api_create_'.$collection,$body['ref'] ?? '#'.$id);
        api_send(['success'=>true,'collection'=>$collection,'row'=>$body,'timestamp'=>date('c')],201);
    }

    api_send([
        'success'=>false,
        'connected'=>false,
        'error'=>'unknown_action',
        'message'=>'Unknown action. Use ping, health, status, summary, products, orders, create_order, plus tiers/clients/suppliers/invoices/payments/bank_accounts/stock/projects/documents and create_* endpoints.',
        'timestamp'=>date('c')
    ], 400);
}catch(Throwable $e){
    api_send([
        'success'=>false,
        'connected'=>false,
        'error'=>'api_exception',
        'message'=>app_debug() ? $e->getMessage() : 'Erreur interne API.',
        'timestamp'=>date('c')
    ], 500);
}
