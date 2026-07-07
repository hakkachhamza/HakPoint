<?php
$title='Documents';
$targetOptions=ge_erp_document_target_options();
if(($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST'){
    require_csrf();
    $action=$_POST['action'] ?? 'upload';
    $docs=data_read('documents',[]);
    if($action==='delete'){
        $id=(int)($_POST['id'] ?? 0);
        foreach($docs as $d){ if((int)($d['id']??0)===$id) ge_unlink_document_file($d); }
        $docs=array_values(array_filter($docs, fn($d)=>(int)($d['id']??0)!==$id));
        data_write('documents',$docs,false); redirect_to('index.php?page=documents&deleted=1');
    }
    if(!empty($_FILES['file']['name'])){
        $max=15*1024*1024; $allowed=['pdf','png','jpg','jpeg','webp','doc','docx','xls','xlsx','csv','txt'];
        if(($_FILES['file']['size'] ?? 0)>$max) redirect_to('index.php?page=documents&err='.urlencode('Fichier trop grand. Max 15 MB'));
        $ext=strtolower(pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION));
        if(!in_array($ext,$allowed,true)) redirect_to('index.php?page=documents&err='.urlencode('Type fichier non autorisé'));
        $safe=preg_replace('/[^A-Za-z0-9._-]+/','_',basename($_FILES['file']['name']));
        $filename=date('Ymd_His').'_'.($safe ?: 'document.'.$ext);
        $dir=__DIR__.'/../../uploads/documents'; if(!is_dir($dir)) mkdir($dir,0775,true);
        $path=$dir.'/'.$filename;
        if(!move_uploaded_file($_FILES['file']['tmp_name'],$path)) redirect_to('index.php?page=documents&err='.urlencode('Upload impossible'));
        $target=trim((string)($_POST['target'] ?? '')); $objectType=trim($_POST['object_type']??''); $objectId=(int)($_POST['object_id']??0);
        if($target!=='' && str_contains($target,':')){ [$objectType,$objectIdRaw]=explode(':',$target,2); $objectId=(int)$objectIdRaw; }
        $objectRef=trim($_POST['object_ref']??'');
        if($objectRef==='' && isset($targetOptions[$objectType.':'.$objectId])) $objectRef=$targetOptions[$objectType.':'.$objectId];
        $id=next_id($docs);
        $docs[]=['id'=>$id,'ref'=>'DOC'.date('ymd').'-'.str_pad((string)$id,5,'0',STR_PAD_LEFT),'object_type'=>$objectType,'object_id'=>$objectId,'object_ref'=>$objectRef,'title'=>trim($_POST['title']??'') ?: $safe,'filename'=>$filename,'mime_type'=>$_FILES['file']['type']??'','size_bytes'=>filesize($path),'url'=>'uploads/documents/'.$filename,'path'=>$path,'status'=>'Actif','note'=>trim($_POST['note']??''),'created_at'=>date('Y-m-d H:i:s')];
        data_write('documents',$docs,false); redirect_to('index.php?page=documents&ok=1');
    }
}
$docs=data_read('documents',[]);
$filterType=trim((string)($_GET['type'] ?? ''));
if($filterType!=='') $docs=array_values(array_filter($docs, fn($d)=>(string)($d['object_type']??'')===$filterType));
$docs=array_reverse($docs);
[$docs,$docsTotal,$docsPage,$docsPages]=ge_list_paginate_current($docs);
include __DIR__.'/../layouts/header.php';
?>
<div class="erp-page documents-page">
<?php if(isset($_GET['ok'])): ?><div class="email-status ok">Document ajouté et relié à son objet.</div><?php endif; ?><?php if(isset($_GET['deleted'])): ?><div class="email-status ok">Document supprimé.</div><?php endif; ?><?php if(isset($_GET['err'])): ?><div class="email-status err"><?=e($_GET['err'])?></div><?php endif; ?>
<div class="erp-head"><div><h2><i class="fa-solid fa-folder-open"></i> Documents</h2><p>GED simple: fichiers reliés aux clients, produits, factures, achats, avoirs et projets.</p></div></div>
<section class="panel erp-card"><h3>Ajouter document</h3><form method="post" enctype="multipart/form-data" class="erp-form compact-grid"><?=csrf_field()?><input type="hidden" name="action" value="upload"><label>Titre</label><input name="title"><label>Objet lié</label><select name="target" class="smart-select"><option value="">Aucun / manuel</option><?php foreach($targetOptions as $v=>$l): ?><option value="<?=e($v)?>"><?=e($l)?></option><?php endforeach; ?></select><label>Type manuel</label><select name="object_type" class="smart-select"><option value="">Auto depuis objet lié</option><?php foreach(ge_erp_object_type_options() as $v=>$l): ?><option value="<?=e($v)?>"><?=e($l)?></option><?php endforeach; ?></select><label>ID manuel</label><input type="number" name="object_id"><label>Réf / libellé manuel</label><input name="object_ref"><label>Fichier</label><input type="file" name="file" required><label>Note</label><textarea name="note"></textarea><div class="erp-actions"><button class="btn primary"><i class="fa-solid fa-upload"></i> Uploader</button></div></form></section>
<section class="panel erp-card"><div class="excel-panel-head"><div><b>Liste documents</b><span>20 documents par page, avec aperçu/téléchargement et lien vers l’objet.</span></div><form method="get" class="inline-filter"><input type="hidden" name="page" value="documents"><select name="type" onchange="this.form.submit()"><option value="">Tous les types</option><?php foreach(ge_erp_object_type_options() as $v=>$l): ?><option value="<?=e($v)?>" <?=$filterType===$v?'selected':''?>><?=e($l)?></option><?php endforeach; ?></select></form></div><div class="table-wrap"><table class="clean-table erp-table strong-lines"><thead><tr><th>Réf.</th><th>Titre</th><th>Objet</th><th>Fichier</th><th>Taille</th><th>Date</th><th>Actions</th></tr></thead><tbody><?php foreach($docs as $d): $url=e($d['url']??'#'); $otype=(string)($d['object_type']??''); $oid=(int)($d['object_id']??0); ?><tr><td><?=e($d['ref']??'')?></td><td><b><?=e($d['title']??'')?></b><small><?=e($d['note']??'')?></small></td><td><?php if($otype && $oid): ?><a class="mini-action" href="<?=e(ge_erp_object_url($otype,$oid))?>"><?=e($d['object_ref'] ?: ($otype.' #'.$oid))?></a><?php else: ?><span class="muted">Non lié</span><?php endif; ?></td><td><a href="<?=$url?>" target="_blank"><?=e($d['filename']??'')?></a></td><td><?=round(((int)($d['size_bytes']??0))/1024)?> Ko</td><td><?=e(substr((string)($d['created_at']??''),0,16))?></td><td class="nowrap"><a class="mini-action" href="<?=$url?>" target="_blank"><i class="fa-solid fa-eye"></i> Voir</a> <a class="mini-action success" href="<?=$url?>" download><i class="fa-solid fa-download"></i> Télécharger</a> <form method="post" style="display:inline" onsubmit="return confirm('Supprimer document ?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)($d['id']??0)?>"><button class="mini-action danger">Supprimer</button></form></td></tr><?php endforeach; if(!$docsTotal): ?><tr><td colspan="7">Aucun document.</td></tr><?php endif; ?></tbody></table></div><?=ge_list_pager($docsTotal,$docsPage,$docsPages,'p',['page'=>'documents'] + ($filterType!=='' ? ['type'=>$filterType] : []))?></section>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
