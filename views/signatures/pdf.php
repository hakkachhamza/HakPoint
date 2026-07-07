<?php
require_once __DIR__.'/../../app/signatures.php';
$token = (string)($_GET['token'] ?? '');
$row = $token !== '' ? ge_signature_find_by_token($token) : null;
$real = $row ? ge_signature_signed_pdf_real($row) : null;
if (!$row || !$real) { http_response_code(404); echo 'PDF introuvable'; return; }
if (!empty($row['expires_at']) && strtotime((string)$row['expires_at']) < time() && (string)($row['status'] ?? '') !== 'signed') { http_response_code(410); echo 'Lien expiré'; return; }
if(headers_sent()) exit;
while(ob_get_level()>0){ @ob_end_clean(); }
header('Content-Type: application/pdf');
header('Content-Length: '.filesize($real));
header('Content-Disposition: inline; filename="'.str_replace(chr(34),'', basename($real)).'"');
header('X-Content-Type-Options: nosniff');
readfile($real);
exit;
?>
