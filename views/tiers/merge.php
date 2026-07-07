<?php
require __DIR__.'/_helpers.php';
$tiers=tiers_all();
$id=(int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$source=find_row_by_id($tiers,$id);
if(!$source) redirect_to('index.php?page=tiers');
$statusMsg='';
function ge_merge_replace_in_collection($collection, $sourceId, $sourceName, $sourceRef, $target){
    $rows=data_read($collection, []); $changed=false;
    foreach($rows as &$r){
        $tid=(int)($target['id'] ?? 0); $tname=(string)($target['name'] ?? ''); $tref=(string)(tier_best_code($target));
        if(isset($r['client_id']) && (int)$r['client_id']===$sourceId){ $r['client_id']=$tid; $r['client']=$tname; $r['client_ref']=$tref; $changed=true; }
        if(isset($r['tier_id']) && (int)$r['tier_id']===$sourceId){ $r['tier_id']=$tid; $r['tier_name']=$tname; $r['tier_ref']=$tref; $changed=true; }
        if(($r['client'] ?? '')===$sourceName){ $r['client']=$tname; if(isset($r['client_ref'])) $r['client_ref']=$tref; $changed=true; }
        if(($r['tier_name'] ?? '')===$sourceName){ $r['tier_name']=$tname; if(isset($r['tier_ref'])) $r['tier_ref']=$tref; $changed=true; }
        if(($r['client_ref'] ?? '')===$sourceRef && isset($r['client_ref'])){ $r['client_ref']=$tref; $changed=true; }
        if(($r['tier_ref'] ?? '')===$sourceRef && isset($r['tier_ref'])){ $r['tier_ref']=$tref; $changed=true; }
    }
    unset($r);
    if($changed) data_write($collection,$rows);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
    $targetId=(int)($_POST['target_id'] ?? 0);
    if($targetId<=0 || $targetId===$id){
        $statusMsg='Choisissez un autre tiers de destination.';
    }else{
        $targetIndex=-1; $sourceIndex=-1;
        foreach($tiers as $k=>$t){ if((int)($t['id'] ?? 0)===$targetId) $targetIndex=$k; if((int)($t['id'] ?? 0)===$id) $sourceIndex=$k; }
        if($targetIndex<0 || $sourceIndex<0){
            $statusMsg='Tiers introuvable.';
        }else{
            $target=$tiers[$targetIndex];
            foreach($source as $k=>$v){
                if(in_array($k,['id','ref','code','code_client','code_supplier','created_at','updated_at','logo'], true)) continue;
                if((!isset($target[$k]) || $target[$k]==='' || $target[$k]===null || $target[$k]===false) && $v!=='' && $v!==null) $target[$k]=$v;
            }
            $target['is_client']=!empty($target['is_client']) || !empty($source['is_client']) || ($target['type']??'')==='client' || ($source['type']??'')==='client';
            $target['is_supplier']=!empty($target['is_supplier']) || !empty($source['is_supplier']) || ($target['type']??'')==='supplier' || ($source['type']??'')==='supplier';
            $target['is_prospect']=!empty($target['is_prospect']) || !empty($source['is_prospect']) || ($target['type']??'')==='prospect' || ($source['type']??'')==='prospect';
            if($target['is_client']) $target['type']='client'; elseif($target['is_supplier']) $target['type']='supplier'; else $target['type']='prospect';
            if(!isset($target['merged_from']) || !is_array($target['merged_from'])) $target['merged_from']=[];
            $target['merged_from'][]=['id'=>$id,'name'=>$source['name'] ?? '', 'date'=>date('d/m/Y H:i'), 'by'=>ge_current_author_name()];
            $target['updated_at']=date('Y-m-d H:i:s');
            $sourceName=(string)($source['name'] ?? ''); $sourceRef=(string)tier_best_code($source);
            $tiers[$targetIndex]=$target;
            unset($tiers[$sourceIndex]);
            $tiers=array_values($tiers);
            data_write('tiers',$tiers); sync_tiers_legacy($tiers);
            foreach(['quotes','orders','invoices','expeditions','receptions'] as $collection){ ge_merge_replace_in_collection($collection,$id,$sourceName,$sourceRef,$target); }
            audit_log('tiers_merge','Tiers '.$sourceName.' merged into '.($target['name'] ?? ''));
            redirect_to('index.php?page=tiers_show&id='.$targetId.'&merged=1');
        }
    }
}
$title='Fusionner tiers'; include __DIR__.'/../layouts/header.php';
?>
<div class="panel email-form">
  <h2>Fusionner le tiers : <?=e($source['name'] ?? '')?></h2>
  <?php if($statusMsg): ?><div class="email-status err"><?=e($statusMsg)?></div><?php endif; ?>
  <p class="muted">Choisissez le tiers de destination. Les documents liés seront déplacés vers ce tiers, puis ce tiers source sera supprimé de la liste.</p>
  <form method="post" action="index.php?page=tiers_merge"><?=csrf_field()?>
    <input type="hidden" name="id" value="<?=(int)$id?>">
    <div class="form-row"><label>Tiers de destination</label><select name="target_id" required><option value="">Sélectionner...</option><?php foreach($tiers as $t): if((int)($t['id'] ?? 0)===$id) continue; ?><option value="<?=(int)$t['id']?>"><?=e(($t['name'] ?? '').' — '.tier_best_code($t))?></option><?php endforeach; ?></select></div>
    <div class="form-actions"><button class="purple-btn" onclick="return confirm('Confirmer la fusion ?')">FUSIONNER</button><a class="purple-btn secondary" href="index.php?page=tiers_show&id=<?=(int)$id?>">ANNULER</a></div>
  </form>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
