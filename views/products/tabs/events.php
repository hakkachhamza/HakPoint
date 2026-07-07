<?php
$pid=(int)($product['id'] ?? 0); $events=[];
foreach(data_read('warehouse_movements',[]) as $m){ if((int)($m['product_id']??0)===$pid){ $events[]=['date'=>$m['date']??'','type'=>'Mouvement stock','label'=>($m['type']??'Mouvement').' - '.($m['qty']??''),'user'=>ge_record_author($m,'user')]; } }
if(!empty($product['created_at'])) $events[]=['date'=>$product['created_at'],'type'=>'Création produit','label'=>$product['ref']??'','user'=>ge_record_author($product,'created_by')];
usort($events, fn($a,$b)=>strcmp((string)$b['date'],(string)$a['date']));
?>
<table class="dol-table"><thead><tr><th>Date</th><th>Événement</th><th>Détail</th><th>Utilisateur</th></tr></thead><tbody><?php if(!$events): ?><tr><td colspan="4" class="empty-row">Aucun événement enregistré.</td></tr><?php endif; ?><?php foreach($events as $ev): ?><tr><td><?=e($ev['date'])?></td><td><?=e($ev['type'])?></td><td><?=e($ev['label'])?></td><td><?=e($ev['user'])?></td></tr><?php endforeach; ?></tbody></table>
