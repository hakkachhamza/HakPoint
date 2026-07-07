<?php include __DIR__.'/../../../app/tab_vars.php';
$saleRows=$product['sale_prices']??[];
if(!is_array($saleRows)) $saleRows=[];
if(!$saleRows){
  $saleRows[]=[
    'date'=>$product['updated_at'] ?? $product['created_at'] ?? date('d/m/Y H:i'),
    'base'=>'HT','tax'=>$taxRate,'ht'=>$sale,'ttc'=>$ttc,
    'min_ht'=>(float)($product['sale_price_min'] ?? 0),'min_ttc'=>(float)($product['sale_price_min'] ?? 0)*(1+($taxRate/100)),
    'user'=>ge_record_author($product,'created_by')
  ];
}
?>
<div class="dol-section">
  <div class="dol-lines full">
    <div><span>Taux de taxe par défaut</span><b><?=e($taxRate)?>%</b></div>
    <div><span>Prix de vente</span><b><?=money($sale)?> HT</b></div>
    <div><span>Prix de vente min.</span><b><?=money($product['sale_price_min'] ?? 0)?> HT</b></div>
  </div>
  <div class="right-actions"><button type="button" class="orange" data-open-modal="salePriceModal">MODIFIER PRIX PAR DÉFAUT</button></div>
</div>

<div class="dol-icon-line"><span class="small-icon money">$=</span><span class="count">(<?=count($saleRows)?>)</span></div>
<table class="dol-table">
  <thead><tr><th>Pratiqués à partir du</th><th>Base de prix</th><th>Taux de taxe par défaut</th><th>HT</th><th>TTC</th><th>Prix de vente min. HT</th><th>Prix de vente min. TTC</th><th>Modifié par</th><th></th></tr></thead>
  <tbody>
    <?php foreach($saleRows as $r): ?>
      <tr><td><?=e($r['date']??'')?></td><td><?=e($r['base']??'HT')?></td><td><?=e($r['tax']??$taxRate)?>%</td><td><?=money($r['ht']??0)?></td><td><?=money($r['ttc']??0)?></td><td><?=money($r['min_ht']??0)?></td><td><?=money($r['min_ttc']??0)?></td><td>👤 <?=e(ge_record_author($r,'user'))?></td><td></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php include __DIR__.'/../modals/sale_price_modal.php'; ?>
