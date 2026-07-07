<?php
require_once __DIR__.'/../../app/signatures.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { redirect_to('index.php'); }
$token = (string)($_POST['token'] ?? '');
$row = $token !== '' ? ge_signature_find_by_token($token) : null;
if (!$row) { http_response_code(404); echo '<h2>Lien invalide</h2>'; return; }
if ((string)($row['status'] ?? '') === 'signed') { redirect_to('index.php?page=signature_open&token='.rawurlencode($token).'&done=1'); }
if (!empty($row['expires_at']) && strtotime((string)$row['expires_at']) < time()) { http_response_code(410); echo '<h2>Ce lien a expiré.</h2>'; return; }
if (empty($_POST['accepted'])) { http_response_code(400); echo '<h2>Confirmation obligatoire.</h2>'; return; }
$name = trim((string)($_POST['signer_name'] ?? ''));
$email = trim((string)($_POST['signer_email'] ?? ''));
$sig = (string)($_POST['signature_data'] ?? '');
if ($name === '') { http_response_code(400); echo '<h2>Nom obligatoire.</h2>'; return; }
if (!preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]+$#', $sig) || strlen($sig) > 750000) { http_response_code(400); echo '<h2>Signature invalide ou trop grande.</h2>'; return; }
$row['status'] = 'signed';
$row['signer_name'] = $name;
$row['signer_email'] = $email;
$row['signature_data'] = $sig;
$row['signed_at'] = date('Y-m-d H:i:s');
$row['signed_ip_hash'] = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? ''));
$row['signed_user_agent'] = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
$row['accepted_text'] = 'Je confirme avoir lu ce document PDF et je donne mon accord par signature électronique.';
$row['signed_pdf_file'] = ge_signature_signed_pdf_relative($row);
try { ge_signature_create_signed_pdf_file($row, __DIR__.'/../../'.$row['signed_pdf_file']); } catch(Throwable $ignored) {}
ge_signature_update_row($row);
try { audit_log('signature_signed', ($row['ref'] ?? '').' signé par '.$name.' | '.$email); } catch(Throwable $ignored) {}
redirect_to('index.php?page=signature_open&token='.rawurlencode($token).'&done=1');
?>
