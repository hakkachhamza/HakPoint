<?php require __DIR__.'/_helpers.php';
$ids=array_map('intval', $_POST['ids'] ?? []);
$action=$_POST['bulk_action'] ?? '';
$return=$_POST['return_page'] ?? 'tiers';
$tiers=tiers_all();
if(!$ids || !$action) redirect_to('index.php?page='.$return);
if($action==='email') redirect_to('index.php?page=tiers_email&id='.$ids[0]);
$out=[];
foreach($tiers as $t){
    if(in_array((int)$t['id'],$ids,true)){
        if($action==='delete') continue;
        if($action==='open') $t['status']='Ouvert';
        if($action==='closed') $t['status']='Clos';
        if($action==='assign') $t['owner']=ge_current_author_name();
        $t['updated_at']=date('Y-m-d H:i:s');
    }
    $out[]=$t;
}
data_write('tiers',$out); sync_tiers_legacy($out);
redirect_to('index.php?page='.$return);
