<?php
$q=trim((string)($_GET['q'] ?? ''));
$title='Recherche';
include __DIR__.'/layouts/header.php';
function ge_search_norm($s){ return mb_strtolower((string)$s); }
function ge_contains($hay,$q){ return $q!=='' && mb_stripos((string)$hay,$q)!==false; }
$groups=[];
if($q!==''){
    $collections=[
        'Produits'=>['collection'=>'products','url'=>'product_show','fields'=>['ref','label','description','brand','category'],'icon'=>'fa-boxes-stacked'],
        'Tiers'=>['collection'=>'tiers','url'=>'tiers_show','fields'=>['name','code','code_client','code_supplier','email','phone','city'],'icon'=>'fa-city'],
        'Devis'=>['collection'=>'quotes','url'=>'quote_show','fields'=>['ref','client','client_ref','status'],'icon'=>'fa-file-pen'],
        'Commandes'=>['collection'=>'orders','url'=>'order_show','fields'=>['ref','client','client_ref','status'],'icon'=>'fa-file-invoice'],
        'Factures'=>['collection'=>'invoices','url'=>'invoice_show','fields'=>['ref','client','client_ref','status','order_ref','expedition_ref'],'icon'=>'fa-file-invoice-dollar'],
        'Entrepôts'=>['collection'=>'warehouses','url'=>'warehouse_show','fields'=>['ref','name','city'],'icon'=>'fa-box-open'],
        'Expéditions'=>['collection'=>'expeditions','url'=>'expedition_show','fields'=>['ref','tier_name','tier_ref','tracking','status'],'icon'=>'fa-dolly'],
        'Réceptions'=>['collection'=>'receptions','url'=>'reception_show','fields'=>['ref','tier_name','tier_ref','status'],'icon'=>'fa-cart-flatbed'],
        'Utilisateurs'=>['collection'=>'users','url'=>'user_show','fields'=>['ref','username','firstname','name','email','role'],'icon'=>'fa-users-gear'],
    ];
    foreach($collections as $label=>$cfg){
        $rows=[];
        foreach(data_read($cfg['collection'], []) as $r){
            $hay=[]; foreach($cfg['fields'] as $f) $hay[]=$r[$f] ?? '';
            if(!ge_contains(implode(' ', $hay), $q)) continue;
            $id=(int)($r['id'] ?? 0);
            if($id<=0) continue;
            $main=$r['ref'] ?? $r['label'] ?? $r['name'] ?? $r['username'] ?? ('#'.$id);
            $sub=$r['client'] ?? $r['tier_name'] ?? $r['email'] ?? $r['description'] ?? $r['status'] ?? '';
            $rows[]=['id'=>$id,'url'=>'index.php?page='.$cfg['url'].'&id='.$id,'main'=>$main,'sub'=>$sub,'icon'=>$cfg['icon']];
            if(count($rows)>=10) break;
        }
        if($rows) $groups[$label]=$rows;
    }
}
?>
<div class="panel search-page">
  <h2><i class="fa-solid fa-magnifying-glass"></i> Recherche globale</h2>
  <form method="get" action="index.php" class="dol-form"><input type="hidden" name="page" value="search"><div class="dol-line"><label>Mot clé</label><input name="q" value="<?=e($q)?>" placeholder="Produit, client, facture, commande..."></div><div class="dol-actions"><button class="btn orange">RECHERCHER</button></div></form>
</div>
<?php if($q===''): ?>
  <div class="panel"><p class="muted">Tapez un mot clé pour chercher dans produits, clients, devis, commandes, factures, stock et utilisateurs.</p></div>
<?php elseif(!$groups): ?>
  <div class="panel"><p>Aucun résultat pour <b><?=e($q)?></b>.</p></div>
<?php else: ?>
  <?php foreach($groups as $label=>$rows): ?>
    <div class="panel search-results-group"><h3><?=e($label)?> <small>(<?=count($rows)?>)</small></h3><table class="clean-table"><thead><tr><th>Résultat</th><th>Détail</th><th></th></tr></thead><tbody><?php foreach($rows as $r): ?><tr><td><i class="fa-solid <?=e($r['icon'])?> text-green"></i> <a class="ref" href="<?=e($r['url'])?>"><?=e($r['main'])?></a></td><td><?=e($r['sub'])?></td><td><a class="btn small" href="<?=e($r['url'])?>">Ouvrir</a></td></tr><?php endforeach; ?></tbody></table></div>
  <?php endforeach; ?>
<?php endif; ?>
<?php include __DIR__.'/layouts/footer.php'; ?>
