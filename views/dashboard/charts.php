<?php
$monthLabels=['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Août','Sep','Oct','Nov','Déc'];
if(!function_exists('hp_monthly_stats')){
function hp_monthly_stats($rows){
    $stats=[]; for($i=1;$i<=12;$i++) $stats[$i]=['count'=>0,'amount'=>0];
    foreach($rows as $r){
        $m=month_key_from_date($r['date'] ?? $r['order_date'] ?? $r['invoice_date'] ?? $r['proposal_date'] ?? $r['created_at'] ?? '');
        if($m<1 || $m>12) $m=(int)date('n');
        $stats[$m]['count']++;
        $stats[$m]['amount'] += amount_from_row($r);
    }
    return $stats;
}
}
if(!function_exists('hp_values')){
function hp_values($stats,$field){ return array_values(array_map(fn($s)=>(float)$s[$field], $stats)); }
}
if(!function_exists('hp_status_counts')){
function hp_status_counts($rows){
    $out=[];
    foreach($rows as $r){ $s=trim((string)($r['status'] ?? $r['state'] ?? 'Brouillon')); if($s==='') $s='Brouillon'; $out[$s]=($out[$s]??0)+1; }
    if(!$out) $out=['Aucun'=>0];
    return $out;
}
}
if(!function_exists('hp_total_amount')){
function hp_total_amount($rows){ $t=0; foreach($rows as $r){ $t += amount_from_row($r); } return $t; }
}
if(!function_exists('hp_visual_values')){
function hp_visual_values($values, $fallback = []){
    // Real dashboard mode: keep real zero values; never draw fake demo bars/lines.
    return array_values(array_map(fn($v)=>(float)$v, $values ?? []));
}
}
$invoiceStats=hp_monthly_stats($invoices);
$quoteStats=hp_monthly_stats($quotes);
$orderStats=hp_monthly_stats($orders);
$expeditionStats=hp_monthly_stats($expeditions);
$receptionStats=hp_monthly_stats($receptions);
$quoteStatus=hp_status_counts($quotes);
$orderStatus=hp_status_counts($orders);
$invoiceStatus=hp_status_counts($invoices);
$expStatus=hp_status_counts($expeditions);
$recStatus=hp_status_counts($receptions);
$purchaseStatus=hp_status_counts($purchases ?? []);
$approvalStatus=hp_status_counts($approvals ?? []);
$unpaidInvoices=array_slice(array_values(array_filter($invoices, fn($i)=>!in_array(strtolower($i['status']??''), ['payée','paye','paid','réglée','reglee'], true))),0,5);
$recentDocs=array_slice(array_reverse(array_merge(
    array_map(fn($r)=>$r+['_kind'=>'Devis'], $quotes),
    array_map(fn($r)=>$r+['_kind'=>'Commande'], $orders),
    array_map(fn($r)=>$r+['_kind'=>'Facture'], $invoices)
)),0,6);
$quoteAmountValues=hp_visual_values(hp_values($quoteStats,'amount'));
$orderAmountValues=hp_visual_values(hp_values($orderStats,'amount'));
$invoiceAmountValues=hp_visual_values(hp_values($invoiceStats,'amount'));
$chartPayload=[
  'months'=>$monthLabels,
  'invoiceCounts'=>hp_visual_values(hp_values($invoiceStats,'count')),
  'invoiceAmounts'=>$invoiceAmountValues,
  'quoteCounts'=>hp_visual_values(hp_values($quoteStats,'count')),
  'quoteAmounts'=>$quoteAmountValues,
  'orderCounts'=>hp_visual_values(hp_values($orderStats,'count')),
  'orderAmounts'=>$orderAmountValues,
  'expeditionCounts'=>hp_visual_values(hp_values($expeditionStats,'count')),
  'receptionCounts'=>hp_visual_values(hp_values($receptionStats,'count')),
  'stockLabels'=>array_keys($stockStatus),'stockValues'=>hp_visual_values(array_values($stockStatus)),
  'businessLabels'=>array_keys($businessStatus),'businessValues'=>hp_visual_values(array_values($businessStatus)),
  'documentLabels'=>array_keys($documentsStatus),'documentValues'=>hp_visual_values(array_values($documentsStatus)),
  'financeLabels'=>array_keys($financialStatus),'financeValues'=>hp_visual_values(array_values($financialStatus)),
  'productLabels'=>['Produits','Services'],'productValues'=>hp_visual_values([$productGoods,$productServices]),
  'quoteStatusLabels'=>array_keys($quoteStatus),'quoteStatusValues'=>array_values($quoteStatus),
  'orderStatusLabels'=>array_keys($orderStatus),'orderStatusValues'=>array_values($orderStatus),
  'invoiceStatusLabels'=>array_keys($invoiceStatus),'invoiceStatusValues'=>array_values($invoiceStatus),
  'expStatusLabels'=>array_keys($expStatus),'expStatusValues'=>array_values($expStatus),
  'recStatusLabels'=>array_keys($recStatus),'recStatusValues'=>array_values($recStatus),
  'moduleLabels'=>array_keys($modulesOverview ?? []),'moduleValues'=>hp_visual_values(array_values($modulesOverview ?? [])),
  'enterpriseAmountLabels'=>array_keys($enterpriseAmounts ?? []),'enterpriseAmountValues'=>hp_visual_values(array_values($enterpriseAmounts ?? [])),
  'purchaseStatusLabels'=>array_keys($purchaseStatus),'purchaseStatusValues'=>array_values($purchaseStatus),
  'approvalStatusLabels'=>array_keys($approvalStatus),'approvalStatusValues'=>array_values($approvalStatus),
];
$dashboardInvoiceTotal = hp_total_amount($invoices);
$jsonFlags=JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT;
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<div class="hp-dashboard-pro hp-dashboard-no-gaps">
  <section class="hp-chart-card hp-card-business">
    <div class="hp-chart-head"><div><b>Vue générale</b><span>Tous les modules du projet</span></div><small class="hp-pill-year"><?=date('Y')?></small></div>
    <div class="hp-chart-canvas"><canvas id="hpBusinessChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-docs">
    <div class="hp-chart-head"><div><b>Documents</b><span>Devis, commandes, factures et stock</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpDocsChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-flux">
    <div class="hp-chart-head"><div><b>Flux commercial mensuel</b><span>Devis, commandes et factures</span></div><small class="hp-pill-money"><?=money($dashboardInvoiceTotal)?> MAD</small></div>
    <div class="hp-chart-canvas"><canvas id="hpMonthlyChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-finance">
    <div class="hp-chart-head"><div><b>Montants</b><span>Chiffres par section</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpFinanceChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-stock">
    <div class="hp-chart-head"><div><b>Stock</b><span><?=money($totalStock)?> unités</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpStockChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-products">
    <div class="hp-chart-head"><div><b>Produits / services</b><span>Catalogue</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpProductsChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-status hp-card-quote">
    <div class="hp-chart-head"><div><b>Devis</b><span>Par statut</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpQuoteStatusChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-status hp-card-order">
    <div class="hp-chart-head"><div><b>Commandes</b><span>Par statut</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpOrderStatusChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-status hp-card-invoice">
    <div class="hp-chart-head"><div><b>Factures</b><span>Par statut</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpInvoiceStatusChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-logistics">
    <div class="hp-chart-head"><div><b>Logistique</b><span>Expéditions et réceptions</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpLogisticsChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-status hp-card-exp">
    <div class="hp-chart-head"><div><b>Expéditions</b><span>Par statut</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpExpStatusChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-status hp-card-rec">
    <div class="hp-chart-head"><div><b>Réceptions</b><span>Par statut</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpRecStatusChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-modules">
    <div class="hp-chart-head"><div><b>Modules ERP</b><span>Achats, validations, finance et opérations</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpModulesChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-enterprise">
    <div class="hp-chart-head"><div><b>Montants ERP</b><span>Clients, fournisseurs, avoirs et paiements</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpEnterpriseAmountsChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-status hp-card-purchase">
    <div class="hp-chart-head"><div><b>Achats fournisseurs</b><span>Par statut</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpPurchaseStatusChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-card-status hp-card-approval">
    <div class="hp-chart-head"><div><b>Validations</b><span>Décisions et attentes</span></div></div>
    <div class="hp-chart-canvas"><canvas id="hpApprovalStatusChart"></canvas></div>
  </section>
  <section class="hp-chart-card hp-table-card hp-dashboard-list-card hp-card-list">
    <div class="hp-chart-head"><div><b>Stock à surveiller</b><span>Produits en alerte</span></div></div>
    <div class="hp-clean-list">
      <?php if(empty($lowStockProducts)): ?>
        <div class="hp-clean-empty"><i class="fa-solid fa-circle-check"></i><span>Aucun produit en alerte stock</span><b>OK</b></div>
      <?php endif; ?>
      <?php foreach(array_slice($lowStockProducts,0,8) as $p): ?>
        <a class="hp-clean-row hp-stock-row" href="<?=product_url($p['id'],'stock')?>">
          <span class="hp-row-main"><b><?=e($p['ref'] ?? '')?></b><small><?=e($p['label'] ?? $p['name'] ?? '')?></small></span>
          <span class="hp-row-badge danger"><?=e($p['physical_stock']??0)?> / <?=e($p['alert_stock']??0)?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>
  <section class="hp-chart-card hp-table-card hp-dashboard-list-card hp-card-list">
    <div class="hp-chart-head"><div><b>Documents récents</b><span>Dernières activités</span></div></div>
    <div class="hp-clean-list">
      <?php if(empty($recentDocs)): ?>
        <div class="hp-clean-empty"><i class="fa-regular fa-folder-open"></i><span>Aucun document</span><b>0</b></div>
      <?php endif; ?>
      <?php foreach(array_slice($recentDocs,0,8) as $r): ?>
        <div class="hp-clean-row">
          <span class="hp-row-main"><b><?=e($r['_kind'])?> <?=e($r['ref']??('#'.($r['id']??'')))?></b><small><?=e($r['created_at']??$r['date']??$r['order_date']??'')?></small></span>
          <span class="hp-row-badge"><?=money(amount_from_row($r))?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>
<script>
(function(){
 const d = <?=json_encode($chartPayload,$jsonFlags)?>;
 if(!window.Chart) return;

 /* Modern color palette - harmonious, professional */
 const palette = {
   blue:    { base:'#3B82F6', soft:'#60A5FA', bg:'rgba(59,130,246,0.12)' },
   indigo:  { base:'#6366F1', soft:'#818CF8', bg:'rgba(99,102,241,0.12)' },
   violet:  { base:'#8B5CF6', soft:'#A78BFA', bg:'rgba(139,92,246,0.12)' },
   teal:    { base:'#14B8A6', soft:'#2DD4BF', bg:'rgba(20,184,166,0.12)' },
   emerald: { base:'#10B981', soft:'#34D399', bg:'rgba(16,185,129,0.12)' },
   amber:   { base:'#F59E0B', soft:'#FBBF24', bg:'rgba(245,158,11,0.12)' },
   orange:  { base:'#F97316', soft:'#FB923C', bg:'rgba(249,115,22,0.12)' },
   rose:    { base:'#F43F5E', soft:'#FB7185', bg:'rgba(244,63,94,0.12)' },
   cyan:    { base:'#06B6D4', soft:'#22D3EE', bg:'rgba(6,182,212,0.12)' },
   sky:     { base:'#0EA5E9', soft:'#38BDF8', bg:'rgba(14,165,233,0.12)' },
   slate:   { base:'#64748B', soft:'#94A3B8', bg:'rgba(100,116,139,0.12)' },
   lime:    { base:'#84CC16', soft:'#A3E635', bg:'rgba(132,204,22,0.12)' }
 };
 const paletteList = Object.values(palette).map(p => p.base);
 const paletteBg   = Object.values(palette).map(p => p.bg);

 Chart.defaults.font.family = "'Inter', 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
 Chart.defaults.font.size   = 11;
 Chart.defaults.color       = '#475569';
 Chart.defaults.animation   = { duration: 900, easing: 'easeOutQuart' };
 Chart.defaults.animations  = { colors: true, x: { duration: 700 }, y: { duration: 700 } };

 const money  = v => new Intl.NumberFormat('fr-FR',{maximumFractionDigits:0}).format(Number(v)||0) + ' MAD';
 const number = v => new Intl.NumberFormat('fr-FR',{maximumFractionDigits:0}).format(Number(v)||0);
 const compact = v => new Intl.NumberFormat('fr-FR',{notation:'compact',maximumFractionDigits:1}).format(Number(v)||0);

 function el(id){ return document.getElementById(id); }
 function values(arr){ return (Array.isArray(arr)?arr:[]).map(v=>Number(v)||0); }

 function vGrad(ctx, area, color, alpha1=0.85, alpha2=0.05){
   if(!area) return color;
   const g = ctx.createLinearGradient(0, area.top, 0, area.bottom);
   g.addColorStop(0, hexToRgba(color, alpha1));
   g.addColorStop(1, hexToRgba(color, alpha2));
   return g;
 }
 function hGrad(ctx, area, color, alpha1=0.85, alpha2=0.05){
   if(!area) return color;
   const g = ctx.createLinearGradient(area.left, 0, area.right, 0);
   g.addColorStop(0, hexToRgba(color, alpha1));
   g.addColorStop(1, hexToRgba(color, alpha2));
   return g;
 }
 function hexToRgba(hex, a){
   const h = hex.replace('#','');
   const r = parseInt(h.substring(0,2),16);
   const g = parseInt(h.substring(2,4),16);
   const b = parseInt(h.substring(4,6),16);
   return `rgba(${r},${g},${b},${a})`;
 }

 const grid = { color:'rgba(148,163,184,0.18)', lineWidth:1, drawTicks:false };
 const gridSoft = { color:'rgba(148,163,184,0.10)', lineWidth:1, drawTicks:false };

 const tooltipBase = {
   backgroundColor:'rgba(15,23,42,0.96)',
   titleColor:'#F8FAFC',
   bodyColor:'#E2E8F0',
   titleFont:{size:12,weight:'800',family:"'Inter',sans-serif"},
   bodyFont:{size:11,weight:'600',family:"'Inter',sans-serif"},
   padding:12, cornerRadius:10, displayColors:true, boxPadding:6,
   borderColor:'rgba(255,255,255,0.08)', borderWidth:1
 };

 function barDS(label, data, colorKey, moneyMode, opts={}){
   const c = palette[colorKey] || palette.blue;
   return {
     label:label,
     data:values(data),
     backgroundColor:(ctx)=>vGrad(ctx.chart.ctx, ctx.chart.chartArea, c.base, 0.95, 0.35),
     hoverBackgroundColor:(ctx)=>vGrad(ctx.chart.ctx, ctx.chart.chartArea, c.base, 1, 0.55),
     borderColor:c.soft,
     borderWidth:0,
     borderRadius:{topLeft:8, topRight:8, bottomLeft:0, bottomRight:0},
     borderSkipped:false,
     maxBarThickness:opts.thick || 32,
     categoryPercentage:0.7,
     barPercentage:0.85,
     moneyMode:!!moneyMode,
     borderRadiusAsObject:true
   };
 }

 function lineDS(label, data, colorKey, moneyMode, fill=true){
   const c = palette[colorKey] || palette.blue;
   return {
     label:label,
     data:values(data),
     borderColor:c.base,
     backgroundColor:(ctx)=>vGrad(ctx.chart.ctx, ctx.chart.chartArea, c.base, 0.32, 0.0),
     pointBackgroundColor:'#fff',
     pointBorderColor:c.base,
     pointBorderWidth:2.5,
     pointRadius:4,
     pointHoverRadius:7,
     pointHoverBackgroundColor:c.base,
     pointHoverBorderColor:'#fff',
     borderWidth:3,
     tension:0.4,
     fill:fill,
     moneyMode:!!moneyMode,
     cubicInterpolationMode:'monotone'
   };
 }

 function baseOptions(moneyMode, hideLegend){
   return {
     responsive:true,
     maintainAspectRatio:false,
     layout:{padding:{top:6,right:6,bottom:0,left:2}},
     interaction:{mode:'index',intersect:false},
     plugins:{
       legend:{
         display:!hideLegend,
         position:'bottom',
         labels:{
           boxWidth:8, boxHeight:8, usePointStyle:true, padding:12,
           font:{size:10,weight:'700',family:"'Inter',sans-serif"},
           color:'#64748B'
         }
       },
       tooltip:{
         ...tooltipBase,
         callbacks:{label:(c)=>{
           const v = c.parsed.y ?? c.parsed;
           return '  '+c.dataset.label+': '+(c.dataset.moneyMode ? money(v) : number(v));
         }}
       }
     },
     scales:{
       x:{
         grid:{display:false},
         border:{color:'rgba(148,163,184,0.25)',display:false},
         ticks:{font:{size:10,weight:'700',family:"'Inter',sans-serif"},color:'#94A3B8',maxRotation:0,minRotation:0,padding:6}
       },
       y:{
         beginAtZero:true,
         grid:grid,
         border:{display:false},
         ticks:{
           font:{size:10,weight:'600',family:"'Inter',sans-serif"},
           color:'#94A3B8',
           padding:8,
           callback:(v)=>moneyMode?compact(v):number(v)
         }
       }
     }
   };
 }

 function lineOptions(moneyMode){
   return {
     responsive:true, maintainAspectRatio:false,
     layout:{padding:{top:10,right:10,bottom:0,left:2}},
     interaction:{mode:'index',intersect:false},
     plugins:{
       legend:{
         position:'bottom',
         labels:{boxWidth:8,boxHeight:8,usePointStyle:true,padding:12,font:{size:10,weight:'700',family:"'Inter',sans-serif"},color:'#64748B'}
       },
       tooltip:{
         ...tooltipBase,
         callbacks:{label:(c)=>'  '+c.dataset.label+': '+(c.dataset.moneyMode?money(c.parsed.y):number(c.parsed.y))}
       }
     },
     scales:{
       x:{grid:{display:false},border:{display:false},ticks:{font:{size:10,weight:'700'},color:'#94A3B8',padding:6}},
       y:{
         beginAtZero:true,grid:grid,border:{display:false},
         ticks:{font:{size:10,weight:'600'},color:'#94A3B8',padding:8,callback:(v)=>moneyMode?compact(v):number(v)}
       }
     }
   };
 }

 function doughnutOptions(extra={}){
   return {
     responsive:true, maintainAspectRatio:false,
     cutout:'68%',
     radius:'92%',
     layout:{padding:6},
     plugins:{
       legend:{
         position:'bottom',
         labels:{boxWidth:8,boxHeight:8,usePointStyle:true,padding:11,font:{size:10,weight:'700',family:"'Inter',sans-serif"},color:'#64748B'}
       },
       tooltip:{
         ...tooltipBase,
         callbacks:{label:(c)=>'  '+c.label+': '+number(c.parsed)}
       }
     },
     ...extra
   };
 }

 function polarOptions(extra={}){
   return {
     responsive:true, maintainAspectRatio:false,
     layout:{padding:6},
     plugins:{
       legend:{
         position:'bottom',
         labels:{boxWidth:8,boxHeight:8,usePointStyle:true,padding:11,font:{size:10,weight:'700',family:"'Inter',sans-serif"},color:'#64748B'}
       },
       tooltip:{
         ...tooltipBase,
         callbacks:{label:(c)=>'  '+c.label+': '+number(c.parsed.r)}
       }
     },
     scales:{
       r:{
         beginAtZero:true,
         grid:{color:'rgba(148,163,184,0.16)'},
         angleLines:{color:'rgba(148,163,184,0.16)'},
         pointLabels:{font:{size:10,weight:'700'},color:'#475569'},
         ticks:{display:false,backdropColor:'transparent'}
       }
     },
     ...extra
   };
 }

 function radarOptions(extra={}){
   return {
     responsive:true, maintainAspectRatio:false,
     layout:{padding:6},
     plugins:{
       legend:{display:false},
       tooltip:{...tooltipBase,callbacks:{label:(c)=>'  '+c.dataset.label+': '+number(c.parsed.r)}}
     },
     scales:{
       r:{
         beginAtZero:true,
         grid:{color:'rgba(148,163,184,0.18)'},
         angleLines:{color:'rgba(148,163,184,0.18)'},
         pointLabels:{font:{size:10,weight:'700'},color:'#475569',padding:4},
         ticks:{display:false,backdropColor:'transparent'}
       }
     },
     ...extra
   };
 }

 /* ===== Chart renderers ===== */

 function singleBar(id, labels, data, label, moneyMode, colorKey, opts){
   const c = el(id); if(!c) return;
   new Chart(c, {
     type:'bar',
     data:{labels:labels||[], datasets:[barDS(label||'Total', data, colorKey, moneyMode, opts)]},
     options:{...baseOptions(moneyMode, true), plugins:{...baseOptions(moneyMode,true).plugins}}
   });
 }

 function groupedBar(id, labels, sets, moneyMode){
   const c = el(id); if(!c) return;
   new Chart(c, {
     type:'bar',
     data:{labels:labels||[], datasets:sets},
     options:baseOptions(moneyMode)
   });
 }

 function multiLine(id, labels, sets, moneyMode){
   const c = el(id); if(!c) return;
   new Chart(c, {
     type:'line',
     data:{labels:labels||[], datasets:sets},
     options:lineOptions(moneyMode)
   });
 }

 function doughnutChart(id, labels, data){
   const c = el(id); if(!c) return;
   const bg = (labels||[]).map((_,i)=>paletteList[i % paletteList.length]);
   new Chart(c, {
     type:'doughnut',
     data:{labels:labels||[], datasets:[{
       data:values(data),
       backgroundColor:bg,
       hoverBackgroundColor:bg.map(c=>hexToRgba(c, 0.85)),
       borderColor:'#fff',
       borderWidth:3,
       borderRadius:6,
       spacing:2,
       hoverOffset:8
     }]},
     options:doughnutOptions()
   });
 }

 function polarChart(id, labels, data){
   const c = el(id); if(!c) return;
   const bg = (labels||[]).map((_,i)=>hexToRgba(paletteList[i % paletteList.length], 0.78));
   const border = (labels||[]).map((_,i)=>paletteList[i % paletteList.length]);
   new Chart(c, {
     type:'polarArea',
     data:{labels:labels||[], datasets:[{
       data:values(data),
       backgroundColor:bg,
       borderColor:border,
       borderWidth:2,
       hoverOffset:8
     }]},
     options:polarOptions()
   });
 }

 function radarChart(id, labels, data, colorKey){
   const c = el(id); if(!c) return;
   const col = palette[colorKey] || palette.blue;
   new Chart(c, {
     type:'radar',
     data:{labels:labels||[], datasets:[{
       label:'Total',
       data:values(data),
       backgroundColor:(ctx)=>{
         const a = ctx.chart.chartArea;
         if(!a) return col.bg;
         const g = ctx.chart.ctx.createRadialGradient(
           (a.left+a.right)/2, (a.top+a.bottom)/2, 10,
           (a.left+a.right)/2, (a.top+a.bottom)/2, Math.max(a.right-a.left, a.bottom-a.top)/2
         );
         g.addColorStop(0, hexToRgba(col.base, 0.45));
         g.addColorStop(1, hexToRgba(col.base, 0.10));
         return g;
       },
       borderColor:col.base,
       borderWidth:2.5,
       pointBackgroundColor:'#fff',
       pointBorderColor:col.base,
       pointBorderWidth:2,
       pointRadius:4,
       pointHoverRadius:6
     }]},
     options:radarOptions()
   });
 }

 function horizontalBar(id, labels, data, label, moneyMode, colorKey){
   const c = el(id); if(!c) return;
   const col = palette[colorKey] || palette.blue;
   new Chart(c, {
     type:'bar',
     data:{labels:labels||[], datasets:[{
       label:label||'Total',
       data:values(data),
       backgroundColor:(ctx)=>hGrad(ctx.chart.ctx, ctx.chart.chartArea, col.base, 0.95, 0.45),
       hoverBackgroundColor:col.base,
       borderColor:col.soft,
       borderWidth:0,
       borderRadius:6,
       borderSkipped:false,
       maxBarThickness:24,
       categoryPercentage:0.7,
       barPercentage:0.85,
       moneyMode:!!moneyMode
     }]},
     options:{
       ...baseOptions(moneyMode, true),
       indexAxis:'y',
       scales:{
         x:{beginAtZero:true,grid:grid,border:{display:false},ticks:{font:{size:10,weight:'600'},color:'#94A3B8',padding:6,callback:(v)=>moneyMode?compact(v):number(v)}},
         y:{grid:{display:false},border:{display:false},ticks:{font:{size:10,weight:'700'},color:'#475569',padding:4}}
       }
     }
   });
 }

 /* ===== Assign charts to cards ===== */

 /* Overview - radar (signature visual) */
 radarChart('hpBusinessChart', d.businessLabels, d.businessValues, 'indigo');

 /* Documents - horizontal bar (great for comparing categories) */
 horizontalBar('hpDocsChart', d.documentLabels, d.documentValues, 'Documents', false, 'blue');

 /* Monthly flux - smooth area lines (most premium look) */
 multiLine('hpMonthlyChart', d.months, [
   lineDS('Devis', d.quoteAmounts, 'blue', true),
   lineDS('Commandes', d.orderAmounts, 'emerald', true),
   lineDS('Factures', d.invoiceAmounts, 'violet', true)
 ], true);

 /* Finance - vertical bar with gradient */
 singleBar('hpFinanceChart', d.financeLabels, d.financeValues, 'Montant', true, 'emerald');

 /* Stock - doughnut (perfect for status breakdown) */
 doughnutChart('hpStockChart', d.stockLabels, d.stockValues);

 /* Products / services - polar area (eye-catching for binary-ish data) */
 polarChart('hpProductsChart', d.productLabels, d.productValues);

 /* Status charts - doughnuts for visual variety */
 doughnutChart('hpQuoteStatusChart', d.quoteStatusLabels, d.quoteStatusValues);
 doughnutChart('hpOrderStatusChart', d.orderStatusLabels, d.orderStatusValues);
 doughnutChart('hpInvoiceStatusChart', d.invoiceStatusLabels, d.invoiceStatusValues);

 /* Logistics - grouped bars with smooth corners */
 groupedBar('hpLogisticsChart', d.months, [
   barDS('Expéditions', d.expeditionCounts, 'cyan', false),
   barDS('Réceptions', d.receptionCounts, 'indigo', false)
 ], false);

 doughnutChart('hpExpStatusChart', d.expStatusLabels, d.expStatusValues);
 doughnutChart('hpRecStatusChart', d.recStatusLabels, d.recStatusValues);

 /* Modules - radar (impactful for many categories) */
 radarChart('hpModulesChart', d.moduleLabels, d.moduleValues, 'teal');

 /* Enterprise amounts - horizontal bar */
 horizontalBar('hpEnterpriseAmountsChart', d.enterpriseAmountLabels, d.enterpriseAmountValues, 'Montant', true, 'amber');

 /* Status doughnuts */
 doughnutChart('hpPurchaseStatusChart', d.purchaseStatusLabels, d.purchaseStatusValues);
 doughnutChart('hpApprovalStatusChart', d.approvalStatusLabels, d.approvalStatusValues);

})();
</script>
