(function(){
  const chat = document.getElementById('assistantChat');
  const form = document.getElementById('assistantForm');
  const input = document.getElementById('assistantInput');

  function escapeHtml(s){
    return String(s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  }

  function addBubble(role, text, links){
    const row = document.createElement('div');
    row.className = 'assistant-bubble ' + (role === 'user' ? 'user' : 'bot');

    const avatar = document.createElement('div');
    avatar.className = 'assistant-avatar';
    avatar.innerHTML = role === 'user' ? '<i class="fa-solid fa-user"></i>' : '<i class="fa-solid fa-robot"></i>';

    const msg = document.createElement('div');
    msg.className = 'assistant-message';
    msg.innerHTML = '<b>' + (role === 'user' ? 'You' : 'hakpoint AI') + '</b><p>' + escapeHtml(text).replace(/\n/g, '<br>') + '</p>';

    if (Array.isArray(links) && links.length) {
      const wrap = document.createElement('div');
      wrap.className = 'assistant-links';
      links.forEach(l => {
        if (!l || !l.url) return;
        const a = document.createElement('a');
        a.href = l.url;
        a.target = String(l.url).indexOf('uploads/') === 0 ? '_blank' : '_self';
        a.rel = 'noopener';
        a.textContent = l.label || l.url;
        wrap.appendChild(a);
      });
      msg.appendChild(wrap);
    }

    if (role === 'user') { row.appendChild(msg); row.appendChild(avatar); }
    else { row.appendChild(avatar); row.appendChild(msg); }
    chat.appendChild(row);
    chat.scrollTop = chat.scrollHeight;
  }

  function setThinking(on){
    let el = document.getElementById('assistantThinking');
    if (on && !el) {
      el = document.createElement('div');
      el.id = 'assistantThinking';
      el.className = 'assistant-bubble bot assistant-thinking';
      el.innerHTML = '<div class="assistant-avatar"><i class="fa-solid fa-robot"></i></div><div class="assistant-message"><b>hakpoint AI</b><p>Thinking...</p></div>';
      chat.appendChild(el);
      chat.scrollTop = chat.scrollHeight;
    } else if (!on && el) {
      el.remove();
    }
  }

  async function send(message){
    message = String(message || '').trim();
    if (!message) return;
    addBubble('user', message);
    input.value = '';
    input.focus();
    setThinking(true);
    try {
      const res = await fetch('index.php?page=assistant_chat', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-Token': window.GE_CSRF_TOKEN || ''},
        body: JSON.stringify({message})
      });
      const data = await res.json();
      setThinking(false);
      if (!res.ok || data.ok === false) {
        addBubble('bot', data.reply || 'hakpoint AI error.');
        return;
      }
      addBubble('bot', data.reply || 'No response', data.links || []);
    } catch (e) {
      setThinking(false);
      addBubble('bot', 'Assistant connection error: ' + e.message);
    }
  }

  if (form) form.addEventListener('submit', e => { e.preventDefault(); send(input.value); });
  document.querySelectorAll('[data-assistant-quick]').forEach(b => b.addEventListener('click', () => send(b.getAttribute('data-assistant-quick'))));
})();
