<?php require __DIR__.'/_helpers.php';
$id=(int)($_GET['id']??0); $tiers=tiers_all();
foreach($tiers as &$t){
    if((int)$t['id']===$id){
        $t=tier_collect_post($t,$id);
        $t=tier_handle_logo_upload($t);
        break;
    }
}
unset($t);
data_write('tiers',$tiers); sync_tiers_legacy($tiers);
redirect_to('index.php?page=tiers_show&id='.$id);
