<?php
require_once __DIR__.'/../../app/signatures.php';
$token = (string)($_GET['token'] ?? ($_POST['token'] ?? ''));
$row = $token !== '' ? ge_signature_find_by_token($token) : null;
$err = '';
if (!$row) $err = 'Lien de signature introuvable ou invalide.';
$expired = false;
if ($row && !empty($row['expires_at']) && strtotime((string)$row['expires_at']) < time() && (string)($row['status'] ?? '') !== 'signed') {
    $expired = true;
    $err = 'Ce lien de signature a expiré.';
}
if ($row && !$err && (string)($row['status'] ?? '') !== 'signed' && empty($row['opened_at'])) {
    $row['opened_at'] = date('Y-m-d H:i:s');
    $row['open_user_agent'] = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
    ge_signature_update_row($row);
}
$done = isset($_GET['done']);
?>
<!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Signature électronique</title><link rel="icon" href="assets/images/global-energie-icon.png"><style>
:root{--main:#61c7e8;--dark:#0f172a;--muted:#64748b;--bg:#f5fbff;--border:#d9eef8;--ok:#16a34a;--danger:#dc2626}*{box-sizing:border-box}body{margin:0;font-family:Arial,Helvetica,sans-serif;background:linear-gradient(135deg,#eefaff,#ffffff);color:var(--dark)}.wrap{max-width:1180px;margin:0 auto;padding:20px}.top{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:18px}.brand{display:flex;align-items:center;gap:12px}.brand img{width:52px;height:52px;object-fit:contain}.brand b{display:block;font-size:18px}.brand small{color:var(--muted)}.badge{display:inline-flex;align-items:center;border-radius:999px;padding:8px 12px;background:#e7f8ff;border:1px solid var(--border);font-weight:700;color:#075985}.card{background:#fff;border:1px solid var(--border);border-radius:22px;box-shadow:0 14px 35px rgba(15,23,42,.08);overflow:hidden;margin-bottom:18px}.card-head{padding:18px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px}.card-head h1{font-size:22px;margin:0}.card-head p{margin:6px 0 0;color:var(--muted)}.pdf{height:72vh;min-height:540px;width:100%;border:0;background:#eef2f7}.sign-box{padding:20px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}label{font-weight:700;font-size:13px;color:#334155}input{width:100%;margin-top:6px;border:1px solid var(--border);border-radius:12px;padding:12px;font-size:15px}.sig-pad-wrap{margin-top:14px}.sig-pad{width:100%;height:180px;border:2px dashed #9bdcf1;border-radius:16px;background:#fff;touch-action:none}.check{display:flex;gap:10px;align-items:flex-start;margin:16px 0;color:#475569}.check input{width:auto;margin-top:3px}.actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap}.btn{border:0;border-radius:999px;padding:13px 20px;font-weight:800;cursor:pointer;background:var(--main);color:#042f3c;text-decoration:none;display:inline-flex;align-items:center;gap:8px}.btn.secondary{background:#f1f5f9;color:#334155}.btn.danger{background:#fee2e2;color:#991b1b}.msg{padding:18px 20px;border-radius:18px;margin-bottom:16px}.msg.ok{background:#ecfdf5;color:#166534;border:1px solid #bbf7d0}.msg.err{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}.signed-proof{padding:22px;text-align:center}.signed-proof img{max-width:340px;border:1px solid var(--border);border-radius:14px;background:#fff}.muted{color:var(--muted)}@media(max-width:760px){.top,.card-head{align-items:flex-start;flex-direction:column}.grid{grid-template-columns:1fr}.pdf{height:58vh;min-height:420px}.wrap{padding:12px}}
</style></head><body><div class="wrap">
  <div class="top"><div class="brand"><img src="assets/images/global-energie-icon.png" alt="Global Energie"><div><b>Global Energie</b><small>Signature électronique sécurisée</small></div></div><?php if($row): ?><span class="badge"><?=e($row['ref'] ?? 'Signature')?></span><?php endif; ?></div>
  <?php if($err): ?>
    <div class="msg err"><b>Impossible d’ouvrir la signature.</b><br><?=e($err)?></div>
  <?php else: ?>
    <?php if($done): ?><div class="msg ok"><b>Merci.</b> Votre signature a bien été confirmée et reçue dans le panel Global Energie.</div><?php endif; ?>
    <div class="card"><div class="card-head"><div><h1><?=e(($row['doc_type'] ?? 'Document').' — '.($row['pdf_name'] ?? 'PDF'))?></h1><p>Veuillez lire le PDF, puis signer dans la zone de confirmation en bas de page.</p></div><a class="btn secondary" target="_blank" href="index.php?page=signature_pdf&token=<?=e(urlencode($token))?>">Ouvrir le PDF</a></div><iframe class="pdf" src="index.php?page=signature_pdf&token=<?=e(urlencode($token))?>"></iframe></div>
    <?php if((string)($row['status'] ?? '') === 'signed'): ?>
      <div class="card signed-proof"><h2>Document déjà signé</h2><p class="muted">Signé par <?=e($row['signer_name'] ?? $row['client_name'] ?? 'client')?> le <?=e($row['signed_at'] ?? '')?>.</p><?php if(!empty($row['signature_data'])): ?><img src="<?=e($row['signature_data'])?>" alt="Signature"><?php endif; ?></div>
    <?php else: ?>
      <form class="card sign-box" method="post" action="index.php?page=signature_confirm" onsubmit="return prepareSignature()">
        <input type="hidden" name="token" value="<?=e($token)?>"><input type="hidden" name="signature_data" id="signatureData">
        <h2>Zone de signature</h2><p class="muted">Dessinez votre signature avec la souris ou le doigt, puis confirmez.</p>
        <div class="grid"><div><label>Nom complet / Société</label><input name="signer_name" id="signerName" value="<?=e($row['client_name'] ?? '')?>" required></div><div><label>Email</label><input type="email" name="signer_email" value="<?=e($row['client_email'] ?? '')?>"></div></div>
        <div class="sig-pad-wrap"><label>Signature</label><canvas class="sig-pad" id="sigPad"></canvas></div>
        <div class="actions" style="margin-top:10px"><button class="btn secondary" type="button" onclick="clearSig()">Effacer la signature</button></div>
        <label class="check"><input type="checkbox" name="accepted" value="1" required> <span>Je confirme avoir lu ce document PDF et je donne mon accord par signature électronique.</span></label>
        <div class="actions"><button class="btn" type="submit">Confirmer et signer</button></div>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>
<script>
const canvas=document.getElementById('sigPad'); let ctx=null, drawing=false, hasInk=false;
function resizeCanvas(){ if(!canvas) return; const data=canvas.toDataURL(); const rect=canvas.getBoundingClientRect(); canvas.width=Math.max(600, Math.floor(rect.width*2)); canvas.height=Math.max(240, Math.floor(rect.height*2)); ctx=canvas.getContext('2d'); ctx.lineWidth=4; ctx.lineCap='round'; ctx.strokeStyle='#0f172a'; const img=new Image(); img.onload=()=>{ if(hasInk) ctx.drawImage(img,0,0,canvas.width,canvas.height); }; img.src=data; }
function pos(e){ const r=canvas.getBoundingClientRect(); const t=e.touches?e.touches[0]:e; return {x:(t.clientX-r.left)*(canvas.width/r.width), y:(t.clientY-r.top)*(canvas.height/r.height)}; }
function start(e){ if(!canvas) return; drawing=true; const p=pos(e); ctx.beginPath(); ctx.moveTo(p.x,p.y); e.preventDefault(); }
function move(e){ if(!drawing) return; const p=pos(e); ctx.lineTo(p.x,p.y); ctx.stroke(); hasInk=true; e.preventDefault(); }
function stop(){ drawing=false; }
function clearSig(){ if(ctx){ ctx.clearRect(0,0,canvas.width,canvas.height); hasInk=false; } }
function prepareSignature(){ const name=document.getElementById('signerName'); if(!name || !name.value.trim()){ alert('Veuillez saisir votre nom.'); return false; } if(!hasInk){ alert('Veuillez dessiner votre signature.'); return false; } document.getElementById('signatureData').value=canvas.toDataURL('image/png'); return true; }
if(canvas){ resizeCanvas(); window.addEventListener('resize', resizeCanvas); canvas.addEventListener('mousedown',start); canvas.addEventListener('mousemove',move); window.addEventListener('mouseup',stop); canvas.addEventListener('touchstart',start,{passive:false}); canvas.addEventListener('touchmove',move,{passive:false}); canvas.addEventListener('touchend',stop); }
</script></body></html>
