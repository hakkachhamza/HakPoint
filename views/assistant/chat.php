<?php
header('Content-Type: application/json; charset=utf-8');
try {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '{}', true);
    if(!is_array($json)) $json=[];
    $message = trim((string)($json['message'] ?? $_POST['message'] ?? ''));
    $res = ge_assistant_handle($message);
    ge_assistant_log($message, $res);
    echo json_encode($res, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'reply'=>'Assistant error: '.$e->getMessage(),'links'=>[]], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
