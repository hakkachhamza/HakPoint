<?php require __DIR__.'/_helpers.php';
$tiers=tiers_all(); $id=next_id($tiers);
$row=['id'=>$id,'created_at'=>date('Y-m-d H:i:s')];
$row=tier_collect_post($row,$id);
$row=tier_handle_logo_upload($row);
$tiers[]=$row;
data_write('tiers',$tiers); sync_tiers_legacy($tiers);
redirect_to('index.php?page=tiers_show&id='.$id);
