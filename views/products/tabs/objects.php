<?php
$pid=(int)($product['id'] ?? 0);
$refs=[];
$collections=[
  ['quotes','Devis','quote_show','fa-file-pen'],
  ['orders','Commande','order_show','fa-file-lines'],
  ['invoices','Facture','invoice_show','fa-file-invoice-dollar'],
  ['expeditions','Expédition','expedition_show','fa-dolly'],
  ['receptions','Réception','reception_show','fa-cart-flatbed'],
];
foreach($collections as [$collection,$kind,$page,$icon]){
  foreach(data_read($collection,[]) as $doc){
    $found=false;
    foreach(($doc['lines'] ?? []) as $line){ if((int)($line['product_id'] ?? 0)===$pid){ $found=true; break; } }
    if(!$found && in_array($collection,['expeditions','receptions'],true)){
      foreach(data_read(rtrim($collection,'s').'_lines',[]) as $line){ if((int)($line[rtrim($collection,'s').'_id'] ?? 0)===(int)($doc['id']??0) && (int)($line['product_id']??0)===$pid){ $found=true; break; } }
    }
    if($found) $refs[]=['kind'=>$kind,'page'=>$page,'icon'=>$icon,'id'=>(int)($doc['id']??0),'ref'=>$doc['ref'] ?? ('#'.($doc['id']??'')),'status'=>$doc['status'] ?? '', 'amount'=>amount_from_row($doc)];
  }
}
?>
<div class="dol-section"><div class="dol-lines full"><div><span>Objets liés</span><b><?=count($refs)?> document(s)</b></div></div></div>
<table class="dol-table"><thead><tr><th>Type</th><th>Référence</th><th>État</th><th class="num">Montant HT</th></tr></thead><tbody>
<?php if(!$refs): ?><tr><td colspan="4" class="empty-row">Aucun objet référent trouvé pour ce produit.</td></tr><?php endif; ?>
<?php foreach($refs as $r): ?><tr><td><i class="fa-solid <?=e($r['icon'])?> text-green"></i> <?=e($r['kind'])?></td><td><a class="ref" href="index.php?page=<?=e($r['page'])?>&id=<?=(int)$r['id']?>"><?=e($r['ref'])?></a></td><td><?=e($r['status'])?></td><td class="num"><?=money($r['amount'])?></td></tr><?php endforeach; ?>
</tbody></table>
