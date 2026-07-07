<?php
$files=[];
if(!empty($product['image'])) $files[]=['name'=>$product['image'],'url'=>product_image_src($product),'type'=>'Image produit'];
foreach((array)($product['attachments'] ?? []) as $f){ if(is_array($f)) $files[]=$f; elseif($f) $files[]=['name'=>$f,'url'=>'uploads/products/'.rawurlencode((string)$f),'type'=>'Fichier']; }
?>
<table class="dol-table"><thead><tr><th>Type</th><th>Fichier</th></tr></thead><tbody><?php if(!$files): ?><tr><td colspan="2" class="empty-row">Aucun fichier joint pour ce produit.</td></tr><?php endif; ?><?php foreach($files as $f): ?><tr><td><?=e($f['type']??'Fichier')?></td><td><a class="ref" href="<?=e($f['url']??'#')?>" target="_blank" rel="noopener"><?=e($f['name']??'Fichier')?></a></td></tr><?php endforeach; ?></tbody></table>
