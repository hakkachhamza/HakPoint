<div class="erp-dashboard-layout bottom-dashboard">
  <section class="erp-widget">
    <div class="widget-head"><span>Les 5 derniers produits/services modifiés</span><div><i class="fa-solid fa-arrows-up-down-left-right"></i> <i class="fa-solid fa-xmark"></i></div></div>
    <table class="tiny-table">
      <?php if(empty($recentProducts)): ?><tr><td>Aucun produit</td><td class="ok-dot"></td></tr><?php endif; ?>
      <?php foreach($recentProducts as $p): ?>
        <tr><td><a href="<?=product_url($p['id'])?>"><i class="fa-solid fa-cube product-cube"></i> <?=e($p['ref'])?></a></td><td><?=e($p['label'])?></td><td><?=money($p['sale_price']??0)?></td><td class="ok-dot"></td></tr>
      <?php endforeach; ?>
    </table>
  </section>
  <section class="erp-widget">
    <div class="widget-head"><span>Balances des factures ouvertes</span><div><i class="fa-solid fa-arrows-up-down-left-right"></i> <i class="fa-solid fa-xmark"></i></div></div>
    <?php
      $openInvoices = array_filter($invoices ?? [], fn($i)=>!in_array(strtolower($i['status'] ?? ''), ['payée','paye','paid','réglée','reglee','abandonnée','abandonnee'], true));
      $openTotal = array_sum(array_map('amount_from_row', $openInvoices));
    ?>
    <table class="tiny-table"><tr><td>Total MAD</td><td class="num"><?=money($openTotal)?> MAD</td></tr><tr><td>Factures ouvertes</td><td class="num"><?=count($openInvoices)?></td></tr></table>
  </section>
</div>
