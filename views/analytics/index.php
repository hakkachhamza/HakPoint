<?php
$title='Analyse / BI';
include __DIR__.'/../layouts/header.php';
$products=data_read('products',[]); $tiers=data_read('tiers',[]); $quotes=data_read('quotes',[]); $orders=data_read('orders',[]); $invoices=data_read('invoices',[]); $payments=data_read('payments',[]); $supplierPayments=data_read('supplier_payments',[]);
$purchases=[]; $supplierInvoices=[]; $creditNotes=[]; $approvals=[];
try{ $pdo=db(); if(function_exists('ge_erp_ensure_tables')) ge_erp_ensure_tables($pdo); $purchases=ge_fetch_tenant_rows($pdo, 'ge_purchase_orders', 'id DESC', 5000); $supplierInvoices=ge_fetch_tenant_rows($pdo, 'ge_supplier_invoices', 'id DESC', 5000); $creditNotes=ge_fetch_tenant_rows($pdo, 'ge_credit_notes', 'id DESC', 5000); $approvals=ge_fetch_tenant_rows($pdo, 'ge_approval_requests', 'id DESC', 5000); }catch(Throwable $e){}
function ga_sum($rows){$t=0; foreach((array)$rows as $r){$t+=amount_from_row($r);} return $t;}
function ga_status($rows){$out=[]; foreach((array)$rows as $r){$s=trim((string)($r['status']??'Sans statut'))?:'Sans statut'; $out[$s]=($out[$s]??0)+1;} return $out?:['Aucune donnée'=>0];}
$counts=['Produits'=>count($products),'Tiers'=>count($tiers),'Devis'=>count($quotes),'Commandes'=>count($orders),'Factures'=>count($invoices),'Achats'=>count($purchases),'Fact. fourn.'=>count($supplierInvoices),'Avoirs'=>count($creditNotes),'Validations'=>count($approvals),'Paiements'=>count($payments)+count($supplierPayments)];
$amounts=['Devis'=>ga_sum($quotes),'Commandes'=>ga_sum($orders),'Factures'=>ga_sum($invoices),'Achats'=>ga_sum($purchases),'Fact. fourn.'=>ga_sum($supplierInvoices),'Avoirs'=>ga_sum($creditNotes),'Paiements clients'=>ga_sum($payments),'Paiements fourn.'=>ga_sum($supplierPayments)];
$payload=['countLabels'=>array_keys($counts),'countValues'=>array_values($counts),'amountLabels'=>array_keys($amounts),'amountValues'=>array_values($amounts),'invoiceStatusLabels'=>array_keys(ga_status($invoices)),'invoiceStatusValues'=>array_values(ga_status($invoices)),'approvalStatusLabels'=>array_keys(ga_status($approvals)),'approvalStatusValues'=>array_values(ga_status($approvals))];
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<div class="ge-simple-section analytics-page">
  <section class="ge-section-hero compact">
    <div><div class="ge-eyebrow"><i class="fa-solid fa-chart-pie"></i> Analyse / BI</div><h1>Analyse globale</h1><p>Vue simple et légère de tous les modules actifs avec des graphiques Chart.js.</p></div>
    <div class="ge-hero-actions"><a class="btn secondary" href="index.php?page=reports">Rapports tableau</a><a class="btn primary" href="index.php?page=dashboard">Dashboard</a></div>
  </section>
  <div class="analytics-grid">
    <section class="hp-chart-card hp-wide hp-card-modules">
      <div class="hp-chart-head"><div><b>Volumes par module</b><span>Total des éléments par section</span></div></div>
      <div class="hp-chart-canvas hp-tall"><canvas id="gaCounts"></canvas></div>
    </section>
    <section class="hp-chart-card hp-wide hp-card-finance">
      <div class="hp-chart-head"><div><b>Montants par module</b><span>Ventes, achats, paiements et avoirs</span></div></div>
      <div class="hp-chart-canvas hp-tall"><canvas id="gaAmounts"></canvas></div>
    </section>
    <section class="hp-chart-card hp-card-invoice">
      <div class="hp-chart-head"><div><b>Factures</b><span>Statuts</span></div></div>
      <div class="hp-chart-canvas"><canvas id="gaInvoices"></canvas></div>
    </section>
    <section class="hp-chart-card hp-card-approval">
      <div class="hp-chart-head"><div><b>Validations</b><span>Statuts</span></div></div>
      <div class="hp-chart-canvas"><canvas id="gaApprovals"></canvas></div>
    </section>
  </div>
</div>
<script>
(function(){
 const d = <?=json_encode($payload, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
 if(!window.Chart) return;

 const palette = ['#3B82F6','#10B981','#F59E0B','#8B5CF6','#06B6D4','#14B8A6','#EC4899','#FACC15','#F43F5E','#6366F1'];
 const paletteLight = palette.map(h => h+'22');

 Chart.defaults.font.family = "'Inter','SF Pro Display',-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif";
 Chart.defaults.font.size   = 11;
 Chart.defaults.color       = '#475569';
 Chart.defaults.animation   = { duration: 900, easing: 'easeOutQuart' };

 function el(id){ return document.getElementById(id); }
 function values(arr){ return (Array.isArray(arr)?arr:[]).map(v=>Number(v)||0); }
 function money(v){ return new Intl.NumberFormat('fr-FR',{maximumFractionDigits:0}).format(Number(v)||0)+' MAD'; }
 function number(v){ return new Intl.NumberFormat('fr-FR',{maximumFractionDigits:0}).format(Number(v)||0); }
 function compact(v){ return new Intl.NumberFormat('fr-FR',{notation:'compact',maximumFractionDigits:1}).format(Number(v)||0); }
 function hexToRgba(hex, a){
   const h = hex.replace('#','');
   return 'rgba('+parseInt(h.substring(0,2),16)+','+parseInt(h.substring(2,4),16)+','+parseInt(h.substring(4,6),16)+','+a+')';
 }

 const tooltipBase = {
   backgroundColor:'rgba(15,23,42,0.96)',
   titleColor:'#F8FAFC',
   bodyColor:'#E2E8F0',
   titleFont:{size:12,weight:'800',family:"'Inter',sans-serif"},
   bodyFont:{size:11,weight:'600',family:"'Inter',sans-serif"},
   padding:12, cornerRadius:10, displayColors:true, boxPadding:6,
   borderColor:'rgba(255,255,255,0.08)', borderWidth:1
 };

 /* Counts - horizontal bars with multi-color */
 new Chart(el('gaCounts'), {
   type:'bar',
   data:{labels:d.countLabels, datasets:[{
     label:'Total',
     data:values(d.countValues),
     backgroundColor:(ctx)=>{
       const a = ctx.chart.chartArea;
       if(!a) return palette[0];
       const g = ctx.chart.ctx.createLinearGradient(a.left, 0, a.right, 0);
       const color = palette[ctx.dataIndex % palette.length];
       g.addColorStop(0, hexToRgba(color, 0.95));
       g.addColorStop(1, hexToRgba(color, 0.55));
       return g;
     },
     borderRadius:6, borderSkipped:false,
     maxBarThickness:22,
     categoryPercentage:0.7, barPercentage:0.85
   }]},
   options:{
     responsive:true, maintainAspectRatio:false, indexAxis:'y',
     layout:{padding:{top:6,right:8,bottom:0,left:2}},
     plugins:{
       legend:{display:false},
       tooltip:{...tooltipBase, callbacks:{label:(c)=>'  '+c.label+': '+number(c.parsed.x)}}
     },
     scales:{
       x:{beginAtZero:true, grid:{color:'rgba(148,163,184,0.18)'}, border:{display:false}, ticks:{font:{size:10,weight:'600'},color:'#94A3B8',padding:6,callback:compact}},
       y:{grid:{display:false}, border:{display:false}, ticks:{font:{size:11,weight:'700'},color:'#475569',padding:4}}
     }
   }
 });

 /* Amounts - vertical bars with gradient */
 new Chart(el('gaAmounts'), {
   type:'bar',
   data:{labels:d.amountLabels, datasets:[{
     label:'Montant',
     data:values(d.amountValues),
     backgroundColor:(ctx)=>{
       const a = ctx.chart.chartArea;
       if(!a) return palette[0];
       const g = ctx.chart.ctx.createLinearGradient(0, a.top, 0, a.bottom);
       g.addColorStop(0, hexToRgba(palette[0], 0.95));
       g.addColorStop(1, hexToRgba(palette[0], 0.45));
       return g;
     },
     hoverBackgroundColor:hexToRgba(palette[0], 1),
     borderRadius:{topLeft:8, topRight:8, bottomLeft:0, bottomRight:0},
     borderSkipped:false,
     maxBarThickness:34,
     categoryPercentage:0.7, barPercentage:0.85
   }]},
   options:{
     responsive:true, maintainAspectRatio:false,
     layout:{padding:{top:6,right:8,bottom:0,left:2}},
     plugins:{
       legend:{display:false},
       tooltip:{...tooltipBase, callbacks:{label:(c)=>'  '+money(c.parsed.y)}}
     },
     scales:{
       x:{grid:{display:false}, border:{display:false}, ticks:{font:{size:10,weight:'700'},color:'#94A3B8',padding:6,maxRotation:30,minRotation:0}},
       y:{beginAtZero:true, grid:{color:'rgba(148,163,184,0.18)'}, border:{display:false}, ticks:{font:{size:10,weight:'600'},color:'#94A3B8',padding:8,callback:compact}}
     }
   }
 });

 /* Invoices - doughnut */
 new Chart(el('gaInvoices'), {
   type:'doughnut',
   data:{labels:d.invoiceStatusLabels, datasets:[{
     data:values(d.invoiceStatusValues),
     backgroundColor:palette,
     hoverBackgroundColor:palette.map(c=>hexToRgba(c, 0.85)),
     borderColor:'#fff', borderWidth:3, borderRadius:6, spacing:2, hoverOffset:8
   }]},
   options:{
     responsive:true, maintainAspectRatio:false, cutout:'68%', radius:'92%',
     layout:{padding:6},
     plugins:{
       legend:{position:'bottom', labels:{boxWidth:8,boxHeight:8,usePointStyle:true,padding:11,font:{size:10,weight:'700'},color:'#64748B'}},
       tooltip:{...tooltipBase, callbacks:{label:(c)=>'  '+c.label+': '+number(c.parsed)}}
     }
   }
 });

 /* Approvals - polar area */
 new Chart(el('gaApprovals'), {
   type:'polarArea',
   data:{labels:d.approvalStatusLabels, datasets:[{
     data:values(d.approvalStatusValues),
     backgroundColor:palette.map(c=>hexToRgba(c, 0.78)),
     borderColor:palette,
     borderWidth:2,
     hoverOffset:8
   }]},
   options:{
     responsive:true, maintainAspectRatio:false,
     layout:{padding:6},
     plugins:{
       legend:{position:'bottom', labels:{boxWidth:8,boxHeight:8,usePointStyle:true,padding:11,font:{size:10,weight:'700'},color:'#64748B'}},
       tooltip:{...tooltipBase, callbacks:{label:(c)=>'  '+c.label+': '+number(c.parsed.r)}}
     },
     scales:{
       r:{
         beginAtZero:true,
         grid:{color:'rgba(148,163,184,0.16)'},
         angleLines:{color:'rgba(148,163,184,0.16)'},
         pointLabels:{font:{size:10,weight:'700'},color:'#475569'},
         ticks:{display:false,backdropColor:'transparent'}
       }
     }
   }
 });
})();
</script>
<?php include __DIR__.'/../layouts/footer.php'; ?>
