<?php
$id=(int)($_GET['id']??0); $tiers=tiers_all(); $t=find_row_by_id($tiers,$id); if(!$t) redirect_to('index.php?page=tiers');
$new=$t; $new['id']=next_id($tiers); $new['name']=($t['name']??'Tiers').' (copie)'; $new['code']='';
$defaults=tier_default_codes($new['type']??'client',$new['id']); $new['code_client']=$defaults['code_client']; $new['code_supplier']=$defaults['code_supplier']; $new['code']=$defaults['code_client']; $new['created_at']=date('Y-m-d H:i:s');
$tiers[]=$new; data_write('tiers',$tiers); sync_tiers_legacy($tiers); redirect_to('index.php?page=tiers_show&id='.$new['id']);
