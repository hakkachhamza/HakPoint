<?php
$title='Workflow statuts';
$pdo=db(); if(function_exists('ge_erp_ensure_tables')) ge_erp_ensure_tables($pdo);
if(($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST'){
    require_csrf();
    $types=['devis','commande','facture','achat','avoir'];
    foreach($types as $t){
        $amount=ge_decimal($_POST['rule_'.$t] ?? 0);
        $active=!empty($_POST['active_'.$t]) ? 1 : 0;
        $stmt=$pdo->prepare('INSERT INTO ge_approval_rules(tenant_id,object_type,min_amount,active) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE min_amount=VALUES(min_amount), active=VALUES(active)');
        $stmt->execute([ge_current_tenant_id(),$t,$amount,$active]);
    }
    audit_log('workflow_rules_updated','Approval thresholds updated');
    redirect_to('index.php?page=workflow&ok=1');
}
$rules=[];
try{ foreach($pdo->query('SELECT * FROM ge_approval_rules WHERE tenant_id='.(int)ge_current_tenant_id()) as $r){ $rules[$r['object_type']]=$r; } }catch(Throwable $e){}
$labels=['devis'=>'Devis','commande'=>'Commande client','facture'=>'Facture client','achat'=>'Achat fournisseur','avoir'=>'Avoir client'];
include __DIR__.'/../layouts/header.php';
?>
<div class="erp-page">
<?php if(isset($_GET['ok'])): ?><div class="email-status ok">Règles workflow enregistrées.</div><?php endif; ?>
<div class="erp-head"><div><h2><i class="fa-solid fa-route"></i> Workflow statuts</h2><p>Cycles de vie et règles de validation automatique. Si un document dépasse le montant configuré, une demande de validation est créée.</p></div></div>
<section class="panel erp-card"><h3>Règles de validation automatique</h3>
<form method="post" class="settings-form"><?=csrf_field()?>
  <div class="table-wrap"><table class="clean-table"><thead><tr><th>Document</th><th>Actif</th><th>Montant minimum validation</th></tr></thead><tbody>
  <?php foreach($labels as $key=>$label): $r=$rules[$key] ?? ['min_amount'=>ge_approval_min_amount($key),'active'=>1]; ?>
    <tr><td><?=e($label)?></td><td><input type="checkbox" name="active_<?=e($key)?>" value="1" <?=!empty($r['active'])?'checked':''?>></td><td><input name="rule_<?=e($key)?>" value="<?=e($r['min_amount'] ?? 0)?>" inputmode="decimal"></td></tr>
  <?php endforeach; ?>
  </tbody></table></div>
  <button class="btn primary" type="submit"><i class="fa-solid fa-save"></i> Enregistrer règles</button>
</form>
</section>
<section class="panel erp-card"><h3>Statuts standards</h3><div class="workflow-grid">
<?php foreach(ge_workflows() as $type=>$statuses): ?>
  <div class="workflow-card"><h3><?=e($type)?></h3><div class="workflow-steps"><?php foreach($statuses as $s): ?><span><?=e($s)?></span><?php endforeach; ?></div></div>
<?php endforeach; ?>
</div></section>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
