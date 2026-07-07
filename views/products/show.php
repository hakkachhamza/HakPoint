<?php
$products=data_read('products',[]); $id=(int)($_GET['id']??0); $product=null; foreach($products as $p){ if((int)$p['id']===$id){$product=$p;break;} } if(!$product) redirect_to('index.php?page=products');
$tab=$_GET['tab']??'product';
$tabs=['product'=>'Produit','sale_price'=>'Prix de vente','buy_price'=>'Prix d’achat','stock'=>'Stock','objects'=>'Objets référents','statistics'=>'Statistiques','margins'=>'Marges','notes'=>'Notes','files'=>'Fichiers joints','events'=>'Événements'];
if(!array_key_exists($tab,$tabs)) $tab='product';
$title='Produit - '.$product['label']; include __DIR__.'/../layouts/header.php';
$tabFile=__DIR__.'/tabs/'.$tab.'.php'; if(!is_file($tabFile)) $tabFile=__DIR__.'/tabs/product.php';
?>
<div class="product-show clean-dol">
 <div class="tabs"><?php foreach($tabs as $key=>$label):?><a class="<?=$tab===$key?'active':''?>" href="<?=product_url($product['id'],$key)?>"><?=e($label)?></a><?php endforeach;?></div>
 <div class="show-head">
  <div class="photo product-photo-real"><img src="<?=e(product_image_src($product))?>" alt="Image produit"></div>
  <div><h1><?=e($product['label'])?></h1><p class="muted-text">Réf. <?=e($product['ref'])?></p></div>
  <div class="head-actions"><a href="index.php?page=products">Retour liste</a><span>‹</span><b>›</b><div><span class="badge green"><?=e($product['sale_status']??'En vente')?></span> <span class="badge green"><?=e($product['buy_status']??'En achat')?></span></div></div>
 </div>
 <?php include $tabFile; ?>
</div>
<?php include __DIR__.'/../layouts/footer.php'; ?>
