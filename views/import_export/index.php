<?php
$title='Import / Export';
function ge_import_export_csv_safe($value){
    if(is_array($value) || is_object($value)) $value=json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $value=(string)$value;
    $value=str_replace(["
","","
"], ' ', $value);
    $value=trim($value);
    if($value !== '' && in_array(substr($value,0,1), ['=', '+', '-', '@'], true)) $value="'".$value;
    return $value;
}
$allowed=['products','tiers','quotes','orders','invoices','payments','supplier_payments','bank_accounts','documents','projects'];
if(isset($_GET['download'])){
    $collection=in_array($_GET['collection'] ?? '',$allowed,true) ? $_GET['collection'] : 'products';
    $rows=data_read($collection,[]);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$collection.'_'.date('Ymd_His').'.csv"');
    $out=fopen('php://output','w');
    $headers=[]; foreach($rows as $r){ if(is_array($r)) $headers=array_values(array_unique(array_merge($headers,array_keys($r)))); }
    if(!$headers) $headers=['id','ref','label','status'];
    fputcsv($out,$headers,';');
    foreach($rows as $r){ $line=[]; foreach($headers as $h){ $v=$r[$h]??''; $line[]=ge_import_export_csv_safe($v); } fputcsv($out,$line,';'); }
    fclose($out); exit;
}
if(($_SERVER['REQUEST_METHOD'] ?? 'GET')==='POST'){
    require_csrf();
    $collection=in_array($_POST['collection'] ?? '',$allowed,true) ? $_POST['collection'] : 'products';
    if(!empty($_FILES['csv']['tmp_name'])){
        $fh=fopen($_FILES['csv']['tmp_name'],'r');
        $header=fgetcsv($fh,0,';'); if(!$header) redirect_to('index.php?page=import_export&err=CSV invalide');
        $rows=data_read($collection,[]); $next=next_id($rows); $count=0;
        while(($line=fgetcsv($fh,0,';'))!==false){ $row=[]; foreach($header as $i=>$h){ $row[trim($h)]=$line[$i]??''; } if(empty($row['id'])) $row['id']=$next++; $row['created_at']=$row['created_at']??date('Y-m-d H:i:s'); $rows[]=$row; $count++; }
        fclose($fh); data_write($collection,$rows);
        $imports=data_read('imports',[]); $imports[]=['id'=>next_id($imports),'ref'=>'IMP'.date('ymd').'-'.str_pad((string)next_id($imports),5,'0',STR_PAD_LEFT),'collection'=>$collection,'filename'=>basename($_FILES['csv']['name']),'rows_imported'=>$count,'status'=>'Terminé','created_at'=>date('Y-m-d H:i:s')]; data_write('imports',$imports);
        redirect_to('index.php?page=import_export&ok='.$count);
    }
}
include __DIR__.'/../layouts/header.php';
?>
<div class="erp-page">
<?php if(isset($_GET['ok'])): ?><div class="email-status ok">Import terminé: <?=e($_GET['ok'])?> lignes.</div><?php endif; ?><?php if(isset($_GET['err'])): ?><div class="email-status err"><?=e($_GET['err'])?></div><?php endif; ?>
<div class="erp-head"><div><h2><i class="fa-solid fa-file-arrow-up"></i> Import / Export</h2><p>Importer ou exporter les données principales en CSV.</p></div></div>
<div class="erp-two"><section class="panel erp-card"><h3>Importer CSV</h3><form method="post" enctype="multipart/form-data" class="erp-form small"><?=csrf_field()?><label>Collection</label><select name="collection"><?php foreach($allowed as $c): ?><option value="<?=e($c)?>"><?=e($c)?></option><?php endforeach; ?></select><label>Fichier CSV ;</label><input type="file" name="csv" accept=".csv,text/csv" required><button class="btn primary">Importer</button></form></section><section class="panel erp-card"><h3>Exporter CSV</h3><form method="get" class="erp-form small"><input type="hidden" name="page" value="import_export"><input type="hidden" name="download" value="1"><label>Collection</label><select name="collection"><?php foreach($allowed as $c): ?><option value="<?=e($c)?>"><?=e($c)?></option><?php endforeach; ?></select><button class="btn primary">Télécharger CSV</button></form></section></div>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
