document.querySelectorAll('[data-menu-toggle]').forEach(a=>a.addEventListener('click',e=>{e.preventDefault();a.parentElement.classList.toggle('open')}));

document.addEventListener('click', function(e){
  const openBtn = e.target.closest('[data-open-modal]');
  if(openBtn){
    const id = openBtn.getAttribute('data-open-modal');
    const modal = document.getElementById(id);
    if(modal){ modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); document.body.classList.add('modal-lock'); }
  }
  const closeBtn = e.target.closest('[data-close-modal]');
  if(closeBtn){
    const id = closeBtn.getAttribute('data-close-modal');
    const modal = document.getElementById(id);
    if(modal){ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); document.body.classList.remove('modal-lock'); }
  }
  if(e.target.classList && e.target.classList.contains('crm-modal')){
    e.target.classList.remove('open'); e.target.setAttribute('aria-hidden','true'); document.body.classList.remove('modal-lock');
  }
});

document.addEventListener('keydown', function(e){
  if(e.key === 'Escape'){
    document.querySelectorAll('.crm-modal.open').forEach(m=>{m.classList.remove('open');m.setAttribute('aria-hidden','true');});
    document.body.classList.remove('modal-lock');
  }
});

// v12 quote list bulk action bar
(function(){
  const checks = Array.from(document.querySelectorAll('.quote-row-check'));
  const all = document.getElementById('quoteCheckAll');
  const bar = document.getElementById('quoteBulkBar');
  function syncQuoteBulk(){
    const selected = checks.filter(c=>c.checked);
    if(bar){ bar.classList.toggle('show', selected.length > 0); bar.setAttribute('aria-hidden', selected.length ? 'false' : 'true'); }
    checks.forEach(c=>{ const tr=c.closest('tr'); if(tr) tr.classList.toggle('selected', c.checked); });
    if(all){ all.checked = checks.length > 0 && selected.length === checks.length; all.indeterminate = selected.length > 0 && selected.length < checks.length; }
  }
  checks.forEach(c=>c.addEventListener('change', syncQuoteBulk));
  if(all){ all.addEventListener('change', ()=>{ checks.forEach(c=>c.checked=all.checked); syncQuoteBulk(); }); }
  const form = document.getElementById('quoteBulkForm');
  if(form){ form.addEventListener('submit', function(e){
    const action = form.querySelector('[name="bulk_action"]')?.value;
    const selected = checks.filter(c=>c.checked).length;
    if(!selected || !action){ e.preventDefault(); return; }
    if(action === 'delete' && !confirm('Supprimer les devis sélectionnés ?')) e.preventDefault();
  }); }
  syncQuoteBulk();
})();

// v16 invoice list bulk action bar
(function(){
  const checks = Array.from(document.querySelectorAll('.invoice-row-check'));
  const all = document.getElementById('invoiceCheckAll');
  const bar = document.getElementById('invoiceBulkBar');
  function syncInvoiceBulk(){
    const selected = checks.filter(c=>c.checked);
    if(bar){ bar.classList.toggle('show', selected.length > 0); bar.setAttribute('aria-hidden', selected.length ? 'false' : 'true'); }
    checks.forEach(c=>{ const tr=c.closest('tr'); if(tr) tr.classList.toggle('selected', c.checked); });
    if(all){ all.checked = checks.length > 0 && selected.length === checks.length; all.indeterminate = selected.length > 0 && selected.length < checks.length; }
  }
  checks.forEach(c=>c.addEventListener('change', syncInvoiceBulk));
  if(all){ all.addEventListener('change', ()=>{ checks.forEach(c=>c.checked=all.checked); syncInvoiceBulk(); }); }
  const form = document.getElementById('invoiceBulkForm');
  if(form){ form.addEventListener('submit', function(e){
    const action = form.querySelector('[name="bulk_action"]')?.value;
    const selected = checks.filter(c=>c.checked).length;
    if(!selected || !action){ e.preventDefault(); return; }
    if(action === 'delete' && !confirm('Supprimer les factures sélectionnées ?')) e.preventDefault();
  }); }
  syncInvoiceBulk();
})();


// v27 responsive + desktop collapsible sidebar
(function(){
  const sidebar = document.querySelector('.sidebar');
  const overlay = document.querySelector('.mobile-overlay');
  const toggle = document.querySelector('[data-sidebar-toggle]');
  const closeEls = document.querySelectorAll('[data-sidebar-close]');
  const storageKey = 'hakpointSidebarCollapsed';

  function isDesktop(){ return window.innerWidth > 900; }
  function openMenu(){ if(sidebar){sidebar.classList.add('open')} if(overlay){overlay.classList.add('show')} }
  function closeMenu(){ if(sidebar){sidebar.classList.remove('open')} if(overlay){overlay.classList.remove('show')} }
  function setCollapsed(collapsed){
    document.body.classList.toggle('sidebar-collapsed', collapsed);
    try{ localStorage.setItem(storageKey, collapsed ? '1' : '0'); }catch(e){}
    if(toggle){
      const icon = toggle.querySelector('i');
      if(icon){ icon.className = collapsed ? 'fa-solid fa-angles-right' : 'fa-solid fa-bars'; }
      toggle.setAttribute('aria-label', collapsed ? 'Ouvrir le menu' : 'Fermer le menu');
      toggle.setAttribute('title', collapsed ? 'Ouvrir le menu' : 'Fermer le menu');
    }
  }

  if(isDesktop()){
    try{ setCollapsed(localStorage.getItem(storageKey) === '1'); }catch(e){ setCollapsed(false); }
  }

  if(toggle){
    toggle.addEventListener('click', function(e){
      e.preventDefault();
      if(isDesktop()){
        setCollapsed(!document.body.classList.contains('sidebar-collapsed'));
        closeMenu();
      }else{
        openMenu();
      }
    });
  }

  closeEls.forEach(el=>el.addEventListener('click', closeMenu));
  document.querySelectorAll('.sidebar a[href]:not([href="#"])').forEach(a=>a.addEventListener('click', ()=>{
    if(!isDesktop()) closeMenu();
  }));
  window.addEventListener('resize', ()=>{
    if(isDesktop()){
      closeMenu();
      try{ setCollapsed(localStorage.getItem(storageKey) === '1'); }catch(e){}
    }else{
      document.body.classList.remove('sidebar-collapsed');
    }
  });
})();

// v18 invoice editable lines
(function(){
  const addBtn = document.querySelector('[data-add-invoice-line]');
  const table = document.querySelector('#invoiceLinesEditor tbody');
  const tpl = document.getElementById('invoiceLineTemplate');
  if(addBtn && table && tpl){
    addBtn.addEventListener('click', function(){
      table.appendChild(tpl.content.cloneNode(true));
    });
  }
  document.addEventListener('click', function(e){
    const btn = e.target.closest('[data-remove-line]');
    if(btn){
      const tbody = btn.closest('tbody');
      const rows = tbody ? tbody.querySelectorAll('tr') : [];
      if(rows.length > 1) btn.closest('tr').remove();
    }
  });
})();

// v22 tiers list bulk action bar
(function(){
  const checks = Array.from(document.querySelectorAll('.tiers-row-check'));
  const all = document.getElementById('tiersCheckAll');
  const bar = document.getElementById('tiersBulkBar');
  function syncTiersBulk(){
    const selected = checks.filter(c=>c.checked);
    if(bar){ bar.classList.toggle('show', selected.length > 0); bar.setAttribute('aria-hidden', selected.length ? 'false' : 'true'); }
    checks.forEach(c=>{ const tr=c.closest('tr'); if(tr) tr.classList.toggle('selected', c.checked); });
    if(all){ all.checked = checks.length > 0 && selected.length === checks.length; all.indeterminate = selected.length > 0 && selected.length < checks.length; }
  }
  checks.forEach(c=>c.addEventListener('change', syncTiersBulk));
  if(all){ all.addEventListener('change', ()=>{ checks.forEach(c=>c.checked=all.checked); syncTiersBulk(); }); }
  const form = document.getElementById('tiersBulkForm');
  if(form){ form.addEventListener('submit', function(e){
    const action = form.querySelector('[name="bulk_action"]')?.value;
    const selected = checks.filter(c=>c.checked).length;
    if(!selected || !action){ e.preventDefault(); return; }
    if(action === 'delete' && !confirm('Supprimer les tiers sélectionnés ?')) e.preventDefault();
  }); }
  syncTiersBulk();
})();

// v32 searchable selects: type and select at the same time, original select still submits normally
(function(){
  function textOf(option){ return (option.textContent || option.label || '').trim(); }
  function normalize(s){ return String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,''); }
  function shouldEnhance(select){
    if(!select || select.dataset.comboReady === '1') return false;
    if(select.multiple || select.size > 1) return false;
    if(select.closest('.hp-select-combo')) return false;
    if(select.dataset.noCombo === '1' || select.classList.contains('no-combo')) return false;
    return true;
  }
  function enhanceSelect(select){
    if(!shouldEnhance(select)) return;
    select.dataset.comboReady = '1';
    select.classList.add('hp-original-select');

    const wrap = document.createElement('div');
    wrap.className = 'hp-select-combo';
    const control = document.createElement('div');
    control.className = 'hp-combo-control';
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'hp-combo-input';
    input.autocomplete = 'off';
    input.placeholder = select.getAttribute('data-placeholder') || select.getAttribute('placeholder') || 'Sélectionner ou taper...';
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'hp-combo-btn';
    btn.innerHTML = '<i class="fa-solid fa-chevron-down"></i>';
    btn.setAttribute('aria-label','Ouvrir la liste');
    const list = document.createElement('div');
    list.className = 'hp-combo-list';

    control.appendChild(input);
    control.appendChild(btn);
    wrap.appendChild(control);
    wrap.appendChild(list);

    select.parentNode.insertBefore(wrap, select.nextSibling);
    wrap.appendChild(select);

    function selectedOption(){ return select.options[select.selectedIndex] || null; }
    function syncInput(){
      const opt = selectedOption();
      input.value = opt && opt.value !== '' ? textOf(opt) : '';
    }
    function options(){ return Array.from(select.options); }
    function close(){ wrap.classList.remove('is-open'); }
    function open(){ render(''); wrap.classList.add('is-open'); setTimeout(function(){ const first=list.querySelector('.hp-combo-option'); if(first) first.classList.add('is-active'); },0); }
    function choose(opt){
      select.value = opt.value;
      syncInput();
      close();
      select.dispatchEvent(new Event('change', {bubbles:true}));
    }
    function render(filter){
      const q = normalize(filter);
      list.innerHTML = '';
      let matched = options().filter(opt => {
        const t = textOf(opt);
        if(!q) return true;
        return normalize(t).includes(q) || normalize(opt.value).includes(q);
      });
      if(!matched.length){
        const empty = document.createElement('div');
        empty.className = 'hp-combo-empty';
        empty.textContent = 'Aucun résultat';
        list.appendChild(empty);
        return;
      }
      matched.forEach(opt => {
        const item = document.createElement('div');
        item.className = 'hp-combo-option';
        if(opt.selected) item.classList.add('is-selected');
        item.textContent = textOf(opt) || opt.value;
        item.dataset.value = opt.value;
        item.addEventListener('mousedown', function(e){ e.preventDefault(); choose(opt); });
        list.appendChild(item);
      });
    }
    function commitTypedValue(){
      const q = normalize(input.value.trim());
      if(q === ''){
        const emptyOpt = options().find(o => o.value === '');
        if(emptyOpt) choose(emptyOpt); else syncInput();
        return;
      }
      const exact = options().find(o => normalize(textOf(o)) === q || normalize(o.value) === q);
      if(exact){ choose(exact); } else { syncInput(); }
    }

    syncInput();
    select.addEventListener('change', syncInput);
    input.addEventListener('focus', open);
    input.addEventListener('click', function(){ open(); });
    input.addEventListener('pointerdown', function(){ setTimeout(open, 0); });
    input.addEventListener('input', function(){ render(input.value); wrap.classList.add('is-open'); });
    input.addEventListener('blur', function(){ setTimeout(function(){ if(!wrap.matches(':hover')){ commitTypedValue(); close(); } }, 120); });
    btn.addEventListener('click', function(e){ e.preventDefault(); wrap.classList.contains('is-open') ? close() : open(); input.focus(); });
    input.addEventListener('keydown', function(e){
      const visible = Array.from(list.querySelectorAll('.hp-combo-option'));
      let idx = visible.findIndex(x => x.classList.contains('is-active'));
      if(e.key === 'ArrowDown'){
        e.preventDefault(); open();
        if(visible.length){ if(idx >= 0) visible[idx].classList.remove('is-active'); idx = Math.min(idx + 1, visible.length - 1); visible[idx].classList.add('is-active'); visible[idx].scrollIntoView({block:'nearest'}); }
      }else if(e.key === 'ArrowUp'){
        e.preventDefault();
        if(visible.length){ if(idx >= 0) visible[idx].classList.remove('is-active'); idx = idx <= 0 ? 0 : idx - 1; visible[idx].classList.add('is-active'); visible[idx].scrollIntoView({block:'nearest'}); }
      }else if(e.key === 'Enter'){
        const active = list.querySelector('.hp-combo-option.is-active') || list.querySelector('.hp-combo-option');
        if(active){ e.preventDefault(); const opt = options().find(o => o.value === active.dataset.value); if(opt) choose(opt); }
      }else if(e.key === 'Escape'){
        close(); syncInput();
      }
    });
  }
  function init(root){
    (root || document).querySelectorAll('select').forEach(enhanceSelect);
  }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ init(document); }); else init(document);
  const mo = new MutationObserver(function(mutations){
    mutations.forEach(function(m){
      m.addedNodes.forEach(function(node){
        if(node.nodeType === 1){
          if(node.tagName === 'SELECT') enhanceSelect(node);
          else init(node);
        }
      });
    });
  });
  mo.observe(document.documentElement, {childList:true, subtree:true});
  document.addEventListener('click', function(e){
    document.querySelectorAll('.hp-select-combo.is-open').forEach(function(w){ if(!w.contains(e.target)) w.classList.remove('is-open'); });
  });
})();

// v34 product brand combo: allows typing and selecting with brand logos
(function(){
  function normalize(s){ return String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim(); }
  function initCombo(combo){
    if(!combo || combo.dataset.ready === '1') return;
    combo.dataset.ready = '1';
    const hidden = combo.querySelector('.brand-combo-value');
    const input = combo.querySelector('.brand-combo-input');
    const btn = combo.querySelector('.brand-combo-btn');
    const list = combo.querySelector('.brand-combo-list');
    const options = Array.from(combo.querySelectorAll('.brand-combo-option'));
    if(!hidden || !input || !btn || !list) return;

    function labelFor(value){
      const opt = options.find(o => normalize(o.dataset.value) === normalize(value) || normalize(o.dataset.label) === normalize(value));
      return opt ? (opt.dataset.label || opt.dataset.value) : value;
    }
    function syncSelected(){
      const v = normalize(hidden.value);
      options.forEach(o => o.classList.toggle('is-selected', normalize(o.dataset.value) === v || normalize(o.dataset.label) === v));
      if(hidden.value && input.value === hidden.value) input.value = labelFor(hidden.value);
    }
    function open(){ render(); combo.classList.add('is-open'); }
    function close(){ combo.classList.remove('is-open'); }
    function choose(opt){
      hidden.value = opt.dataset.value || opt.dataset.label || '';
      input.value = opt.dataset.label || opt.dataset.value || '';
      syncSelected(); close();
      hidden.dispatchEvent(new Event('change', {bubbles:true}));
    }
    function render(){
      const q = normalize(input.value);
      let shown = 0;
      list.querySelectorAll('.brand-combo-empty').forEach(e => e.remove());
      options.forEach(opt => {
        const hay = normalize((opt.dataset.label || '') + ' ' + (opt.dataset.value || ''));
        const show = !q || hay.includes(q);
        opt.classList.toggle('is-hidden', !show);
        if(show) shown++;
      });
      if(!shown){
        const empty = document.createElement('div');
        empty.className = 'brand-combo-empty';
        empty.textContent = 'Aucune marque trouvée — le texte tapé sera enregistré.';
        list.appendChild(empty);
      }
    }
    function commitTyped(){
      const val = input.value.trim();
      const exact = options.find(o => normalize(o.dataset.value) === normalize(val) || normalize(o.dataset.label) === normalize(val));
      if(exact) choose(exact);
      else { hidden.value = val; syncSelected(); close(); }
    }

    input.value = labelFor(hidden.value || input.value);
    syncSelected();
    input.addEventListener('focus', open);
    input.addEventListener('click', function(){ open(); });
    input.addEventListener('pointerdown', function(){ setTimeout(open, 0); });
    input.addEventListener('input', function(){ hidden.value = input.value.trim(); render(); combo.classList.add('is-open'); });
    input.addEventListener('blur', function(){ setTimeout(function(){ if(!combo.matches(':hover')) commitTyped(); }, 120); });
    btn.addEventListener('click', function(e){ e.preventDefault(); combo.classList.contains('is-open') ? close() : open(); input.focus(); });
    options.forEach(opt => opt.addEventListener('mousedown', function(e){ e.preventDefault(); choose(opt); }));
    input.addEventListener('keydown', function(e){
      const visible = options.filter(o => !o.classList.contains('is-hidden'));
      let idx = visible.findIndex(o => o.classList.contains('is-active'));
      if(e.key === 'ArrowDown'){
        e.preventDefault(); open(); if(visible.length){ if(idx>=0) visible[idx].classList.remove('is-active'); idx = Math.min(idx+1, visible.length-1); visible[idx].classList.add('is-active'); visible[idx].scrollIntoView({block:'nearest'}); }
      }else if(e.key === 'ArrowUp'){
        e.preventDefault(); if(visible.length){ if(idx>=0) visible[idx].classList.remove('is-active'); idx = idx<=0 ? 0 : idx-1; visible[idx].classList.add('is-active'); visible[idx].scrollIntoView({block:'nearest'}); }
      }else if(e.key === 'Enter'){
        const active = visible.find(o => o.classList.contains('is-active')) || visible[0];
        if(active){ e.preventDefault(); choose(active); }
      }else if(e.key === 'Escape'){
        close();
      }
    });
    document.addEventListener('click', function(e){ if(!combo.contains(e.target)) close(); });
  }
  function init(root){ (root || document).querySelectorAll('[data-brand-combo]').forEach(initCombo); }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ init(document); }); else init(document);
})();


// v35 client-side list filters: make filter rows and per-page select useful
(function(){
  function normalize(s){ return String(s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim(); }
  function initTable(table){
    if(!table || table.dataset.filtersReady === '1') return;
    const filterRow = table.querySelector('thead tr.quote-filters-row, thead tr.invoice-filters-row');
    const tbody = table.tBodies && table.tBodies[0];
    if(!filterRow || !tbody) return;
    table.dataset.filtersReady = '1';
    const filterCells = Array.from(filterRow.children);
    const controls = Array.from(filterRow.querySelectorAll('input, select'));
    controls.forEach(c => {
      if(c.type === 'checkbox') return;
      c.addEventListener('input', apply);
      c.addEventListener('change', apply);
      if(c.tagName === 'SELECT' && c.options.length <= 1){
        const cellIndex = filterCells.indexOf(c.closest('th'));
        const values = new Set();
        Array.from(tbody.rows).forEach(row => { const v=(row.cells[cellIndex]?.textContent || '').trim(); if(v) values.add(v); });
        Array.from(values).sort().slice(0,50).forEach(v => { const opt=document.createElement('option'); opt.value=v; opt.textContent=v; c.appendChild(opt); });
      }
    });
    function apply(){
      const rows = Array.from(tbody.rows).filter(r => !r.classList.contains('empty-row'));
      rows.forEach(row => {
        let show = true;
        filterCells.forEach((cell, idx) => {
          const inputs = Array.from(cell.querySelectorAll('input, select')).filter(c => c.type !== 'checkbox');
          if(!inputs.length || !show) return;
          const cellText = normalize(row.cells[idx]?.textContent || '');
          inputs.forEach(input => {
            const val = normalize(input.value);
            if(val && !cellText.includes(val)) show = false;
          });
        });
        row.dataset.filterHidden = show ? '0' : '1';
        row.style.display = show ? '' : 'none';
      });
    }
  }
  function init(){ document.querySelectorAll('table.quote-dol-list, table.invoice-dol-list, table.order-dol-list').forEach(initTable); }
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

// v48 security + clean table utilities
(function(){
  function addCsrfToForms(){
    const token = window.GE_CSRF_TOKEN || '';
    if(!token) return;
    document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(function(form){
      if(!form.querySelector('input[name="csrf_token"]')){
        const input=document.createElement('input'); input.type='hidden'; input.name='csrf_token'; input.value=token; form.appendChild(input);
      }
    });
  }
  function addCsrfToStateLinks(){
    const token = window.GE_CSRF_TOKEN || '';
    const pages = new Set(window.GE_CSRF_PAGES || []);
    if(!token || !pages.size) return;
    document.querySelectorAll('a[href*="index.php?page="]').forEach(function(a){
      try{
        const url=new URL(a.getAttribute('href'), window.location.href);
        const page=url.searchParams.get('page');
        if(pages.has(page) && !url.searchParams.get('csrf')){
          url.searchParams.set('csrf', token);
          a.setAttribute('href', url.pathname.split('/').pop()+url.search+url.hash);
        }
      }catch(e){}
    });
  }
  function ready(){ addCsrfToForms(); addCsrfToStateLinks(); }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', ready); else ready();
})();

function filterCleanTable(tableId){
  const table=document.getElementById(tableId); if(!table) return;
  const tbody=table.tBodies && table.tBodies[0]; if(!tbody) return;
  const controls=Array.from(table.querySelectorAll('thead .clean-filters [data-filter]'));
  const norm=s=>String(s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();
  Array.from(tbody.rows).forEach(row=>{
    if(row.classList.contains('empty-row')) return;
    let show=true;
    controls.forEach(ctrl=>{
      const val=norm(ctrl.value); if(!val || !show) return;
      const idx=parseInt(ctrl.dataset.filter,10);
      const cell=norm(row.cells[idx]?.textContent || '');
      if(!cell.includes(val)) show=false;
    });
    row.dataset.filterVisible = show ? '1' : '0';
    row.style.display=show?'':'none';
  });
  applyCleanPagination(tableId);
}
function clearCleanFilters(tableId){
  const table=document.getElementById(tableId); if(!table) return;
  table.querySelectorAll('thead .clean-filters [data-filter]').forEach(c=>{ c.value=''; });
  Array.from(table.tBodies[0]?.rows || []).forEach(row=>{ row.dataset.filterVisible='1'; row.style.display=''; });
  applyCleanPagination(tableId);
}
function applyCleanPagination(tableId){
  const table=document.getElementById(tableId); if(!table) return;
  const page=document.querySelector('.clean-select') || table.closest('.clean-list-page')?.querySelector('.clean-select');
  const limit=parseInt(page?.value || '999999',10) || 999999;
  let shown=0;
  Array.from(table.tBodies[0]?.rows || []).forEach(row=>{
    if(row.classList.contains('empty-row')) return;
    const visible=row.dataset.filterVisible !== '0';
    if(!visible){ row.style.display='none'; return; }
    shown++;
    row.style.display = shown<=limit ? '' : 'none';
  });
}
(function(){
  function init(){
    document.querySelectorAll('table.clean-table[id]').forEach(table=>{
      table.querySelectorAll('thead .clean-filters [data-filter]').forEach(c=>{
        c.addEventListener('input', ()=>filterCleanTable(table.id));
        c.addEventListener('change', ()=>filterCleanTable(table.id));
      });
      const sel=table.closest('.clean-list-page')?.querySelector('.clean-select');
      if(sel) sel.addEventListener('change', ()=>applyCleanPagination(table.id));
      applyCleanPagination(table.id);
    });
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

// v48 quote/order/invoice pager select (20/50/100) is now functional
(function(){
  function applyPager(container){
    const table=container.querySelector('table.quote-dol-list, table.invoice-dol-list, table.order-dol-list');
    const sel=container.querySelector('.quote-pager select');
    if(!table || !sel || !table.tBodies[0]) return;
    const limit=parseInt(sel.value || '20',10) || 20;
    let shown=0;
    Array.from(table.tBodies[0].rows).forEach(row=>{
      if(row.classList.contains('empty-row')) return;
      const filteredOut = row.dataset.filterHidden === '1';
      if(filteredOut){ row.style.display='none'; return; }
      shown++;
      row.style.display = shown<=limit ? '' : 'none';
    });
  }
  function init(){
    document.querySelectorAll('.quote-list-page').forEach(container=>{
      const table=container.querySelector('table.quote-dol-list, table.invoice-dol-list, table.order-dol-list');
      const sel=container.querySelector('.quote-pager select');
      if(!table || !sel) return;
      sel.addEventListener('change',()=>applyPager(container));
      table.querySelectorAll('thead input, thead select').forEach(c=>{
        c.addEventListener('input',()=>setTimeout(()=>applyPager(container),0));
        c.addEventListener('change',()=>setTimeout(()=>applyPager(container),0));
      });
      applyPager(container);
    });
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

// v51 real product line editor: selecting a product fills description, price, TVA, unit and cost.
(function(){
  function fillLineFromProduct(select){
    if(!select) return;
    const opt = select.options[select.selectedIndex];
    const tr = select.closest('tr');
    if(!opt || !tr) return;
    const ref = opt.dataset.ref || '';
    const label = opt.dataset.label || '';
    const desc = (ref + ' - ' + label).replace(/^\s*-\s*|\s*-\s*$/g,'').trim();
    const set = function(name, value, onlyIfEmpty){
      const el = tr.querySelector('[name="'+name+'[]"]');
      if(!el) return;
      if(onlyIfEmpty && String(el.value || '').trim() !== '') return;
      el.value = value;
      el.dispatchEvent(new Event('input', {bubbles:true}));
      el.dispatchEvent(new Event('change', {bubbles:true}));
    };
    if(select.value){
      set('line_description', desc, true);
      set('line_label', desc, true);
      set('line_tva', opt.dataset.vat || '20', false);
      set('line_pu_ht', opt.dataset.price || '0', false);
      set('line_unit', opt.dataset.unit || 'u.', false);
      set('line_cost_price', opt.dataset.cost || '0', false);
    }
  }
  function initProductLineEditors(root){
    (root || document).querySelectorAll('[data-product-line-select]').forEach(function(select){
      if(select.dataset.productLineReady === '1') return;
      select.dataset.productLineReady = '1';
      select.addEventListener('change', function(){ fillLineFromProduct(select); });
    });
  }
  window.geInitProductLineEditors = initProductLineEditors;
  if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function(){ initProductLineEditors(document); }); else initProductLineEditors(document);
})();

// v51 re-init product line selects when invoice line template is added.
(function(){
  document.addEventListener('click', function(e){
    if(e.target.closest('[data-add-invoice-line]')){
      setTimeout(function(){ if(window.geInitProductLineEditors) window.geInitProductLineEditors(document); }, 0);
    }
  });
})();


// v60 stock alert notification sound + desktop/mobile toast
(function(){
  function playStockAlertSound(){
    try{
      const AudioCtx = window.AudioContext || window.webkitAudioContext;
      if(!AudioCtx) return false;
      const ctx = new AudioCtx();
      const now = ctx.currentTime;
      const gain = ctx.createGain();
      gain.gain.setValueAtTime(0.001, now);
      gain.gain.exponentialRampToValueAtTime(0.12, now + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.001, now + 0.65);
      gain.connect(ctx.destination);
      [880, 660, 880].forEach(function(freq, i){
        const osc = ctx.createOscillator();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, now + i * 0.18);
        osc.connect(gain);
        osc.start(now + i * 0.18);
        osc.stop(now + i * 0.18 + 0.16);
      });
      setTimeout(function(){ try{ ctx.close(); }catch(e){} }, 900);
      return true;
    }catch(e){ return false; }
  }
  function showToast(count){
    if(!count || document.querySelector('.stock-alert-toast')) return;
    const toast=document.createElement('div');
    toast.className='stock-alert-toast';
    toast.innerHTML='<i class="fa-solid fa-triangle-exclamation"></i><div><b>Alerte stock</b><span>'+count+' produit(s) en stock critique.</span></div><button type="button" aria-label="Fermer">×</button>';
    document.body.appendChild(toast);
    toast.querySelector('button')?.addEventListener('click', function(){ toast.remove(); });
    setTimeout(function(){ toast.classList.add('show'); }, 30);
    setTimeout(function(){ toast.classList.remove('show'); setTimeout(function(){ toast.remove(); }, 220); }, 8000);
  }
  function init(){
    const count = Number(window.GE_STOCK_ALERT_COUNT || 0);
    const signature = String(window.GE_STOCK_ALERT_SIGNATURE || '');
    const onLogin = !!window.GE_STOCK_ALERT_ON_LOGIN;
    if(!count || !signature || !onLogin) return;
    let old='';
    try{ old=localStorage.getItem('hakpointStockAlertSignature') || ''; }catch(e){}
    showToast(count);
    if(old === signature) return;
    let played = playStockAlertSound();
    function unlock(){
      if(!played){ played = playStockAlertSound(); }
      if(played){ try{ localStorage.setItem('hakpointStockAlertSignature', signature); }catch(e){} cleanup(); }
    }
    function cleanup(){ document.removeEventListener('pointerdown', unlock); document.removeEventListener('keydown', unlock); }
    if(played){ try{ localStorage.setItem('hakpointStockAlertSignature', signature); }catch(e){} }
    else { document.addEventListener('pointerdown', unlock, {once:true}); document.addEventListener('keydown', unlock, {once:true}); }
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

// v72: universal light client-side pagination for list pages (20 rows shown per page)
(function(){
  function isListTable(table){
    return !!table.closest('.clean-list-page,.quote-list-page,.soc-list-page,.dol-list-page') && !table.closest('form.settings-form') && !table.classList.contains('purchase-lines-table');
  }
  function initTable(table){
    if(table.dataset.geClientPager === '1') return;
    if(!isListTable(table)) return;
    const tbody=table.tBodies && table.tBodies[0];
    if(!tbody) return;
    const rows=Array.from(tbody.rows).filter(function(r){ return !r.classList.contains('empty-row') && r.querySelectorAll('td').length; });
    if(rows.length <= 20) return;
    table.dataset.geClientPager='1';
    let page=1, per=20, pages=Math.max(1, Math.ceil(rows.length/per));
    const box=table.closest('.clean-table-box,.quote-table-wrap,.table-responsive,.soc-table-card') || table.parentElement;
    const pager=document.createElement('div');
    pager.className='ge-pager ge-js-pager';
    pager.innerHTML='<button class="btn small" type="button" data-prev>‹</button><span></span><button class="btn small" type="button" data-next>›</button>';
    box.after(pager);
    const label=pager.querySelector('span');
    const prev=pager.querySelector('[data-prev]');
    const next=pager.querySelector('[data-next]');
    function render(){
      rows.forEach(function(r,i){ r.style.display = (i >= (page-1)*per && i < page*per) ? '' : 'none'; });
      label.textContent = page+' / '+pages+' · '+rows.length+' lignes';
      prev.disabled = page<=1; next.disabled = page>=pages;
      prev.classList.toggle('disabled', page<=1); next.classList.toggle('disabled', page>=pages);
    }
    prev.addEventListener('click', function(){ if(page>1){ page--; render(); } });
    next.addEventListener('click', function(){ if(page<pages){ page++; render(); } });
    render();
  }
  function init(){ document.querySelectorAll('table.clean-table, table.quote-dol-list, table.soc-table').forEach(initTable); }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

// v74: universal Excel export button for list pages
(function(){
  const serverMap={
    products:'products', product_stock:'product_stock', tiers:'tiers', prospects:'prospects', clients:'clients', suppliers:'suppliers',
    warehouses:'warehouses', warehouse_movements:'warehouse_movements', quotes:'quotes', orders:'orders', invoices:'invoices', expeditions:'expeditions', receptions:'receptions',
    purchase_orders:'purchase_orders', supplier_invoices:'supplier_invoices', credit_notes:'credit_notes', credit_notes_list:'credit_notes', approvals:'approvals', approvals_list:'approvals',
    bank_accounts:'bank_accounts', payment_modes:'payment_modes', payments:'payments', supplier_payments:'supplier_payments', accounting:'accounting_entries', documents:'documents',
    projects:'projects', agenda:'agenda', pos:'pos_sales', manufacturing:'manufacturing_orders', currencies:'currencies', custom_fields:'custom_fields'
  };
  function pageName(){ try{return new URLSearchParams(location.search).get('page') || 'dashboard';}catch(e){return 'dashboard';} }
  function makeButton(type){
    const a=document.createElement('a');
    a.className='btn light ge-export-excel-btn';
    a.href='index.php?page=export_excel&type='+encodeURIComponent(type);
    a.setAttribute('data-export-excel','server');
    a.innerHTML='<i class="fa-solid fa-file-excel"></i> Exporter Excel';
    return a;
  }
  function insertSmart(btn){
    if(document.querySelector('.ge-export-excel-btn[data-export-excel="server"]')) return;
    const cleanTools=document.querySelector('.clean-list-head .clean-tools');
    if(cleanTools){ cleanTools.insertBefore(btn, cleanTools.firstChild); return; }
    const socTools=document.querySelector('.soc-list-top .soc-list-actions');
    if(socTools){ socTools.insertBefore(btn, socTools.firstChild); return; }
    const erpHead=document.querySelector('.erp-head');
    if(erpHead){ erpHead.appendChild(btn); return; }
    const excelHead=document.querySelector('.excel-panel-head');
    if(excelHead){ excelHead.appendChild(btn); return; }
    const listHead=document.querySelector('.clean-list-head,.soc-list-top,.ge-section-hero');
    if(listHead){ listHead.appendChild(btn); return; }
    const firstPanel=document.querySelector('.content,.main-content,main,body');
    if(firstPanel){ const wrap=document.createElement('div'); wrap.className='ge-export-toolbar'; wrap.appendChild(btn); firstPanel.prepend(wrap); }
  }
  function cleanCellText(cell){ return (cell.innerText || cell.textContent || '').replace(/\s+/g,' ').trim(); }
  function clientExportTable(table){
    const title=(document.querySelector('h1,h2,.clean-title span,.soc-list-title span')?.textContent || 'export').trim();
    const clone=table.cloneNode(true);
    clone.querySelectorAll('input,button,select,textarea,.actions-cell,.clean-row-tools').forEach(el=>{
      if(el.closest('td,th')) el.closest('td,th').remove(); else el.remove();
    });
    Array.from(clone.rows).forEach(row=>{ row.style.display=''; Array.from(row.cells).forEach(cell=>{ cell.textContent=cleanCellText(cell); }); });
    const html='<!DOCTYPE html><html><head><meta charset="UTF-8"><style>table{border-collapse:collapse;width:100%;font-family:Arial;font-size:12px}th{background:#eaf3fb;border:1px solid #b6c5d6;padding:7px;text-align:left}td{border:1px solid #d9e2ec;padding:6px;mso-number-format:"\\@"}tr:nth-child(even) td{background:#f8fafc}</style></head><body><h2>'+title+'</h2>'+clone.outerHTML+'</body></html>';
    const blob=new Blob(['\ufeff',html],{type:'application/vnd.ms-excel;charset=utf-8'});
    const url=URL.createObjectURL(blob); const a=document.createElement('a');
    a.href=url; a.download=title.toLowerCase().replace(/[^a-z0-9]+/gi,'-').replace(/^-|-$/g,'')+'-'+new Date().toISOString().slice(0,10)+'.xls';
    document.body.appendChild(a); a.click(); setTimeout(()=>{URL.revokeObjectURL(url); a.remove();},300);
  }
  function addFallbackTableButtons(){
    document.querySelectorAll('table.clean-table,table.soc-table,table.quote-dol-list,table.erp-table').forEach(function(table,idx){
      if(table.dataset.geExportAttached==='1' || table.classList.contains('purchase-lines-table') || table.closest('form.settings-form')) return;
      const rows=table.tBodies && table.tBodies[0] ? table.tBodies[0].rows.length : 0;
      if(rows<1) return;
      table.dataset.geExportAttached='1';
      const box=table.closest('.clean-table-box,.table-wrap,.table-responsive,.soc-table-card,.quote-table-wrap') || table.parentElement;
      if(!box || box.querySelector(':scope > .ge-table-export-mini')) return;
      const mini=document.createElement('div'); mini.className='ge-table-export-mini';
      const b=document.createElement('button'); b.type='button'; b.className='btn light small'; b.innerHTML='<i class="fa-solid fa-file-excel"></i> Exporter ce tableau';
      b.addEventListener('click', function(){ clientExportTable(table); });
      mini.appendChild(b); box.prepend(mini);
    });
  }
  function init(){
    const page=pageName();
    if(page === 'users') return;
    if(serverMap[page]) {
      insertSmart(makeButton(serverMap[page]));
    } else {
      addFallbackTableButtons();
    }
  }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
