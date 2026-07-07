// v63 professional responsive helpers
(function(){
  function ready(fn){
    if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', fn);
    else fn();
  }
  ready(function(){
    document.querySelectorAll('table').forEach(function(table){
      if(table.closest('.ge-pdf-preview,.ge-pdf-paper,.pdf-preview,.signature-pad-wrap')) return;
      if(table.closest('.table-wrap,.table-responsive,.clean-table-box,.dol-table-wrap,.quote-lines-wrap,.pro-table-responsive')) return;
      var wrapper = document.createElement('div');
      wrapper.className = 'pro-table-responsive';
      table.parentNode.insertBefore(wrapper, table);
      wrapper.appendChild(table);
    });
    document.querySelectorAll('table').forEach(function(table){
      if(table.closest('.ge-pdf-preview,.ge-pdf-paper')) return;
      if(!table.className || String(table.className).trim() === '') table.classList.add('clean-table');
    });
  });
})();
